<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\purchase\ra;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\sbcscript\sbcscript;
use DateInterval;
use DatePeriod;
use DateTime;

class leaveapplicationportal
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LEAVE APPLICATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $linkemail;
  private $sqlquery;
  private $payrollcommon;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $stock = 'leavetrans';
  public $prefix = '';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;
  private $isexist = 0;
  private $sbcscript;

  private $fields = [
    'dateid',
    'status',
    'adays',
    'remarks',
    'effectivity',
    'empid',
    'batchid'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = [];
  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = false;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'With Balance', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Without Balance', 'color' => 'primary'],
    ['val' => 'history', 'label' => 'Previous', 'color' => 'primary']
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
    $this->linkemail = new linkemail;
    $this->payrollcommon = new payrollcommon;
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2380,
      // 'new' => 1593,
      'save' => 2376,
      // 'delete' => 1594,
      'print' => 2377,
      'edit' => 2381,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $leavelabel = $this->companysetup->getleavelabel($config['params']);
    $companyid = $config['params']['companyid'];
    $getcols = ['action', 'listdocument', 'startdate', 'enddate', 'codename', 'days', 'adays', 'bal', 'hired'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$codename]['label'] = 'Leave Type';
    $cols[$days]['label'] = $leavelabel;

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;text-align:left';
    $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$startdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$enddate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$codename]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;text-align:left';
    $cols[$days]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:left';
    $cols[$adays]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:left';
    $cols[$bal]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left';

    $cols[$hired]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left';
    if ($companyid == 58) { //cdo
      // $cols[$startdate]['type'] = 'coldel';
      // $cols[$enddate]['type'] = 'coldel';
      $cols[$days]['label'] = 'Total Leave Credit';
      $cols[$adays]['label'] = 'Leave Credit Used';
      $cols[$bal]['label'] = 'Remaining Leave';
      $cols[$adays]['align'] = 'text-align';
    } else {
      $cols[$adays]['type'] = 'coldel';
      $cols[$hired]['type'] = 'coldel';
    }
    if ($companyid == 44) { //stonepro
      $this->showfilterlabel = [];
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $filteroption = '';
    $option = $config['params']['itemfilter'];
    if ($option == 'draft') {
      $filteroption = " and s.bal<>0";
    } else {
      $filteroption = " and s.bal=0";
    }
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['e.emplast', 'e.empfirst', 'e.empmiddle', 'p.codename', 's.docno'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $curdate = $this->othersClass->getCurrentDate();
    $id = $config['params']['adminid'];

    switch ($option) {
      case "history":
        $qry = "select CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname,
            p.codename,if(p.code = 'PT122','',s.days) as days, if(p.code = 'PT122','',s.bal) as bal,
            (s.days-s.bal) as adays, s.trno as clientid, date(dateid) as dateid, 
            date_format(prdstart, '%m-%d-%y') as startdate, date_format(prdend, '%m-%d-%y') as enddate, s.docno,
            date_format(e.hired,'%M %d, %Y') as hired, 'HISTORY' as type
            from leavesetup as s
            left join employee as e on e.empid=s.empid
            left join client as cl on cl.clientid = e.empid
            left join paccount as p on p.line=s.acnoid
            where cl.clientid = '$id' and year('" . $curdate . "') = (year(dateid)+1) " . $filtersearch . " 
            order by CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle), p.codename";
        break;
      default:
        $qry = "
            select CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname,
            p.codename,if(p.code = 'PT122','',s.days) as days, if(p.code = 'PT122','',s.bal) as bal,
            (s.days-s.bal) as adays, s.trno as clientid, date(dateid) as dateid, 
            date_format(prdstart, '%m-%d-%y') as startdate, date_format(prdend, '%m-%d-%y') as enddate, s.docno,
            date_format(e.hired,'%M %d, %Y') as hired, 'CURRENT' as type
            from leavesetup as s
            left join employee as e on e.empid=s.empid
            left join client as cl on cl.clientid = e.empid
            left join paccount as p on p.line=s.acnoid
            where cl.clientid = '$id' and date('" . $curdate . "') between date(prdstart) and date(prdend) "  . $filteroption . " " . $filtersearch . "
            order by CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle), p.codename";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $btns = array(
      'load',
      // 'new',
      'save',
      // 'delete',
      'cancel',
      'print',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    if ($companyid == 53 || $companyid == 44) { //camera | stonepro
      $buttons['edit']['label'] = 'create';
    }
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewleaveapplication', 'label' => 'DETAILS']];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }
  public function createtab2($access, $config)
  {
    //testing for cdohris approver list tab
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { //cdohris
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryapproverlist', 'label' => 'Approver List', 'access' => 'view']];
      $obj = $this->tabClass->createtab($tab, []);
      $return['APPROVER LIST'] = ['icon' => 'fa fa-users', 'tab' => $obj];
    } else {
      $return = [];
    }
    return $return;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $leavelabel = $this->companysetup->getleavelabel($config['params']);

    if ($companyid == 58) { //cdo
      $fields = ['client', ['docno', 'acno'], 'acnoname'];
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'docno.type', 'input');
      data_set($col1, 'docno.class', 'csdocno sbccsreadonly');
      data_set($col1, 'client.type', 'hidden');
      data_set($col1, 'acno.type', 'input');
      data_set($col2, 'acnoname.label', 'Type of Leave');
      data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');

      $fields = [['start', 'end'], 'hours'];
      $col2 = $this->fieldClass->create($fields);

      data_set($col2, 'start.label', 'Start Date');
      data_set($col2, 'start.required', true);

      data_set($col2, 'end.name', 'effectivity');
      data_set($col2, 'end.label', 'End Date');
      data_set($col2, 'end.required', true);

      data_set($col2, 'hours.label', 'Leave ' . $leavelabel);
      data_set($col2, 'hours.required', true);
      data_set($col2, 'hours.maxlength', 2);

      data_set($col2, 'hours.type', 'lookup');
      data_set($col2, 'hours.class', 'sbccsreadonly');
      data_set($col2, 'hours.action', 'lookupdays');
      data_set($col2, 'hours.lookupclass', 'lookupdays');

      $fields = ['remarks'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'remarks.type', 'ctextarea');


      $fields = [];
      $col4 = $this->fieldClass->create($fields);
    } else {
      $fields = ['client', ['docno', 'acno'], 'acnoname', ['start', 'end']];
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'docno.type', 'input');
      data_set($col1, 'docno.class', 'csdocno sbccsreadonly');
      data_set($col1, 'client.type', 'hidden');
      data_set($col1, 'acno.type', 'input');
      data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');
      data_set($col1, 'start.name', 'prdstart');
      data_set($col1, 'start.label', 'Period From');
      data_set($col1, 'start.class', 'csprdstart sbccsreadonly');
      data_set($col1, 'end.name', 'prdend');
      data_set($col1, 'end.label', 'Period To');
      data_set($col1, 'end.class', 'csprdend sbccsreadonly');

      $fields = [['dateid', 'empstat'], ['days', 'bal']];
      $col2 = $this->fieldClass->create($fields);

      data_set($col2, 'empstat.type', 'input');
      data_set($col2, 'empstat.class', 'csempstat sbccsreadonly');
      data_set($col2, 'empstat.action', 'lookupleavestatus');
      data_set($col2, 'empstat.lookupclass', 'lookupleavestatus');
      data_set($col2, 'empstat.name', 'status');
      data_set($col2, 'empstat.required', false);
      data_set($col2, 'dateid.label', 'Date Created');
      data_set($col2, 'dateid.type', 'input');
      data_set($col2, 'dateid.class', 'csdateid sbccsreadonly');
      data_set($col2, 'days.label', 'Entitled');
      data_set($col2, 'days.name', 'days');
      data_set($col2, 'days.class', 'csdays sbccsreadonly');
      data_set($col2, 'bal.label', 'Remaining');
      data_set($col2, 'bal.name', 'bal');
      data_set($col2, 'bal.class', 'csbal sbccsreadonly');

      $fields = [['hours', 'end']];
      if ($companyid == 51) { // ulitc
        array_push($fields, 'batch', 'ispickupdate', 'statrem', 'remarks'); #'selectprefix'
      } else {
        array_push($fields, 'remarks');
      }
      $col3 = $this->fieldClass->create($fields);

      if ($leavelabel == 'Days') {
        $leavelabel = 'Day';
      }
      data_set($col3, 'remarks.type', 'ctextarea');
      data_set($col3, 'remarks.required', true);
      data_set($col3, 'hours.label', 'Leave ' . $leavelabel);
      data_set($col3, 'hours.type', 'input');
      data_set($col3, 'hours.required', true);
      data_set($col3, 'hours.maxlength', 2);

      data_set($col3, 'end.name', 'effectivity');
      data_set($col3, 'end.label', 'Effectivity of Leave');
      data_set($col3, 'end.required', true);
      if ($companyid == 53) { // camera
        data_set($col3, 'remarks.label', 'Reason');
      }
      if ($companyid == 51) { // ulitc
        data_set($col3, 'batch.lookupclass', 'lookupbatchulitc');
        data_set($col3, 'batch.addedparams', ['effectivity']);
        data_set($col3, 'batch.required', true);
        data_set($col3, 'ispickupdate.type', 'input');
        data_set($col3, 'ispickupdate.class', 'csispickupdate sbccsreadonly');
        data_set($col3, 'ispickupdate.label', 'Batch Date Range');
        data_set($col3, 'statrem.label', 'Half day Filling');
        data_set($col3, 'statrem.readonly', true);
        data_set($col3, 'statrem.class', 'csstatrem sbccsreadonly');
        data_set($col3, 'statrem.options',  array(
          ['label' => 'Morning Leave', 'value' => 'Morning Leave'],
          ['label' => 'Afternoon Leave', 'value' => 'Afternoon Leave']
        ));
      }

      $fields = [];
      $col4 = $this->fieldClass->create($fields);
    }



    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function sbcscript($config)
  {
    if ($config['params']['companyid'] == 51) { //ulitc
      return $this->sbcscript->leaveapplicationportal($config);
    } else {
      return true;
    }
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    $hideobj = [];
    if ($config['params']['companyid'] == 53) { //camera
      $hideobj['submit'] = true;
      $hideobj['lblsubmit'] = true;
    }
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['remarks'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['acnoid'] = 0;
    $data[0]['acno'] = '';
    $data[0]['acnoname'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['setupdate'] = $this->othersClass->getCurrentDate();
    $data[0]['status'] = '';
    $data[0]['adays'] = '';
    $data[0]['batch'] = '';
    $data[0]['batchid'] = 0;
    $data[0]['start'] = null;
    $data[0]['effectivity'] = null;
    $data[0]['prdstart'] = '';
    $data[0]['ispickupdate'] = '';
    $data[0]['statrem'] = '';
    $data[0]['prdend'] = '';
    $data[0]['days'] = '0';
    $data[0]['days'] = '0';
    $data[0]['bal'] = '0';
    $data[0]['hours'] = '';

    return $data;
  }

  function getheadqry($trno)
  {
    $now = $this->othersClass->getCurrentTimeStamp();
    return "select s.trno, e.empid, s.trno as clientid, client.client,
        '" . $now . "' as dateid, s.empid,s.dateid as setupdate,
        CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname,
        p.codename as acnoname, s.acnoid, p.`code` as acno,
        if(p.code = 'PT122','',s.days) as days, if(p.code = 'PT122','',s.bal) as bal, s.docno, s.prdstart, s.prdend,
        '' as remarks, null as effectivity,null as start, 'ENTRY' as status, '' as hours,e.divid,'' as batchid,p.uom,'' as ispickupdate,'' as statrem
        from leavesetup as s
        left join employee as e on e.empid=s.empid
        left join paccount as p on p.line=s.acnoid
        left join client on client.clientid=e.empid
        where s.trno=" . $trno;
  }


  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $head = $this->coreFunctions->opentable($this->getheadqry($trno));
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'reloadtableentry' => true];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $udpate)
  {
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $empid = $config['params']['adminid'];
    $msg = '';


    // $curyear = date_format(date_create($this->othersClass->getCurrentDate()), 'Y');
    // $applyyear = date_format(date_create($head['setupdate']), 'Y');
    // if ($applyyear < $curyear) {
    //   return ['status' => false, 'msg' => "For current year leave only are allowed to apply", 'clientid' => $config['params']['head']['trno']];
    // }


    $effectivity = $this->othersClass->sbcdateformat($head['effectivity']);
    $prdstart = $this->othersClass->sbcdateformat($head['prdstart']);
    $prdend = $this->othersClass->sbcdateformat($head['prdend']);
    if ($effectivity >= $prdstart &&  $effectivity <= $prdend) {
    } else {
      return ['status' => false, 'msg' => 'Applied leave is not applicable for this date range of ' . $prdstart . ' to ' . $prdend];
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $head[$key] = $this->othersClass->sanitizekeyfield($key, $head[$key]);
        } //end if
      }
    }
    if ($head['remarks'] == '') {
      return ['status' => false, 'msg' => 'Remarks is Empty', 'clientid' => $config['params']['head']['trno']];
    }

    $entrydays = $this->coreFunctions->getfieldvalue("leavetrans", "sum(adays)", "trno=? and status='E'", [$head['clientid']]);
    $bal = $this->coreFunctions->getfieldvalue("leavesetup", "bal", "trno=?", [$head['clientid']]);
    if ($bal == '') {
      $bal = 0;
    }

    if ($bal == 0) {
      return ['status' => false, 'msg' => 'Unable to apply leave, insufficient balance. Remaining balance: ' . $bal, 'clientid' => $config['params']['head']['trno']];
    }

    $uom = $this->coreFunctions->getfieldvalue("paccount", "uom", "line=?", [$head['acnoid']]);

    if ($companyid == 58) { // cdohris

      if ($head['hours'] == 'Whole day') {
        $head['hours'] = 1;
      } else {
        $head['hours'] = 0.5;
      }
    } else {
      if (strtoupper($uom) == 'DAYS') {
        if ($head['hours'] != 1 && $head['hours'] != 0.5) {
          return ['status' => false, 'msg' => 'Applied ' . $head['hours'] . ' Day must be (1 or 0.5)', 'clientid' => $config['params']['head']['trno']];
        }
      }
    }

    $head['hours'] = trim($head['hours']);
    $entrybal = $head['hours'] + $entrydays;

    if (preg_match('/[a-z]/', strtolower($head['hours']))) { // only letters
      if (strtoupper($uom) == 'DAYS') {
        return ['status' => false, 'msg' => 'Please enter 1 for a full day or 0.5 for a half day.', 'clientid' => $config['params']['head']['trno']];
      } else {
        return ['status' => false, 'msg' => 'Please enter valid hours.', 'clientid' => $config['params']['head']['trno']];
      }
    }

    $numapp = 0;
    if ($entrybal > $bal) {
      if ($entrydays != 0) {
        $numapp = $entrydays;
      }
      return ['status' => false, 'msg' => 'Your leave application cannot be processed due to insufficient leave balance. ' . '<br> Remaining balance: ' . $bal . ' with ' . $numapp . ' currently pending for approval.', 'clientid' => $config['params']['head']['trno']];
    }
    $islatefilling = 0;
    switch ($companyid) {
      case 53: //camera
        $checkcutoffdate = $this->payrollcommon->checkbatchsched($head['effectivity'], $head['divid']);
        if (!empty($checkcutoffdate['msg'])) {
          $msg = $checkcutoffdate['msg'];
          return ['status' => false, 'msg' => $msg];
        }
        break;
      case 58: //cdo
        $head['start'] = date_create($head['start']);
        $head['start'] = date_format($head['start'], 'Y-m-d');

        $checkdate = $this->payrollcommon->checkleave($head, $config);
        if (!empty($checkdate['msg'])) {
          $msg = $checkdate['msg'];
          return ['status' => false, 'msg' => $msg];
        }
        break;
      case 51: //ulitc
        $effectivity = $this->othersClass->sbcdateformat($head['effectivity']);
        $batchdate = $this->coreFunctions->opentable("select date(startdate) as startdate, date(enddate) as enddate from batch where line= " . $head['batchid'] . "");

        if ($effectivity < $batchdate[0]->startdate) {
          $islatefilling = 1;
        }
        if ($effectivity > $batchdate[0]->enddate) {
          return ['status' => false, 'msg' => 'The effectivity date you applied is not within the batch period.'];
        }
        break;
    }

    if ($head['uom'] == 'DAYS') {
      if ($head['hours'] > 1) {
        $msg = "Leave Days must be 1 for a full day or 0.5 for a half day.";
        return ['status' => false, 'msg' => $msg, 'clientid' => $config['params']['head']['trno']];
      }
    }
    $line = $this->coreFunctions->datareader("select ifnull(max(line),0)+1 as value from leavetrans where trno=" . $head['trno']);
    $status = true;
    $data = [];
    $data['trno'] =  $head['clientid'];
    $data['line'] =  $line;
    $data['dateid'] = $head['dateid'];
    $data['effectivity'] = $head['effectivity'];
    $data['status'] = "E";
    $data['status2'] = "E";
    $data['islatefilling'] = $islatefilling;

    $data['adays'] = $head['hours'];
    $data['empid'] = $head['empid'];
    $data['remarks'] = $head['remarks'];

    if ($companyid == 51) { //ulitc
      if ($head['hours'] == .5) {
        $data['fillingtype'] = isset($head['statrem']['value']) ? $head['statrem']['value'] : '';
        if ($data['fillingtype'] == '') {
          return ['status' => false, 'msg' => 'Half Day field cannot be empty', 'clientid' => $config['params']['head']['trno']];
        }
      }
    }

    if ($head['batchid'] != '') {
      $data['batchid'] = $head['batchid'];
    }
    $date = date('Y-m-d', strtotime($data['effectivity']));
    if (!empty($this->checking($config, $date, $head))) {
      $msg = "Already Exist";
      $head['clientid'] = 0;
      $status = false;
    } else {
      if ($companyid == 58) { // cdohris
        $result = $this->generateleave($config, $head, $date, $data, $bal);
        $msg = $result['msg'];
        if (!$result['status']) {
          $status = false;
          goto def;
        }
        goto log;
      }
      $url = 'App\Http\Classes\modules\payrollentry\leaveapplicationportalapproval';
      $appstatus = $this->othersClass->insertUpdatePendingapp($data['trno'], $line, 'LEAVE', $data, $url, $config, 0, true, true);
      if (!$appstatus['status']) {
        $msg = $appstatus['msg'];
        $status = $appstatus['status'];
      } else {
        if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
          $msg = 'Successfully applied';
          if ($companyid == 53 || $companyid == 51) { // camera|ulitc
            $result = $this->stockstatusposted($config, $line, $data['trno']);
            if (!$result['status']) {
              $status = false;
            }
            $msg = $result['msg'];
          }
          log:
          $empname = $this->coreFunctions->datareader("select cl.clientname as value
          from employee as e
          left join client as cl on cl.clientid = e.empid
          where e.empid = ?", [$head['empid']]);

          $this->logger->sbcmasterlog(
            $data['trno'],
            $config,
            "CREATE - NAME: " . $empname . ", DATE: " . date('Y-m-d', strtotime($data['dateid'])) . " EFFECTIVITY: " . $date . "  LEAVE: " . $head['hours'] . ", STATUS: " . $head['status'] . ""
          );
        }
      }
    }


    def:
    return ['status' => $status, 'msg' => $msg, 'clientid' => $config['params']['head']['trno']];
  } // end function

  public function getlastclient($pref)
  {
    return '';
  }

  public function openstock($trno, $config)
  {
    $qry = 'select line, trno, description from jobtdesc where trno=?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVE' and trno=?", 'delete', [$clientid]);
    return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  // -> print function

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  public function stockstatusposted($config, $line, $trno)
  {

    $companyid = $config['params']['companyid'];
    $empid = $config['params']['adminid'];
    $query = "
        select lt.trno, lt.line as line,concat(lt.trno,'~',lt.line) as trline, date(lt.dateid) as dateid, lt.daytype,lt.remarks,
    lt.adays, date(lt.effectivity) as effdate,cl.clientname,ls.bal,ls.days as entitled,lt.empid,
    lt.approvedby_disapprovedby,p.codename,
    date(lt.date_approved_disapproved) as date_approved_disapproved,
    lt.approvedby_disapprovedby2 as approvedby_disapprovedbysup,
    date(lt.date_approved_disapproved2) as date_approved_disapprovedsup,
    lt.disapproved_remarks,lt.disapproved_remarks2,e.approver1,e.approver2,e.supervisorid,lt.fillingtype
    from leavetrans lt
    left join leavesetup as ls on lt.trno = ls.trno
    left join paccount as p on p.line=ls.acnoid
    left join employee as e on e.empid=ls.empid
    left join client as cl on cl.clientid=e.empid
    left join batch as b on b.line=lt.batchid
    where lt.line= $line and lt.trno = $trno ";
    $leavedata = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    $params = [];
    $params['title'] = 'LEAVE APPLICATION';
    $params['clientname'] = $leavedata[0]['clientname'];
    $params['line'] = $leavedata[0]['trline'];
    $params['effdate'] = $leavedata[0]['effdate'];
    $params['dateid'] = $leavedata[0]['dateid'];
    $params['adays'] = $leavedata[0]['adays'];
    $params['bal'] = $leavedata[0]['bal'];
    $params['entitled'] = $leavedata[0]['entitled'];
    $params['codename'] = $leavedata[0]['codename'];
    $params['remarks'] = $leavedata[0]['remarks'];
    $params['reason1'] = $leavedata[0]['disapproved_remarks'];
    $params['reason2'] = $leavedata[0]['disapproved_remarks2'];
    $params['companyid'] = $companyid;
    $params['fillingtype'] = $leavedata[0]['fillingtype'];
    $params['muduletype'] = 'LEAVE';

    // testing pending app
    if ($companyid == 51) { //ulitc
      $msg = 'Applications submitted less than three (3) days before the intended leave date shall be subject to the approval of the HR Manager.';
    } else {
      $msg = 'Successfully applied';
    }

    $qry = "select trno,line,doc,app.clientid,cl.clientname,cl.email as username,emp.email,app.approver from pendingapp as app
  				left join client as cl on cl.clientid = app.clientid
  				left join employee as emp on emp.empid = app.clientid
  				where app.doc ='LEAVE' and app.trno = " . $trno . " and app.line = " . $line . " ";

    $data = $this->coreFunctions->opentable($qry);

    if (empty($data)) {
      $this->coreFunctions->execqry("delete from leavetrans where empid = $empid and line= $line and trno = $trno", 'delete');
      return ['row' => [], 'status' => false, 'msg' => 'Please advice admin to set up approver.', 'backlisting' => true];
    }
    $status = true;
    foreach ($data as $key => $value) {
      if (!empty($data[$key]->email)) {
        $params['approver'] = $data[$key]->username;
        $params['email'] = $data[$key]->email;
        $params['isapp'] = $data[$key]->approver;
        // $res =  $this->linkemail->createLeaveEmail($params);
        $res =  $this->linkemail->weblink($params, $config);
        if (!$res['status']) {
          $msg = $res['msg'];
          $status = false;
        }
      }
    }
    if (!$status) {
      $this->coreFunctions->execqry("delete from pendingapp where doc = 'LEAVE' and trno = $trno and line= $line ", 'delete');
      $this->coreFunctions->execqry("delete from leavetrans where empid = $empid and line= $line and trno = $trno ", 'delete');
    }
    // $data2 = ['empid' => $empid];
    // $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
    // // $this->othersClass->insertPendingapp($trno, $line, 'LEAVE', $data2, $url, $config, 0, true);
    // $status = true;
    // $appstatus = $this->othersClass->insertUpdatePendingapp($trno, $line, 'LEAVE', $data2, $url, $config, 0, true, true);
    // if ($appstatus['status']) {
    //   // sending mail
    //   if ($companyid == 53 || $companyid == 51) {
    //   }
    // } else {
    //   $status = false;
    //   $msg = $appstatus['msg'];
    // }
    $submitdate = $this->othersClass->getCurrentTimeStamp();
    $this->logger->sbcmasterlog($trno, $config, "SUBMIT DATE : " . $submitdate);
    return ['row' => [], 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }
  public function checking($config, $date, $head)
  {
    $empid = $config['params']['adminid'];
    $companyid = $config['params']['companyid'];
    $day = date('l', strtotime($head['effectivity']));
    $dis = ['D'];
    $status = ['A', 'E'];

    $query = "select ifnull(sum(lt.adays),0) + " . $head['hours'] . " as value 
    from $this->stock as lt 
    left join leavesetup as ls on ls.trno = lt.trno
    where lt.empid = $empid and date(effectivity) = '" . $date . "'
    and (lt.status in ('E','A') and lt.status2 in ('E','A'))";
    $adays =  $this->coreFunctions->datareader($query, [], '', true);

    switch ($companyid) {
      case 29: //sbc
        if ($day == 'Saturday') {
          $adays = $adays / 3;
        } else {
          $adays = $adays / 9;
        }
        break;

      default:
        if ($head['uom'] == 'HRS') {
          $adays = $adays / 8;
        } else {
          $adays = $adays / 1;
        }
        break;
    }

    if ($adays <= 1) {
      goto end;
    }
    $data =  $this->coreFunctions->opentable("select status,status2 from $this->stock where empid = $empid and date(effectivity) = '" . $date . "' order by line desc ");
    if (!empty($data)) {
      if (in_array($data[0]->status, $dis) || in_array($data[0]->status2, $dis)) {
        goto end;
      } else {
        if (in_array($data[0]->status, $status) && in_array($data[0]->status2, $status)) {
          return $data;
        }
      }
    }
    end:
    return [];
  }
  public function generateleave($config, $head, $end, $data, $bal)
  {
    $start = $head['start'];
    $apdays = $head['hours'];
    $empid = $config['params']['adminid'];
    $start = new DateTime(date('Y-m-d', strtotime($start)));
    $end = new DateTime($end);
    $status = true;

    if ($apdays > 1) {
      $status = false;
      $msg = "Leave Days must be 1 or 0.5";
      goto end;
    }

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));


    $dates = [];
    foreach ($period as $date) {
      array_push($dates, $date->format('Y-m-d'));
    }
    if ($apdays > $bal) {
      $status = false;
      $msg = "Not enough leave balance";
      goto end;
    }

    $i = 0;
    $trno = 0;
    $msg = "Successfully applied ";
    $lines = '';

    foreach ($dates as $date) {
      if (!empty($this->checking($config, $date, $head))) {
        $msg .= " Already Exist. $date and the other date can't be created.";
        $status = false;
      }
      foreach ($data as $key2 => $value) {
        if (strpos($key2, "effectivity") !== false) {
          $data[$key2] = $date;
        }
        if (strpos($key2, "adays") !== false) {
          $data[$key2] = $apdays;
        }
        if (strpos($key2, "line") !== false) {
          if ($i != 0) {
            $data[$key2] = $value + 1;
            $lines .= ',' . $data[$key2];
          } else {
            $lines .= $value;
          }
        }

        if (strpos($key2, "trno") !== false) {
          $trno = $value;
        }
      }
      if ($status) { // if false no need to insert 
        $url = 'App\Http\Classes\modules\payrollentry\leaveapplicationportalapproval';
        $appstatus = $this->othersClass->insertUpdatePendingapp($trno, $data['line'], 'LEAVE', $data, $url, $config, 0, true, true);
        if (!$appstatus['status']) {
          $status = $appstatus['status'];
          $msg = $appstatus['msg'];
        } else {
          $this->coreFunctions->sbcinsert($this->stock, $data);
          $this->logger->sbcmasterlog($trno, $config, "CREATE" . ", DATE: " . date('Y-m-d') . " EFFECTIVITY: " . $date . "  LEAVE: " . $apdays . ", STATUS: " . 'ENTRY' . "");
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno= ? and empid = ? and line in (" . $lines . ") ", "delete", [$trno, $empid]);
        break;
      }
      $i++;
    }

    end:
    return ['status' => $status, 'msg' => $msg];
  }
} //end class
