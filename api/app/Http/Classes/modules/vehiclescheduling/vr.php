<?php

namespace App\Http\Classes\modules\vehiclescheduling;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class vr
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VEHICLE SCHEDULE REQUEST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'vrhead';
  public $hhead = 'hvrhead';
  public $detail = 'vrstock';
  public $hdetail = 'hvrstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';


  private $fields = [
    'trno', 'docno', 'dateid', 'clientid', 'vehicleid', 'deptid', 'driverid', 'schedin', 'schedout', 'rem'
  ];
  private $except = ['trno'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forrevision', 'label' => 'For Revision', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked/For Approval', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
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
    $this->reporter = new SBCPDF;
    $this->hrislookup = new hrislookup;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2894,
      'edit' => 2895,
      'new' => 2896,
      'save' => 2897,
      'change' => 2898,
      'delete' => 2899,
      'print' => 2900,
      'unpost' => 2902,
      'lock' => 2903,
      'unlock' => 2904,
      'additem' => 2905,
      'edititem' => 2906,
      'deleteitem' => 2907
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $lblstatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $empname = 4;
    $deptname = 5;
    $schedin = 6;
    $schedout = 7;
    $driver = 8;
    $vehicle = 9;
    $rem = 10;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'empname', 'deptname', 'schedin', 'schedout', 'driver', 'vehicle', 'rem'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$listdocument]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$empname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$schedin]['style'] = 'width:90px;whiteSpace: normal;min-width:90px; max-width:90px;';
    $cols[$schedout]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$driver]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$rem]['style'] = 'width:300px;whiteSpace: normal;min-width:100px; max-width:300px;';
    $cols[$listdate]['label'] = 'Date of Travel';
    $cols[$schedin]['label'] = 'Start Time';
    $cols[$schedout]['label'] = 'End Time';
    $cols[$rem]['label'] = 'Logistics Remarks';

    $cols[$rem]['type'] = 'textarea';
    $cols[$rem]['readonly'] = true;
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['h.docno', 'emp.clientname', 'dept.clientname', 'driver.clientname', 'vehicle.clientname'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $status = "stat.status";

    $viewall = $this->othersClass->checkAccess($config['params']['user'], 3590);

    if (!$viewall) {
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$adminid]);
      $condition .= " and (h.deptid='" . $deptid . "' or h.createby='" . $config['params']['user'] . "') ";
    }

    switch ($itemfilter) {
      case 'draft':
        $status = "if(num.statid=0,'DRAFT',stat.status)";
        $condition .= ' and num.postdate is null and num.statid in (0, 13, 14)';
        break;
      case 'forrevision':
        $condition = " and num.postdate is null and num.statid=16";
        break;
      case 'locked':
        $condition .= ' and num.postdate is null and num.statid=10';
        break;
      case 'approved':
        $condition .= ' and num.postdate is null and h.approveddate is not null';
        break;
      case 'posted':
        $condition .= ' and num.postdate is not null ';
        break;
    }
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, " . $status . " as stat, emp.clientname as empname, driver.clientname as driver, vehicle.clientname as vehicle, h.schedin, h.schedout,
    if(num.statid=0 or num.statid=10,'',(select rem from headrem where headrem.trno=h.trno order by line desc limit 1)) as rem, dept.clientname as deptname
    from " . $this->head . " as h  
    left join " . $this->tablenum . " as num on num.trno=h.trno
    left join client as emp on emp.clientid = h.clientid
    left join client as driver on driver.clientid = h.driverid
    left join client as vehicle on vehicle.clientid = h.vehicleid
    left join trxstatus as stat on stat.line=num.statid
    left join client as dept on dept.clientid = h.deptid 
    where num.doc=? and num.center = ? and  
    CONVERT(h.schedin,DATE)>=? and 
    CONVERT(h.schedin,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, " . $status . " as stat, emp.clientname as empname, driver.clientname as driver, vehicle.clientname as vehicle, h.schedin, h.schedout,
    if(num.statid=0 or num.statid=10,'',(select rem from hheadrem where hheadrem.trno=h.trno order by line desc limit 1)) as rem, dept.clientname as deptname
    from " . $this->hhead . " as h  
    left join " . $this->tablenum . " as num on num.trno=h.trno
    left join client as emp on emp.clientid = h.clientid
    left join client as driver on driver.clientid = h.driverid
    left join client as vehicle on vehicle.clientid = h.vehicleid
    left join trxstatus as stat on stat.line=num.statid
    left join client as dept on dept.clientid = h.deptid 
    where num.doc=? and num.center = ? and 
    CONVERT(h.schedin,DATE)>=? and 
    CONVERT(h.schedin,DATE)<=? " . $condition . " " . $filtersearch . "
    order by docno desc";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
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
      'unpost',
      'lock',
      'unlock',
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

    $action = 0;
    $address = 1;
    $contact = 2;
    $schedin = 3;
    $schedout = 4;
    $purpose = 5;
    $passengername = 6;
    $itemdesc = 7;

    $tab = [
      $this->gridname =>
      ['gridcolumns' => ['action', 'address', 'contact', 'schedin', 'schedout', 'purpose', 'passengername', 'itemdesc'], 'checkchanges' => 'tableentry'],
      'passengertab' => ['action' => 'vehiclescheduling', 'lookupclass' => 'tabpassenger', 'label' => 'PASSENGER LIST', 'checkchanges' => 'tableentry'],
      'skilldesctab' => ['action' => 'vehiclescheduling', 'lookupclass' => 'tabitems', 'label' => 'ITEMS / CARGO', 'checkchanges' => 'tableentry'],
      'remtab' => ['action' => 'vehiclescheduling', 'lookupclass' => 'tabrem', 'label' => 'REMARKS', 'checkchanges' => 'tableentry']
    ];

    $stockbuttons = ['save', 'delete', 'addvritems', 'addpassenger'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
    $obj[0][$this->gridname]['columns'][$address]['style'] = "width:300px;whiteSpace: normal;min-width:300px;max-width:300px;";
    $obj[0][$this->gridname]['columns'][$contact]['style'] = "width:200px;whiteSpace: normal;min-width:200px;max-width:200px;";
    $obj[0][$this->gridname]['columns'][$schedin]['style'] = "width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";
    $obj[0][$this->gridname]['columns'][$schedout]['style'] = "width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";
    $obj[0][$this->gridname]['columns'][$purpose]['style'] = "text-align:left;width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";
    $obj[0][$this->gridname]['columns'][$passengername]['style'] = "text-align:left;width:200px;whiteSpace: normal;min-width:200px;max-width:200px;";
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "text-align:left;width:200px;whiteSpace: normal;min-width:200px;max-width:200px;";

    $obj[0][$this->gridname]['columns'][$schedin]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$schedout]['type'] = "time";

    $obj[0][$this->gridname]['columns'][$schedin]['label'] = "Start Time";
    $obj[0][$this->gridname]['columns'][$schedout]['label'] = "End Time";

    $obj[0][$this->gridname]['columns'][$passengername]['label'] = "Passenger";
    $obj[0][$this->gridname]['columns'][$passengername]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][$passengername]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = "Items/Cargo";
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$purpose]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$purpose]['action'] = "lookuppurpose";
    $obj[0][$this->gridname]['columns'][$purpose]['lookupclass'] = "purpose";

    $obj[0][$this->gridname]['columns'][$address]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$address]['action'] = "lookupvraddress";
    $obj[0][$this->gridname]['columns'][$address]['lookupclass'] = "vraddress";

    $obj[0][$this->gridname]['columns'][$contact]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$contact]['action'] = "lookupvrcontact";
    $obj[0][$this->gridname]['columns'][$contact]['lookupclass'] = "vrcontact";

    $obj[0][$this->gridname]['descriptionrow'] = ['', 'clientname', 'Customer Name'];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['label'] = 'CUSTOMER';

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $return = [];
    $tab = [];
    $obj = $this->tabClass->createtab($tab, []);
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addcustomer', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['action'] = 'addcustomer';
    $obj[0]['label'] = 'ADD CUSTOMER';
    $obj[1]['label'] = 'SAVE CUSTOMER';
    $obj[2]['label'] = 'DELETE CUSTOMER';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'deptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'vremployee');
    data_set($col1, 'client.label', 'Employee Code');
    data_set($col1, 'clientname.label', 'Employee Name');
    data_set($col1, 'ddeptname.label', 'Department');

    $fields = ['driver', 'drivername', 'vehicle', 'vehiclename'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'driver.action', 'lookupclient');
    data_set($col2, 'driver.lookupclass', 'driver');
    data_set($col2, 'driver.type', 'input');
    data_set($col2, 'driver.label', 'Driver Code');
    data_set($col2, 'driver.class', 'csdriver sbccsreadonly');
    data_set($col2, 'drivername.class', 'csdrivername sbccsreadonly');
    data_set($col2, 'vehicle.type', 'input');
    data_set($col2, 'vehiclename.class', 'csvehiclename sbccsreadonly');

    $fields = ['dateid', 'schedin', 'schedout'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dateid.label', 'Date of Travel');
    data_set($col3, 'schedin.type', 'datetime');
    data_set($col3, 'schedout.type', 'datetime');
    data_set($col3, 'schedin.label', 'Start Time');
    data_set($col3, 'schedout.label', 'End Time');

    $fields = ['rem', 'lblreceived'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'remarks.type', 'ctextarea');
    data_set($col4, 'lblreceived.label', 'FOR APPROVAL!');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    return $this->resetdata($docno);
  }

  public function resetdata($docno = '')
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['schedin'] = $this->othersClass->getCurrentDate();
    $data[0]['schedout'] = $this->othersClass->getCurrentDate();
    $data[0]['driverid'] = 0;
    $data[0]['driver'] = '';
    $data[0]['drivername'] = '';
    $data[0]['vehicle'] = '';
    $data[0]['vehicleid'] = 0;
    $data[0]['vehiclename'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['client'] = '';
    $data[0]['clientid'] = 0;
    $data[0]['clientname'] = '';
    $data[0]['rem'] = '';
    $data[0]['approvedby'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc='VR' and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);

    $statid = $this->coreFunctions->getfieldvalue($this->tablenum, "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        $islocked = true;
        break;
    }

    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;


    $viewall = $this->othersClass->checkAccess($config['params']['user'], 3590);

    $filterdept = '';
    if (!$viewall) {
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$config['params']['adminid']]);
      $filterdept = " and (head.deptid='" . $deptid . "' or head.createby='" . $config['params']['user'] . "') ";
    }

    $qryselect = "select 
    head.trno, 
    head.docno, 
    head.dateid, 
    head.schedin,
    head.schedout, 
    head.driverid, 
    head.clientid, 
    head.vehicleid, 
    head.deptid, 
    driver.clientname as drivername, 
    driver.client as driver, 
    emp.clientname, 
    emp.client, 
    vehicle.clientname as vehiclename, 
    vehicle.client as vehicle, 
    dept.clientname as deptname, 
    dept.client as dept, 
    head.rem,
    head.approvedby,
    head.status
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join $tablenum as num on num.trno = head.trno 
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.trno = ? and num.doc='VR' and num.center=? " . $filterdept . "
    union all " . $qryselect . " from " . $htable . " as head
    left join $tablenum as num on num.trno = head.trno
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.trno = ? and num.doc='VR' and num.center=? 
    " . $filterdept;

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);

      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      if ($statid == 10) {
        $hideobj = ['lblreceived' => false];
      } else {
        $hideobj = ['lblreceived' => true];
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];

    $statusid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$head['trno']]);
    switch ($statusid) {
      case 0;
      case 13:
      case 14:
      case 16;
        break;
      default:
        $statusname = $this->coreFunctions->getfieldvalue("trxstatus", "status", "line=?", [$statusid]);
        return ['status' => false, 'msg' => 'Cannot modify, status is ' . $statusname];
        break;
    }

    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {

      $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$head['trno']]);
      switch ($statid) {
        case '11':
        case '15':
          return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
          break;

        default:
          $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $head['trno']]);
          break;
      }

      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
    }
  } // end function  

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;

    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
      default:
        $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);
        break;
    }

    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from vritems where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from vrpassenger where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into htraininghead (trno, docno, dateid, title, ttype, venue, tdate1, tdate2,
    speaker, amt, cost, attendees,remarks, createby, createdate, editby, 
    editdate, lockdate, lockuser, viewdate, viewby, doc, reqtrain)
    select trno, docno, dateid, title, ttype, venue, tdate1, tdate2, speaker, amt, cost, attendees, 
    remarks, createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, 
    doc, reqtrain from traininghead where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {

      $qry = "insert into " . $this->hdetail . " (trno, line, empid, empname, notes) select trno, line, empid,empname, notes from " . $this->detail . " where trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
      } else {
        $msg = "Posting failed. Kindly check the detail.";
      }
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = '';

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into traininghead (trno, docno, dateid, title, ttype, venue, tdate1, tdate2, speaker, amt, cost, attendees, remarks, createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc, reqtrain)
    select trno, docno, dateid, title, ttype, venue, tdate1, tdate2, speaker, amt, cost, attendees, remarks, createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc, reqtrain from htraininghead where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {

      $qry = "insert into " . $this->detail . " (trno, line, empid, empname, notes) select trno, line, empid, empname, notes from " . $this->hdetail . " where trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
      } else {
        $msg = "Unposting failed. Kindly check the detail.";
      }
    } else {
      $msg = "Unposting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  private function getstockselect($config)
  {
    $qry = "select '' as bgcolor, i.trno, i.line, c.clientid, c.client client, c.clientname as clientname,
            i.shipid, i.shipcontactid, time(i.schedin) as schedin, time(i.schedout) as schedout, i.purposeid, p.purpose, ship.addr as address,
            concat(contact.salutation,' ',contact.fname,' ',contact.mname,' ',contact.lname) as contact
            ";
    return $qry;
  }

  public function openstock($trno, $config)
  {
    $select = $this->getstockselect($config);
    $qry = $select . ", ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from vrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=i.trno and pass.line=i.line),'') as passengername,
        (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from vritems as v where v.trno=i.trno and v.line=i.line) as itemdesc
        from " . $this->detail . " as i left join client as c on c.clientid=i.clientid
        left join purpose_masterfile as p on p.line = i.purposeid
        left join billingaddr as ship on ship.line = i.shipid
        left join contactperson as contact on contact.line = i.shipcontactid
        where i.trno=?
        union all "
      . $select . ", ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from hvrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=i.trno and pass.line=i.line),'') as passengername,
       (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from hvritems as v where v.trno=i.trno and v.line=i.line) as itemdesc
         from " . $this->hdetail . " as i left join client as c on c.clientid=i.clientid  
        left join purpose_masterfile as p on p.line = i.purposeid
        left join billingaddr as ship on ship.line = i.shipid
        left join contactperson as contact on contact.line = i.shipcontactid
        where i.trno=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = $sqlselect . ", ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from vrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=i.trno and pass.line=i.line),'') as passengername,
        (select group_concat(concat(itemname,' (',round(qty),' ',uom,')') SEPARATOR '\r') as itemdesc from vritems as v where v.trno=i.trno and v.line=i.line) as itemdesc
         from " . $this->detail . " as i left join client as c on c.clientid=i.clientid
        left join purpose_masterfile as p on p.line = i.purposeid
        left join billingaddr as ship on ship.line = i.shipid
        left join contactperson as contact on contact.line = i.shipcontactid
        where i.trno=? and i.line=? order by line";

    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  } // end function

  public function stockstatus($config)
  {

    $lookupclass = $config['params']['action'];
    switch ($lookupclass) {
      case 'addcustomergrid':
        return $this->lookupcallback($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $row = $config['params']['rows'];
    $data = [];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from " . $this->tablenum . " where trno = '$id' and postdate is not null and doc = '$doc'");

    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    $msg = '';

    foreach ($row  as $key2 => $value) {

      $exists = $this->coreFunctions->getfieldvalue($this->detail, "clientid", "trno=? and clientid=?", [$id, $value['clientid']]);
      if ($exists != '') {
        $msg .= " Customer " . $value['clientname'] . " already added. ";
      }

      $config['params']['data']['line'] = 0;
      $config['params']['data']['trno'] = $id;
      $config['params']['data']['clientid'] = $value['clientid'];
      $config['params']['data']['client'] = $value['client'];
      $config['params']['data']['clientname'] = $value['clientname'];
      $config['params']['data']['shipid'] = $value['shipid'];
      $config['params']['data']['address'] = $value['address'];
      $config['params']['data']['shipcontactid'] = $value['shipcontactid'];
      $config['params']['data']['contact'] = $value['contact'];
      $config['params']['data']['schedin'] = NULL;
      $config['params']['data']['schedout'] = NULL;
      $config['params']['data']['purposeid'] = 0;
      $config['params']['data']['bgcolor'] = 'bg-blue-2';
      $return = $this->additem('insert', $config);

      if ($return['status']) {
        array_push($data, $return['data'][0]);
      }
    }

    if ($msg == '') {
      $msg  = "Successfully saved.";
    }
    return ['row' => $data, 'status' => true, 'msg' =>  $msg, 'reloadhead' => true];
  } // end function


  public function additem($action, $config)
  {
    $trno = $config['params']['data']['trno'];

    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
    }

    $line = $config['params']['data']['line'];
    $clientid = $config['params']['data']['clientid'];
    $clientname = $config['params']['data']['clientname'];
    $client = $config['params']['data']['client'];
    $address = $config['params']['data']['address'];
    $shipid = $config['params']['data']['shipid'];
    $shipcontactid = $config['params']['data']['shipcontactid'];
    $schedin = $config['params']['data']['schedin'];
    $schedout = $config['params']['data']['schedout'];
    $purposeid = $config['params']['data']['purposeid'];

    $data = [
      'trno' => $trno,
      'line' => $line,
      'clientid' => $clientid,
      'shipid' => $shipid,
      'shipcontactid' => $shipcontactid,
      'schedin' => $schedin,
      'schedout' => $schedout,
      'purposeid' => $purposeid,
    ];

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->detail, $data)) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        $this->logger->sbcwritelog($trno, $config, 'VSTOCK', 'ADD - Line:' . $line . ' CUSTOMER : ' . $clientname);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } elseif ($action == 'update') {
      return $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $data['line']]);
    }
  } // end function


  public function deleteitem($config)
  {

    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$config['params']['row']['trno']]);
    switch ($statid) {
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
      default:
        $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $config['params']['row']['trno']]);
        break;
    }

    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    if ($this->coreFunctions->execqry($qry, 'delete', [$trno, $line])) {
      $qry = "delete from vritems where trno=? and line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

      $qry = "delete from vrpassenger where trno=? and line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line : ' . $line . ' CUSTOMER:' . $data[0]->clientname);
    return ['status' => true, 'msg' => 'Successfully deleted customer.', 'reloadhead' => true];
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];

    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
      default:
        $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);
        break;
    }

    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => [], 'reloadhead' => true];
  }


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];

    if ($config['params']['line'] != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'reloadhead' => true];
    } else {
      $data = $this->additem('insert', $config);
      if ($data['status'] == true) {
        return ['row' => $data['data'], 'status' => true, 'msg' => 'Successfully saved.', 'reloadhead' => true];
      } else {
        return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
      }
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      if ($value['line'] != 0) {
        $this->additem('update', $config);
      } else {
        $this->additem('insert', $config);
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';

    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'reloadhead' => true];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  // report startto
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
}
