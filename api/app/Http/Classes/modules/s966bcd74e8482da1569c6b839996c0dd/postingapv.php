<?php

namespace App\Http\Classes\modules\s966bcd74e8482da1569c6b839996c0dd;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class postingapv
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Payable Vouchers - For Posting';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  
  public $prefix = '';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';

  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablenum = 'cntnum';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $doclistdaterange = 12;

  private $fields = [
    'trno',
    'docno',
    'dateid',
    'clientname',
    'rem'
  ];
  private $except = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = false;
  private $reporter;
    public $showfilterlabel = [
        // ['val' => 'open', 'label' => 'Open', 'color' => 'primary'],
        // ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
        // ['val' => 'posted', 'label' => 'For Checking', 'color' => 'primary'],
        // ['val' => 'complete', 'label' => 'Completed', 'color' => 'primary'],
        // ['val' => 'cancelled', 'label' => 'Cancelled', 'color' => 'primary'],
        // ['val' => 'all', 'label' => 'All', 'color' => 'primary']
    ];


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 380,
      'edit' => 380,
      'save' => 380
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    // $gridcolumns = ['action', 'dateid', 'docno', 'clientname', 'rem'];
    // $stockbuttons = ['pickerdropall'];//['viewtsdetail'];
    // $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];

    // foreach ($gridcolumns as $key => $value) {
    //     $$value = $key;
    // }
    
    // $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    // $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    // $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    // $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

    // $obj[0][$this->gridname]['columns'][$action]['btns']['pickerdropall']['label'] = 'Post';
    // $obj[0][$this->gridname]['columns'][$action]['btns']['pickerdropall']['confirm'] = true;
    // $obj[0][$this->gridname]['columns'][$action]['btns']['pickerdropall']['action'] = 'tableentrystatus';
    // $obj[0][$this->gridname]['columns'][$action]['btns']['pickerdropall']['confirmlabel'] = 'Are you sure you want to post this transaction?';
    // return $obj;
    

     $getcols = ['action', 'dateid', 'docno', 'clientname', 'rem'];
     $stockbuttons = ['pickerdropall'];

     foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    
   
    $cols[$action]['btns']['pickerdropall']['label'] = 'Post';
    $cols[$action]['btns']['pickerdropall']['confirm'] = true;
    $cols[$action]['btns']['pickerdropall']['confirmlabel'] = 'Are you sure you want to post this transaction?';
    
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $center = $config['params']['center'];
    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, head.clientname ,'tableentries/tableentry/postingapv' as url,head.rem
    from lahead as head left join cntnum on cntnum.trno=head.trno 
    where head.doc='PV'  and head.lockdate is not null and cntnum.center ='".$center."'
    order by dateid,docno";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'post',
      'unpost',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton


  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = ['viewhistoricalcomments'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['lblchoice','tasktitle'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'lblchoice.label',  'Task');
    data_set($col1, 'tasktitle.label',  '');

    $fields = ['lblanswer','startdate'];
    $col2 = $this->fieldClass->create($fields);
     data_set($col2, 'lblanswer.label',  'Start Date');
     data_set($col2, 'startdate.label',  '');

    $fields = ['lblbank','enddate'];
    $col3 = $this->fieldClass->create($fields);
     data_set($col3, 'lblbank.label',  'End Date');
     data_set($col3, 'enddate.label',  '');
    return array('col1' => $col1,'col2' => $col2,'col3' => $col3);
  }

  public function newclient($config)
  {
    // $data = $this->resetdata($config, $config['newclient']);
      $data = [];
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($config, $client = '')
  {
    $companyid = $config['params']['companyid'];
    $data = [];
    $data[0]['client'] = '';
    $data[0]['clientid'] = 0;
    return $data;
  }


  public function loadheaddata($config)
  {
    $center = $config['params']['center'];
    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, head.clientname ,'tableentries/tableentry/postingapv' as url,head.rem
    from lahead as head left join cntnum on cntnum.trno=head.trno 
    where head.doc='PV'  and head.lockdate is not null and cntnum.center ='".$center."'
    order by dateid,docno";
    $head = $this->coreFunctions->opentable($qry);
    if (!empty($head)) {
      return  ['reloadtableentry' => true,'head' => $head, 'isnew' => false, 'status' => true, 'msg' => '', 'islocked' => false, 'isposted' => false, 'qq' => $trno];
    } else {
      $head = $this->resetdata($config);
      return ['reloadtableentry' => true,'status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];

    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $clientid = 0;
    $msg = '';
    // $trno=0;

    if($isupdate){
      $trno = $head['trno']; // trno on tmhead
       $line = $head['line'];
    }   
    

      foreach ($this->fields as $key) {
      // if (isset($head[$key]) || is_null($head[$key]))
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }

    
    // if ($isupdate) {
      $this->coreFunctions->logConsole($trno.'trno update');
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno,'line' => $line]);
    

    return ['status' => $msg == '' ? true : false, 'msg' => $msg,'trno'=>$trno, 'line' => $line];
  } // end function


  public function getlastclient($pref)
  {
    return '';
  }

   public function stockstatusposted($config)
  {
    return $this->completetask($config);
  }


   public function completetask($config){
    $path = 'App\Http\Classes\modules\payable\pv';
    $config['params']['trno'] = $config['params']['row']['trno'];
    $stat =  app($path)->posttrans($config);
    $doc = $config['params']['doc'];
    //$data=$this->loaddata($config);
    $config['params']['doc'] = $doc;
    if($stat['status']){
        return ['status'=> true,'msg'=>'Posted Successfully','action' => 'reloadlisting'];
        //return $this->loaddata($config);
    }else{
        return ['status'=> false,'msg'=>$stat['msg'],'action' => 'reloadlisting'];
    }

  }

    private function getstockselect($clientid,$config)
  {
    $sqlselect = "";
    return $sqlselect;
  }

  public function openstock($clientid, $config)
  {
    $sqlselect = $this->getstockselect($clientid,$config);
    return  $sqlselect;
  }

  public function deletetrans($config)
  {
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


 // -> print function
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

     $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);
    $config['params']['trno'] = $config['params']['dataid'];
    $dataparams = $config['params']['dataparams'];

    // $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
