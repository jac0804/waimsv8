<?php

namespace App\Http\Classes\modules\payroll;

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

class leaveapplication
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
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $stock = 'leavetrans';
  public $tablelogs = 'payroll_log';
  public $prefix = '';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'dateid',
    'status',
    'adays',
    'remarks',
    'effectivity',
    'empid'
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
    ['val' => 'posted', 'label' => 'Without Balance', 'color' => 'primary']
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
      'view' => 1595,
      // 'new' => 1593,
      'save' => 1591,
      // 'delete' => 1594,
      'print' => 1592,
      'edit' => 1596,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $dateid = 1;
    $empname = 2;
    $codename = 3;
    $days = 4;
    $bal = 5;

    $getcols = ['action', 'dateid', 'empname', 'codename', 'days', 'bal'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$empname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$codename]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$days]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; ';
    $cols[$bal]['style'] = 'width:250px;whiteSpace: normal;min-width:250px; ';

    $cols[$dateid]['align'] = 'text-left';
    $cols[$codename]['label'] = 'Leave Type';
    $cols[$days]['label'] = 'Hour(s)';
    $cols[$days]['align'] = 'text-left';
    $cols[$bal]['align'] = 'text-left';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $emplvl = $this->othersClass->checksecuritylevel($config, true);

    $filteroption = '';
    $option = $config['params']['itemfilter'];
    if ($option == 'draft') {
      $filteroption = " and s.bal<>0";
    } else {
      $filteroption = " and s.bal=0";
    }
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['e.emplast', 'e.empfirst', 'e.empmiddle', 'p.codename'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }


    $qry = "
      select CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname, p.codename, s.days, s.bal, s.trno as clientid, 
      date(s.dateid) as dateid, s.leavebatch, s.empid
      from leavesetup as s 
      left join employee as e on e.empid=s.empid
      left join paccount as p on p.line=s.acnoid 
      where e.level in " . $emplvl . " " . $filteroption . " " . $filtersearch . "
      order by CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle), p.codename";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      // 'new',
      'save',
      'delete',
      'cancel',
      'print',
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

  public function createHeadField($config)
  {

    $leavelbl = $this->companysetup->getleavelabel($config['params']);

    $fields = ['client', 'empname', ['acno', 'acnoname']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupleavetransemployee');

    data_set($col1, 'acno.type', 'input');
    data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');

    $fields = ['docno', ['start', 'end'], ['days', 'bal']];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'docno.type', 'input');
    data_set($col2, 'docno.class', 'csdocno sbccsreadonly');

    data_set($col2, 'start.name', 'prdstart');
    data_set($col2, 'start.label', 'Period From');
    data_set($col2, 'start.class', 'csprdstart sbccsreadonly');

    data_set($col2, 'end.name', 'prdend');
    data_set($col2, 'end.label', 'Period To');
    data_set($col2, 'end.class', 'csprdend sbccsreadonly');

    data_set($col2, 'days.label', 'Entitled (' . $leavelbl . ')');
    data_set($col2, 'days.name', 'days');
    data_set($col2, 'days.class', 'csdays sbccsreadonly');

    data_set($col2, 'bal.label', 'Remaining (' . $leavelbl . ')');
    data_set($col2, 'bal.name', 'bal');
    data_set($col2, 'bal.class', 'csbal sbccsreadonly');

    $fields = [['dateid', 'end'], ['hours', 'empstat']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'hours.label', 'Leave ' . $leavelbl);
    data_set($col3, 'hours.type', 'cinput');
    data_set($col3, 'hours.required', true);
    data_set($col3, 'hours.maxlength', 2);

    data_set($col3, 'dateid.label', 'Date Created');

    data_set($col3, 'end.name', 'effectivity');
    data_set($col3, 'end.label', 'Effectivity of Leave');
    data_set($col3, 'end.required', true);

    data_set($col3, 'empstat.action', 'lookupleavestatus');
    data_set($col3, 'empstat.lookupclass', 'lookupleavestatus');
    data_set($col3, 'empstat.name', 'status');
    data_set($col3, 'empstat.required', true);

    $fields = ['remarks'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'remarks.type', 'ctextarea');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
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
    $data[0]['status'] = '';
    $data[0]['adays'] = '';
    $data[0]['batch'] = '';
    $data[0]['effectivity'] = null;
    $data[0]['prdstart'] = '';
    $data[0]['prdend'] = '';
    $data[0]['days'] = '0';
    $data[0]['days'] = '0';
    $data[0]['bal'] = '0';
    $data[0]['hours'] = '';

    return $data;
  }

  function getheadqry($trno)
  {
    return "select s.trno, e.empid, s.trno as clientid, client.client, date(now()) as dateid, s.empid, CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname, 
        p.codename as acnoname, s.acnoid, p.`code` as acno, s.days, s.bal, s.docno, s.prdstart, s.prdend, '' as remarks, null as effectivity, '' as `status`, '' as hours
        from leavesetup as s 
        left join employee as e on e.empid=s.empid 
        left join paccount as p on p.line=s.acnoid
        left join client on client.clientid=e.empid
        where s.trno=" . $trno;
  }


  public function loadheaddata($config)
  {
    if (isset($config['params']['trno'])) {
      $trno = $config['params']['trno'];
    } else {
      $trno = $config['params']['clientid'];
    }

    $head = $this->coreFunctions->opentable($this->getheadqry($trno));
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $trno, 'reloadtableentry' => true];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $udpate)
  {
    $head = $config['params']['head'];
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $head[$key] = $this->othersClass->sanitizekeyfield($key, $head[$key]);
        } //end if 
      }
    }

    $line = $this->coreFunctions->datareader("select ifnull(max(line),0)+1 as value from leavetrans where trno=" . $head['trno']);

    $data = [];
    $data['trno'] =  $head['clientid'];
    $data['line'] =  $line;
    $data['dateid'] = $head['dateid'];
    $data['effectivity'] = $head['effectivity'];
    $data['status'] = substr($head['status'], 0, 1);
    $data['adays'] = $head['hours'];
    $data['empid'] = $head['empid'];
    $data['remarks'] = $head['remarks'];


    $bal = $this->coreFunctions->datareader("select bal as value from leavesetup where trno=" . $head['trno']);

    if ($head['hours'] <= $bal) {
      if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
        if (substr($head['status'], 0, 1) == 'A') {
          if ($head['hours'] != "") {
            $this->coreFunctions->execqry("update leavesetup set bal=bal-'" . $head['hours'] . "' where trno =?", 'update', [$head['clientid']]);
          }
        }

        $this->logger->sbcmasterlog(
          $head['trno'],
          $config,
          "CREATE - NAME: " . $head['empname'] . ", LEAVE: " . $head['hours'] . ", STATUS: " . $head['status'] . ", BALANCE: " . $head['bal']
        );
      }

      return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $config['params']['head']['trno']];
    } else {
      return ['status' => false, 'msg' => 'You only have ' . $bal . ' hour/s.'];
    }
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
    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
    return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
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

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }


} //end class
