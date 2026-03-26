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

use Carbon\Carbon;

class vl
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LOGISTICS';
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
    'trno', 'docno', 'dateid', 'clientid', 'vehicleid', 'deptid', 'driverid', 'schedin', 'schedout', 'rem', 'status'
  ];
  private $except = ['trno'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = false;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Without vehicle', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'With vehicle', 'color' => 'primary'],
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
      'view' => 2930,
      'edit' => 2931,
      'save' => 2933,
      'change' => 2934,
      'print' => 2936,
      'post' => 2937,
      'unpost' => 2938,
      'additem' => 2941,
      'edititem' => 2942,
      'deleteitem' => 2943
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $lblstatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $schedin = 4;
    $schedout = 5;
    $empname = 6;
    $driver = 7;
    $vehicle = 8;
    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'schedin', 'schedout', 'empname', 'deptname', 'driver', 'vehicle'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$listdate]['label'] = 'Date of Travel';


    $cols[$schedin]['label'] = 'Start Time';
    $cols[$schedout]['label'] = 'End Time';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
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


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and h.vehicleid=0';
        break;
      case 'approved':
        $condition = ' and num.postdate is null and h.vehicleid<>0';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, stat.status as stat, emp.clientname as empname, driver.clientname as driver, vehicle.clientname as vehicle, h.schedin, h.schedout, dept.clientname as deptname
    from " . $this->head . " as h  
    left join " . $this->tablenum . " as num on num.trno=h.trno
    left join client as emp on emp.clientid = h.clientid
    left join client as driver on driver.clientid = h.driverid
    left join client as vehicle on vehicle.clientid = h.vehicleid
    left join client as dept on dept.clientid = h.deptid 
    left join trxstatus as stat on stat.line=num.statid
    where num.doc='VR' and num.center = ? and h.approveddate is not null and
    CONVERT(h.schedin,DATE)>=? and 
    CONVERT(h.schedin,DATE)<=? " . $condition . "  " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, stat.status as stat, emp.clientname as empname, driver.clientname as driver, vehicle.clientname as vehicle, h.schedin, h.schedout, dept.clientname as deptname
    from " . $this->hhead . " as h  
    left join " . $this->tablenum . " as num on num.trno=h.trno
    left join client as emp on emp.clientid = h.clientid
    left join client as driver on driver.clientid = h.driverid
    left join client as vehicle on vehicle.clientid = h.vehicleid
    left join client as dept on dept.clientid = h.deptid 
    left join trxstatus as stat on stat.line=num.statid
    where num.doc='VR' and num.center = ? and h.approveddate is not null and 
    CONVERT(h.schedin,DATE)>=? and 
    CONVERT(h.schedin,DATE)<=? " . $condition . "  " . $filtersearch . "
    order by docno desc";
    $data = $this->coreFunctions->opentable($qry, [$center, $date1, $date2, $center, $date1, $date2]);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'save',
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

    $action = 0;
    $address = 1;
    $contact = 2;
    $schedin = 3;
    $schedout = 4;
    $purpose = 5;
    $passengername = 6;
    $itemdesc = 7;

    $tab = [
      $this->gridname => ['gridcolumns' => ['action', 'address', 'contact', 'schedin', 'schedout', 'purpose', 'passengername', 'itemdesc']],
      'passengertab' => ['action' => 'vehiclescheduling', 'lookupclass' => 'tabpassenger', 'label' => 'PASSENGER LIST'],
      'skilldesctab' => ['action' => 'vehiclescheduling', 'lookupclass' => 'tabitems', 'label' => 'ITEMS / CARGO'],
      'remtab' => ['action' => 'vehiclescheduling', 'lookupclass' => 'tabrem', 'label' => 'REMARKS'],
    ];

    $stockbuttons = ['addvritems', 'addpassenger'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$address]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][$contact]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$schedin]['style'] = "width:175px;whiteSpace: normal;min-width:175px;";
    $obj[0][$this->gridname]['columns'][$schedout]['style'] = "width:175px;whiteSpace: normal;min-width:175px;";
    $obj[0][$this->gridname]['columns'][$purpose]['style'] = "text-align:left;width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$passengername]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";

    $obj[0][$this->gridname]['columns'][$schedin]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$schedout]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$passengername]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][$address]['type'] = "textarea";

    $obj[0][$this->gridname]['columns'][$schedin]['label'] = "Start Time";
    $obj[0][$this->gridname]['columns'][$schedout]['label'] = "End Time";

    $obj[0][$this->gridname]['columns'][$address]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$contact]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$schedin]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$schedout]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$purpose]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$passengername]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = true;

    $obj[0][$this->gridname]['descriptionrow'] = ['', 'clientname', 'Customer '];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['label'] = 'CUSTOMER';

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $return = [];
    return $return;
  }


  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'deptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.label', 'Employee Code');
    data_set($col1, 'clientname.label', 'Employee Name');
    data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col1, 'docno.class', 'csdocno sbccsreadonly');
    data_set($col1, 'ddeptname.label', 'Department');
    data_set($col1, 'docno.type', 'input');


    $fields = ['driver', 'drivername', 'vehicle', 'vehiclename'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'driver.action', 'lookupclient');
    data_set($col2, 'driver.lookupclass', 'driver');
    data_set($col2, 'driver.type', 'lookup');
    data_set($col2, 'driver.label', 'Driver Assign');
    data_set($col2, 'vehicle.label', 'Vehicle Assign');
    data_set($col2, 'driver.class', 'csdriver sbccsreadonly');
    data_set($col2, 'drivername.class', 'csdrivername sbccsreadonly');
    data_set($col2, 'vehiclename.class', 'csvehiclename sbccsreadonly');
    data_set($col2, 'driver.required', true);
    data_set($col2, 'vehicle.required', true);

    $fields = ['dateid', 'schedin', 'schedout', 'status'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dateid.class', 'csdateid sbccsreadonly');
    data_set($col3, 'dateid.label', 'Date of Travel');
    data_set($col3, 'schedin.class', 'csschedin sbccsreadonly');
    data_set($col3, 'schedout.class', 'csschedout sbccsreadonly');
    data_set($col3, 'schedin.type', 'datetime');
    data_set($col3, 'schedout.type', 'datetime');
    data_set($col3, 'schedin.label', 'Start Time');
    data_set($col3, 'schedout.label', 'End Time');

    data_set($col3, 'status.type', 'lookup');
    data_set($col3, 'status.lookupclass', 'lookup_vrstatus');
    data_set($col3, 'status.action', 'lookuprandom');

    $fields = ['rem', ['rescedule', 'forrevision']];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'remarks.type', 'ctextarea');
    data_set($col4, 'rescedule.lookupclass', 'updateremreschedule');
    data_set($col4, 'rescedule.action', 'customformdialog');

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
    $data[0]['status'] = '';
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
        $trno = $this->coreFunctions->datareader("

          select trno as value from " . $this->tablenum . " 
          where doc='VR' and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

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
    if(head.status = 'APPROVED', '', head.status) as status,
    head.approvedby
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join $tablenum as num on num.trno = head.trno 
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.trno = ? and num.doc='VR' and num.center=? and head.approveddate is not null
    union all " . $qryselect . " from " . $htable . " as head
    left join $tablenum as num on num.trno = head.trno
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.trno = ? and num.doc='VR' and num.center=? and head.approveddate is not null";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
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
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);

      if ($head['vehicleid'] != 0) {
        $this->coreFunctions->sbcupdate("transnum", ['statid' => 15], ['trno' => $head['trno']]);
        $this->updatevehiclestatus($head);
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
    }
  } // end function  

  public function updatevehiclestatus($head, $cancel = false, $trno = 0, $vehicleid = 0, $post = false)
  {
    $schedin = '';
    $sign = "+";

    $posttime = '';
    if ($cancel) {
      $sign = "-";
      $head['vehicleid'] = $vehicleid;
      $temp_schedin = $this->coreFunctions->datareader("select ifnull(schedin,'') as value from " . $this->head . " where trno=?", [$trno]);
      $schedin = Carbon::parse($temp_schedin);

      $temp_schedout = $this->coreFunctions->datareader("select ifnull(schedout,'') as value from " . $this->head . " where trno=?", [$trno]);
      $schedout = Carbon::parse($temp_schedout);

      if ($post) {
        $posttime = Carbon::parse($this->othersClass->getCurrentTimeStamp());
      }
      goto updatehere;
    }

    $schedin = Carbon::parse($head['schedin']);
    $schedout = Carbon::parse($head['schedout']);

    $assigndriver = $this->coreFunctions->datareader("select ifnull(assigndriver,'') as value from " . $this->head . " where trno=?", [$head['trno']]);
    if ($assigndriver === '') {
      updatehere:
      $dateonly = $schedin->format('Y-m-d') . ' 12:00:00';
      $day = strtolower(substr($schedin->format('l'), 0, 3));

      //am sched
      if ($schedin < $dateonly) {
        if ($post) {
          if ($posttime < $schedout && $posttime > $dateonly) {
            goto updatepmhere;
          }
        }
        $this->update_use_ampm($day, $head['vehicleid'], $schedin->format('Y-m-d'), $sign, "am");

        // checking if scheduleout is for wholeday
        if ($schedout >= $dateonly) {
          $this->update_use_ampm($day, $head['vehicleid'], $schedin->format('Y-m-d'), $sign, "pm");
        }

        updatenextdayhere:
        // if scheduleout is extended on the other day
        if ($schedout->format('Y-m-d') > $schedin->format('Y-m-d')) {
          $day = strtolower(substr($schedout->format('l'), 0, 3));
          $this->update_use_ampm($day, $head['vehicleid'], $schedout->format('Y-m-d'), $sign, "am");

          $dateonly = $schedout->format('Y-m-d') . ' 12:00:00';
          if ($schedout > $dateonly) {
            $this->update_use_ampm($day, $head['vehicleid'], $schedout->format('Y-m-d'), $sign, "pm");
          }
        }
      } else {
        updatepmhere:
        if ($post) {
          if ($posttime > $schedout) {
            if ($schedout->format('Y-m-d') > $schedin->format('Y-m-d')) {
              goto updatenextdayhere;
            }
          }
        }
        $this->update_use_ampm($day, $head['vehicleid'], $schedin->format('Y-m-d'), $sign, "pm");
        goto updatenextdayhere;
      }
      if (!$cancel) {
        $this->coreFunctions->sbcupdate($this->head, ['assigndriver' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $head['trno']]);
      }
    }
  }

  private function update_use_ampm($day, $vehicle, $date, $sign, $t)
  {
    $this->coreFunctions->LogConsole($date . ' ' . $day . "_" . $t . ' ' . $sign);
    $isexist = $this->coreFunctions->datareader("select is" . $day . "_" . $t . " as value from daysched where clientid=?", [$vehicle]);
    if ($isexist == '') {
      $isexist = 0;
    }
    if ($isexist) {
      $this->coreFunctions->LogConsole($date . ' update ' . $t . ' ' . $sign);
      $this->coreFunctions->execqry("update vehiclesched set " . $t . "used=" . $t . "used" . $sign . "1 where dateid='" . $date . "'");
    }
  }

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $status = strtoupper($this->coreFunctions->getfieldvalue($this->head, 'status', 'trno=?', [$trno]));

    if ($status != 'DONE' && $status != 'CANCEL') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. The status should be done/cancel.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into hvrhead (trno, doc, docno, clientid, deptid, driverid, vehicleid, dateid,
    schedin, schedout, rem, status, createby, createdate, editby, 
    editdate, lockdate, lockuser, viewdate, viewby, approvedby, approveddate)
    select trno, doc, docno, clientid, deptid, driverid, vehicleid, dateid,
    schedin, schedout, rem, status, createby, createdate, editby, 
    editdate, lockdate, lockuser, viewdate, viewby, approvedby, approveddate 
    from vrhead where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {

      $qry = "insert into " . $this->hdetail . " (trno, line, clientid, schedin, schedout, purposeid, shipid, shipcontactid, createdate, createby, editby, editdate) 
      select trno, line, clientid, schedin, schedout, purposeid, shipid, shipcontactid, createdate, createby, editby, editdate
      from " . $this->detail . " where trno=?";

      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
        $qry = "insert into hvritems (pline, trno, line, itemid, uom, qty, editdate, editby, itemname) 
          select pline, trno, line, itemid, uom, qty, editdate, editby, itemname
          from vritems where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($result) {
          $qry = "insert into hvrpassenger (pline, trno, line, passengerid, editdate, editby, dropoff) 
          select pline, trno, line, passengerid, editdate, editby, dropoff
          from vrpassenger where trno=?";
          $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
          if ($result) {

            $qry = "insert into hheadrem(line, trno, rem, createby, createdate, remtype) 
            select line, trno, rem, createby, createdate, remtype from headrem where trno=?";
            $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($result) {
              $vehicleid = $this->coreFunctions->getfieldvalue('vrhead', 'vehicleid', 'trno=?', [$trno]);
              $this->updatevehiclestatus(null, true, $trno,  $vehicleid, true);
            } else {
              $msg = "Posting failed. Kindly check the remarks details.";
            }
          } else {
            $msg = "Posting failed. Kindly check the passenger details.";
          }
        } else {
          $msg = "Posting failed. Kindly check the customer items.";
        }
      } else {
        $msg = "Posting failed. Kindly check the detail.";
      }
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user, 'statid' => 12];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from vrhead where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from vrstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from vritems where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from vrpassenger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from headrem where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from hvrhead where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvrstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvritems where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvrpassenger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hheadrem where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into vrhead (trno, doc, docno, clientid, deptid, driverid, vehicleid, dateid,
    schedin, schedout, rem, status, createby, createdate, editby, 
    editdate, lockdate, lockuser, viewdate, viewby, approvedby, approveddate)
    select trno, doc, docno, clientid, deptid, driverid, vehicleid, dateid,
    schedin, schedout, rem, status, createby, createdate, editby, 
    editdate, lockdate, lockuser, viewdate, viewby, approvedby, approveddate 
    from hvrhead where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {

      $qry = "insert into " . $this->detail . " (trno, line, clientid, schedin, schedout, purposeid, shipid, shipcontactid, createdate, createby, editby, editdate) 
      select trno, line, clientid, schedin, schedout, purposeid, shipid, shipcontactid, createdate, createby, editby, editdate
      from " . $this->hdetail . " where trno=?";

      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
        $qry = "insert into vritems (pline, trno, line, itemid, uom, qty, editdate, editby, itemname) 
          select pline, trno, line, itemid, uom, qty, editdate, editby, itemname
          from hvritems where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($result) {
          $qry = "insert into vrpassenger (pline, trno, line, passengerid, editdate, editby, dropoff) 
          select pline, trno, line, passengerid, editdate, editby, dropoff
          from hvrpassenger where trno=?";
          $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
          if ($result) {
            $qry = "insert into headrem(line, trno, rem, createby, createdate, remtype) 
            select line, trno, rem, createby, createdate, remtype from hheadrem where trno=?";
            $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($result) {
            } else {
              $msg = "Unposting failed. Kindly check the passenger detail.";
            }
          } else {
            $msg = "Unposting failed. Kindly check the passenger detail.";
          }
        } else {
          $msg = "Unposting failed. Kindly check the customer items.";
        }
      } else {
        $msg = "Unposting failed. Kindly check the detail.";
      }
    } else {
      $msg = "Unposting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $data = ['postdate' => null, 'postedby' => '', 'statid' => 15];
      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from hvrhead where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvrstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvritems where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvrpassenger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hheadrem where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from vrhead where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from vrstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from vritems where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from vrpassenger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from headrem where trno=?", "delete", [$trno]);
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

    $qry = $select . ", ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from vrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=i.trno and pass.line=i.line),'') as passengername,
        (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from vritems as v where v.trno=i.trno and v.line=i.line) as itemdesc
        from " . $this->detail . " as i left join client as c on c.clientid=i.clientid
        left join purpose_masterfile as p on p.line = i.purposeid
        left join billingaddr as ship on ship.line = i.shipid
        left join contactperson as contact on contact.line = i.shipcontactid
        where i.trno=? and i.line = ?
        union all "
      . $select . ", ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from hvrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=i.trno and pass.line=i.line),'') as passengername,
       (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from hvritems as v where v.trno=i.trno and v.line=i.line) as itemdesc
        from " . $this->hdetail . " as i left join client as c on c.clientid=i.clientid  
        left join purpose_masterfile as p on p.line = i.purposeid
        left join billingaddr as ship on ship.line = i.shipid
        left join contactperson as contact on contact.line = i.shipcontactid
        where i.trno=? and i.line = ? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
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

  private function selecthead($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("

        select trno as value from " . $this->tablenum . " 
        where doc='VR' and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

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
    if(head.status = 'APPROVED', '', head.status) as status,
    head.approvedby
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join $tablenum as num on num.trno = head.trno 
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.trno = ? and num.doc='VR' and num.center=? and approveddate is not null
    union all " . $qryselect . " from " . $htable . " as head
    left join $tablenum as num on num.trno = head.trno
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.trno = ? and num.doc='VR' and num.center=?
    and approveddate is not null

     ";

    return $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
  }

  public function cancelrequest($config)
  {
    $trno = $config['params']['trno'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      return ['status' => false, 'msg' => "Already Posted!"];
    }
    $vehicleid = $this->coreFunctions->getfieldvalue('vrhead', 'vehicleid', 'trno=?', [$trno]);

    $rescedule = [
      'approvedby' => '',
      'approveddate' => null,
      'status' => '',
      'driverid' => 0,
      'vehicleid' => 0,
      'assigndriver' => null
    ];

    $statid = 0;
    $statdesc = '';
    switch ($config['params']['canceltype']) {
      case 'rescedule':
        $statid = 13;
        $statdesc = 'RESCHEDULE';
        break;
      case 'forrevision':
        $statid = 16;
        $statdesc = 'REVISION';
        break;
    }


    $this->coreFunctions->sbcupdate($this->head, $rescedule, ['trno' => $trno]);
    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => $statid], ['trno' => $trno]);
    $this->updatevehiclestatus(null, true, $trno, $vehicleid);
    $this->logger->sbcwritelog($trno, $config, 'HEAD', $statdesc);
    $msg = 'Rescheduled successfully.';
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

    foreach ($row  as $key2 => $value) {
      $config['params']['data']['line'] = 0;
      $config['params']['data']['trno'] = $id;
      $config['params']['data']['clientid'] = $value['clientid'];
      $config['params']['data']['client'] = $value['client'];
      $config['params']['data']['clientname'] = $value['clientname'];
      $config['params']['data']['bgcolor'] = 'bg-blue-2';
      $return = $this->additem('insert', $config);

      if ($return['status']) {
        array_push($data, $return['data'][0]);
      }
    }

    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } // end function


  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $clientid = $config['params']['data']['clientid'];
    $clientname = $config['params']['data']['clientname'];
    $client = $config['params']['data']['client'];

    $data = [
      'trno' => $trno,
      'line' => $line,
      'clientid' => $clientid,
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

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
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' CUSTOMER:' . $data[0]->clientname);
    return ['status' => true, 'msg' => 'Successfully deleted customer.'];
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];

    if ($config['params']['line'] != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      $data = $this->additem('insert', $config);
      if ($data['status'] == true) {
        return ['row' => $data['data'], 'status' => true, 'msg' => 'Successfully saved.'];
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
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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
