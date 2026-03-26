<?php

namespace App\Http\Classes;

/*
use Session;*/

use PDF;

// use Illuminate\Http\Request;
use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use Exception;
use Throwable;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;



class reportlistClass
{
  private $othersClass;
  private $coreFunctions;
  private $headClass;
  private $lookupClass;
  private $logger;
  private $companysetup;
  private $config = [];
  private $sqlquery;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->headClass = new headClass;
    $this->lookupClass = new lookupClass;
    $this->logger = new Logger;
    $this->companysetup = new companysetup;
    $this->sqlquery = new sqlquery;
  }

  public function sbc($params)
  {
    $path = preg_replace('/\s+/', '_', $params['path']);
    $code = preg_replace('/\s+/', '_', $params['name']);
    $classname = __NAMESPACE__ . '\\modules\\reportlist\\' . $path . '\\' . $code;
    try {
      $this->config['classname'] = $classname;
      $this->config['docmodule'] = new $classname;
      $this->config['reportdir'] = ['path' => $path, 'code' => $code];

      if (isset($this->config['params']['logintype'])) {
        if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
          $access = $this->othersClass->getportalaccess($params['user']);
        } else {
          $access = $this->othersClass->getAccess($params['user']);
        }
      } else {
        $access = $this->othersClass->getAccess($params['user']);
      }
      $this->config['access'] = json_decode(json_encode($access), true);
    } catch (Exception $e) {
      echo $e;
      return $this;
    }


    $this->config['params'] = $params;
    return $this;
  }


  public function loadform()
  {
    $txtfield = $this->config['docmodule']->createHeadField($this->config);
    $txtdata = $this->config['docmodule']->paramsdata($this->config);
    $modulename = $this->config['docmodule']->modulename;
    $data = $this->config['docmodule']->getloaddata($this->config);
    $style = $this->config['docmodule']->style;
    $directprint = $this->config['docmodule']->directprint;
    if (isset($this->config['docmodule']->showemailbtn)) {
      $showemailbtn = $this->config['docmodule']->showemailbtn;
    } else {
      $showemailbtn = false;
    }
    $sbcscript = [];
    if (method_exists($this->config['classname'], 'sbcscript')) {
      $sbcscript = $this->config['docmodule']->sbcscript($this->config);
    }

    $this->config['return'] = ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'reportdir' => $this->config['reportdir'], 'style' => $style, 'directprint' => $directprint, 'showemailbtn' => $showemailbtn,'sbcscript'=>$sbcscript];
    return $this;
  }

  public function lookupsetup()
  {
    $this->config['return'] = $this->lookupClass->lookupsetup($this->config);
    return $this;
  } // end function

  public function sendemail()
  {
    $this->config['return'] = $this->config['docmodule']->sendemail($this->config);
    return $this;
  }


  public function reportdata()
  {

    $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());
    $modulename = $this->config['docmodule']->modulename;
    $method = Request::Method();
    if ($method == 'GET') {
      $params = json_decode($this->config['params']['dataparams'], true);
      $this->config['params']['dataparams'] = $params;
    }
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    if ($this->companysetup->getmultibranch($this->config['params'])) {
      if (isset($this->config['params']['dataparams']['center'])) {

        $centeraccess = $this->othersClass->checkAccess($this->config['params']['user'], 4165);
        if (!$centeraccess) {
          if ($this->config['params']['dataparams']['center'] == '') {

            if ($this->config['params']['companyid'] == 29 ) goto ContinueHere; //SBC
            if ($this->config['params']['companyid'] == 56 && ($modulename == 'Monthly Summary of EWT Report' || $modulename == 'Homeworks Sales Report')) goto ContinueHere; //  homeworks
            $this->config['return']['report'] = $this->othersClass->custommsgreport($this->config, 'Select valid branch');
            return $this;
          } else {
            $qry = "select c.code as value from center as c left join centeraccess as ca on ca.center=c.code left join useraccess as u on u.userid=ca.userid 
                      where u.username='" . $this->config['params']['user'] . "' and c.code='" . $this->config['params']['dataparams']['center'] . "'";
            $accesscenter = $this->coreFunctions->datareader($qry);
            if ($accesscenter == '') {
              $this->config['return']['report'] = $this->othersClass->custommsgreport($this->config, 'Access denied for Branch ' . $this->config['params']['dataparams']['centername']);
              return $this;
            }
          }
        }
      }

      if (isset($this->config['params']['dataparams']['wh'])) {

        $centeraccess = $this->othersClass->checkAccess($this->config['params']['user'], 4165);
        if (!$centeraccess) {
          if ($this->config['params']['dataparams']['wh'] == '') {
            if ($this->config['params']['companyid'] == 29) {
              goto ContinueHere; //SBC
            }

            if ($this->config['params']['companyid'] == 60 && $modulename == 'Inventory Balance') {//transpower
              goto ContinueHere; 
            }

            $this->config['return']['report'] = $this->othersClass->custommsgreport($this->config, 'Select valid warehouse');
            return $this;
          } else {
            if ($this->config['params']['companyid'] == 60 && $modulename == 'Inventory Balance') {//transpower
              goto ContinueHere; 
            }
            $qry = "select c.warehouse as value from center as c left join centeraccess as ca on ca.center=c.code left join useraccess as u on u.userid=ca.userid 
                      where u.username='" . $this->config['params']['user'] . "' and c.code='" . $this->config['params']['center'] . "' and c.warehouse='" . $this->config['params']['dataparams']['wh'] . "'";
            $accesscenter = $this->coreFunctions->datareader($qry);
            if ($accesscenter == '') {
              $this->config['return']['report'] = $this->othersClass->custommsgreport($this->config, 'Access denied for Warehouse ' . $this->config['params']['dataparams']['wh']);
              return $this;
            }
          }
        }
      }
    }

    ContinueHere:
    $reportdata = ['userid' => $this->config['params']['user'], 'dateid' => $this->othersClass->getCurrentTimeStamp(), 'valueid' => $this->config['params']['name'], 'sectionid' => 'REPORTS'];
    if ($this->config['params']['dataparams']['print'] == 'default' || $this->config['params']['dataparams']['print'] == 'excel' || $this->config['params']['dataparams']['print'] == 'mobile') {
      $this->config['return'] = $this->config['docmodule']->reportdata($this->config);
      $ret = $this->reportstrsave($this->config['return']['report']);
      $addreturn = ['report' => $ret['str'], 'path' => $ret['filename'],'count'=>$ret['count'],'callback'=>true,'action'=>'reportstr'];

      $this->config['return'] = array_merge($this->config['return'], $addreturn);

      $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
      $elapsed = $start->diffInSeconds($end);
      $reportdata['valueid'] = $this->config['params']['name'] . " (" . $elapsed . "s)";
      if (isset($this->config['docmodule']->closeonprint)) {
        $this->config['return']['closeonprint'] = $this->config['docmodule']->closeonprint;
      }
      $this->coreFunctions->sbcinsert('reportlog', $reportdata);
    } else if ($this->config['params']['dataparams']['print'] == 'CSV') {
      $this->config['return'] = $this->config['docmodule']->reportdatacsv($this->config);
      $ret = $this->csvbatchsave($this->config['return']['data']);
      $addreturn = ['data' => $ret['data'], 'path' => $ret['filename'],'count'=>$ret['count'],'callback'=>true,'action'=>'csvbatch'];
      
       $this->config['return'] = array_merge($this->config['return'], $addreturn);
    //  }

      return $this;
    } else if ($this->config['params']['dataparams']['print'] == 'PDFM') {
      $this->config['return'] = $this->config['docmodule']->reportdata($this->config);
      $this->config['return'] = $this->config['return']['report'];
    } else {
      $repdata = $this->config['docmodule']->reportdata($this->config);

      $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
      $elapsed = $start->diffInSeconds($end);
      $reportdata['valueid'] = $this->config['params']['name'] . " (" . $elapsed . "s)";
      $this->coreFunctions->sbcinsert('reportlog', $reportdata);

      switch (strtolower($repdata['params']['format'])) {
        case 'legal':
          if (strtolower($repdata['params']['orientation']) == 'p') {
            $width = 800;
            $height = 1000;
          } else {
            $width = 1000;
            $height = 800;
          }
          break;
        case 'a4':
          if (strtolower($repdata['params']['orientation']) == 'p') {
            $width = 760;
            $height = 950;
          } else {
            $width = 950;
            $height = 760;
          }
          break;
        default: // letter
          if (strtolower($repdata['params']['orientation']) == 'p') {
            $width = 800;
            $height = 1000;
          } else {
            $width = 1000;
            $height = 800;
          }
          break;
      }
      $width = PDF::pixelsToUnits($width);
      $height = PDF::pixelsToUnits($height);
      PDF::SetTitle($this->config['params']['name']);
      PDF::AddPage($repdata['params']['orientation'], [$width, $height]);
      PDF::setPageUnit('px');
      PDF::SetMargins(0, 0);
      PDF::writeHtml($repdata['report'], true, 0, true, 0);

      $pdf = PDF::Output($this->config['params']['name'] . '.pdf', 'S');
      if ($method == 'GET') {
        $this->config['return'] = $pdf;
      } else {
        $this->config['params']['pdf'] = $pdf;
        Mail::to($this->config['params']['email'])->send(new SendMail($this->config['params']));
        $this->config['return'] = $repdata['report'];
      }
    }
    return $this;
  }

  public function reportlookupsearch()
  {
    $this->config['return'] = $this->lookupClass->lookupsearch($this->config);
    return $this;
  }


  function reportstrsave($str)
  {
    //format: eto ung need ilagay sa file ng report
    //    $ret = $this->reporter->reportstrsave($config,$str);
    //return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $ret['str'], 'params' => $this->reportParams, 'path' => $ret['filename'],'count'=>$ret['count'],'callback'=>true,'action'=>'reportstr'];


      $filename = 'reportstr/'.$this->config['params']['name'].$this->config['params']['user'];
        // Create directory if it doesn't exist
        if (!Storage::disk('sbcpath')->exists(dirname($filename))) {
            Storage::disk('sbcpath')->makeDirectory(dirname($filename));
        }
        $chunks = str_split($str,40000000);
        $count = 0;
        $returnstr = '';
        foreach ($chunks as $key => $value) {
           if ($key == 0) {
             $returnstr = $value;
           } else {
             $putResult = Storage::disk('sbcpath')->put($filename.$key.'.sbc', $value);            
           }
           $count = $key;
        }
        
        return ['filename'=>$filename,'status'=>'ok', 'count'=>$count, 'str'=>$returnstr];
  }

    function csvbatchsave($data)
    {

    // format: eto ung need ilagay sa file ng report tapos CSV ung Print Type
    //public function reportdatacsv($config)
    //  {
    //    $data = $this->coreFunctions->opentable("select * from coa");
    //    $ret = $this->reporter->csvbatchsave($config,$data);
    //    return ['status' => true, 'msg' => 'Generating CSV successfully', 'data' => $ret['data'], 'params' => $this->reportParams, 'path' => $ret['filename'],'count'=>$ret['count'],'callback'=>true,'action'=>'csvbatch','name'=>'chartOfAccount']; 
    //  }
 
      $filename = 'csvfile/'.$this->config['params']['name'].$this->config['params']['user'];        
        // Create directory if it doesn't exist
        if (!Storage::disk('sbcpath')->exists(dirname($filename))) {
            Storage::disk('sbcpath')->makeDirectory(dirname($filename));
        }
        $str = json_encode($data);
        $chunks = str_split($str,1000000000);
        $count = 0;
        $returnstr = '';
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $path=[];
        foreach ($chunks as $key => $value) {
           if ($key == 0) {
             $returnstr = $value;
           } else {
             $putResult = Storage::disk('sbcpath')->put($filename.date_format(date_create($current_timestamp), 'mdYHis').$key.'.sbc', $value);            
             array_push($path,$filename.date_format(date_create($current_timestamp), 'mdYHis').$key.'.sbc');
           }
           $count = $key;
        }
        $count=0;                    
        return ['filename'=>$path,'status'=>'ok', 'count'=>$count, 'data'=>$returnstr];

    }


  public function execute()
  {
    if (Request::isMethod('get')) {
      return $this->config['return'];
    } else {
      return response()->json($this->config['return'], 200);
    }
  } // end function






































} // end class
