<?php

namespace App\Http\Classes\modules\payrollcustomform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

use Carbon\Carbon;

class nb
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BIOMETRIC UPLOADING';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $payrollcommon;
  private $btnClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->payrollcommon = new payrollcommon;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 5510
    );
    return $attrib;
  }

  public function createHeadbutton($config)
  {
    $btns = [];
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['readfile']; // 'createschedule'
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'READ TXTFILE FROM BIOMETRIC';
    $obj[0]['access'] = 'view';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [['start', 'end']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.name', 'startdate');
    data_set($col1, 'end.name', 'enddate');
    return array('col1' => $col1);
  }

  public function stockstatusposted($config)
  {
    $companyid = $config['params']['companyid'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];

    $start =  date('Y-m-d', strtotime($start));
    $end =  date('Y-m-d', strtotime($end));

    switch ($config['params']['action']) {

      case 'readfile':
            $csv = $config['params']['csv'];
            $arrcsv = explode("\r\n", $csv);

            $counter = 1;
            foreach ($arrcsv as $arr) {
              $name = '';
              $time = '';
              $machno = '';
              $type = '';

              $newarr = explode("\t", $arr);
              // $this->othersClass->logConsole($counter . ' - ' . json_encode($newarr));
              if (count($newarr) == 1) {
                goto exithere;
              }

              // list($name, $time, $na1, $type, $machno) = $newarr;
              $name = trim($newarr[0]);
              $time = date('Y-m-d H:i:s', strtotime(trim($newarr[1])));
              $type = trim($newarr[2]);
              $machno = trim($newarr[3]);

              if ($type == "0") {
                $type = "IN";
              } else {
                $type = "OUT";
              }

              $cdate = date('Y-m-d', strtotime($time));

              // $this->othersClass->logConsole('time - ' . $cdate . ' -> ' . $start . ' to ' . $end);

              // if ($companyid == 58) goto SkipDateHere; //cdo 

              // if (($cdate >= $start) && ($cdate <= $end)) {
                // SkipDateHere:
                $chkexist = $this->coreFunctions->datareader("select userid as value from timerec where userid='" . $name . "' and timeinout='" . $time . "'");
                if ($chkexist == "") {
                  $qry = "insert into timerec (machno,userid,timeinout,mode,curdate) values (" . $machno . ",'" . $name . "','" . $time . "','" . $type . "','" . $cdate . "') ";
                  $this->coreFunctions->execqry($qry);
                }
              // }
              $counter = $counter + 1;
            }

        exithere:
        return ['status' => true, 'msg' => 'Readfile Successfully', 'data' => $arrcsv];
        break;

    }
  }


  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select 
      date(now()) as startdate,
      date(now()) as enddate");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    return [];
  }


} //end class
