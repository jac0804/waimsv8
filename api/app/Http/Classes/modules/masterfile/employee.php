<?php

namespace App\Http\Classes\modules\masterfile;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class employee
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EMPLOYEE LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'EM';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "isemployee";
  private $reportheader;

  private $fields = [
    'client',
    'clientname',
    'addr',
    'start',
    'tel2',
    'contact',
    'rem',
    'type',
    'iscustomer',
    'issupplier',
    'isagent',
    'iswarehouse',
    'isinactive',
    'isemployee',
    'isdepartment',
    'picture',
    'isadmin',
    'uv_ischecker',
    'uv_ispicker',
    'deptid',
    'position',
    'empid',
    'salesgroupid',
    'branchid',
    'wh',
    'isdriver',
    'ispassenger',
    'tin',
    'dropoffwh',
    'deptcode',
    'customerid'
  ];

  private $otherfields = ['isapprover', 'idbarcode'];

  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isemployee', 'isdepartment', 'isadmin', 'uv_ischecker', 'uv_ispicker', 'isapprover', 'isdriver', 'ispassenger'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;


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
    $this->reportheader = new reportheader;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 869,
      'edit' => 870,
      'new' => 871,
      'save' => 872,
      'change' => 873,
      'delete' => 874,
      'print' => 875,
      'load' => 868
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $listclient = 1;
    $listclientname = 2;
    $listaddr = 3;
    $deptname = 4;
    $warehouse = 5;
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    if ($config['params']['companyid'] == 16) { //ati
      array_push($getcols, 'deptname', 'warehouse');
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    if ($config['params']['companyid'] == 16) { //ati
      $cols[$warehouse]['label'] = 'Location';
    }
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $company = $config['params']['companyid'];
    $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
    $search = $config['params']['search'];
    $condition = "";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr,ifnull(dept.clientname,'') as deptname, ifnull(wh.clientname,'') as warehouse
    from client left join client as dept on client.deptid = dept.clientid left join client as wh on wh.client = client.wh 
    where client.isemployee=1 " . $condition . " " . $filtersearch . " order by client.client " . $limit;

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
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    switch ($config['params']['companyid']) {
      case 3: //conti
        $this->showcreatebtn = false;
        $btns = array(
          'load',
          'cancel',
          'print',
          'logs',
          'backlisting',
          'toggleup',
          'toggledown'
        );
        break;
    }

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'employee', 'title' => 'EMPLOYEE_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($access, $config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $ar = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewar']];
    $ap = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewap']];
    if ($systemtype == "FAMS" || $systemtype == "ATI") {
      $inv = ['customform' => ['action' => 'customform', 'lookupclass' => 'inventoryhistory_employee_tab']];
    } else {
      $inv = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewcustomerinv']];
    }
    $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryitemgroup', 'label' => 'itemgroup']];
    $itemgroup = $this->tabClass->createtab($tab, []);


    $return = [];
    $return['ACCOUNT RECEIVABLE HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $ar];
    $return['ACCOUNT PAYABLE HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $ap];


    $return['INVENTORY HISTORY'] = ['icon' => 'fa fa-envelope', 'customform' => $inv];
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti & afti usd
      $return['ITEM GROUP'] = ['icon' => 'fa fa-object-group', 'tab' => $itemgroup];
    }

    if ($systemtype == 'VSCHED' || $systemtype == 'ATI') {
      $return = [];
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryempassets', 'label' => 'ASSETS']];
      $tab_issueitem = $this->tabClass->createtab($tab, []);
      $return['ISSUED ITEMS'] = ['icon' => 'fa fa-history', 'tab' => $tab_issueitem];
    }

    $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];

    return $return;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['client', 'clientname', 'addr', 'start'];
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $fields = ['client', 'clientname', 'addr', 'tin', 'start', 'tel2', 'deptname', 'branchname'];
    }

    if ($systemtype == "ATI") {
      array_push($fields, 'dropoffwhemp', 'whname');
    }

    if ($systemtype == "VSCHED" || $systemtype == "ATI" || $systemtype == "FAMS") {
      array_push($fields, 'deptname', 'idbarcode');
    }

    if ($this->companysetup->isshowdept($config['params'])) {
      array_push($fields, 'deptname');
    }

    if ($companyid == 0) { //standard
      array_push($fields, 'dcustomer');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Employee Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'lookupclientemployee');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'clientname.type', 'cinput');

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      data_set($col1, 'deptname.type', 'lookup');
      data_set($col1, 'deptname.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptname.action', 'lookupclient');
      data_set($col1, 'branchname.class', 'csbranchname sbccsreadonly');
      data_set($col1, 'branchname.lookupclass', 'hbranch');
      data_set($col1, 'branchname.style', '');
      data_set($col1, 'branchname.required', true);
      data_set($col1, 'deptname.required', true);
    }

    if ($systemtype == "VSCHED" || $systemtype == "ATI" || $systemtype == "FAMS" || $this->companysetup->isshowdept($config['params'])) {
      data_set($col1, 'deptname.type', 'lookup');
      data_set($col1, 'deptname.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptname.action', 'lookupclient');
    }

    if ($systemtype == "FAMS" || $systemtype == "ATI") {
      data_set($col1, 'whname.label', 'Location/Warehouse');
      data_set($col1, 'whname.type', 'lookup');
      data_set($col1, 'whname.lookupclass', 'wh');
      data_set($col1, 'whname.action', 'lookupclient');
      data_set($col1, 'idbarcode.label', 'Employee No');
    }

    $fields = ['contact', 'tel2', 'rem', 'type'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['position', 'salesgroup', 'empcode', 'empname', 'rem',];
        break;
      case 16: //ati
        array_push($fields, 'deptcode');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);
    data_set($col2, 'type.required', false);
    data_set($col2, 'type.type', 'input');
    data_set($col2, 'type.class', 'cstype');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'tel2.type', 'cinput');

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        data_set($col2, 'empcode.label', 'Head Code');
        data_set($col2, 'empname.label', 'Reports to (Head)');
        data_set($col2, 'empcode.required', false);
        break;
      case 16: //ati
        data_set($col2, 'type.type', 'lookup');
        data_set($col2, 'type.class', 'cstype sbccsreadonly');
        data_set($col2, 'type.label', 'Payment Type');
        data_set($col2, 'type.lookupclass', 'lookuppaymenttype');
        data_set($col2, 'type.action', 'lookuppaymenttype');
        data_set($col2, 'deptcode.label', 'PO Type');
        data_set($col2, 'deptcode.action', 'lookuppotype');
        data_set($col2, 'deptcode.lookupclass', 'lookuppotype');
        break;
    }

    $fields = ['picture', 'isemployee', 'iswarehouse', 'issupplier', 'iscustomer', 'isagent', 'isinactive', 'isdepartment', 'isadmin'];
    switch ($systemtype) {
      case 'WAIMS':
        array_push($fields, 'uv_ischecker', 'uv_ispicker');
        break;
      case 'ATI':
        array_push($fields, 'isapprover', 'isdriver', 'ispassenger');
        break;
    }

    if ($companyid == 19) { //housegem
      array_push($fields, 'uv_ischecker', 'isdriver', 'ispassenger');
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'isemployee.class', 'csissupplier sbccsreadonly');
    data_set($col3, 'picture.lookupclass', 'client');
    data_set($col3, 'picture.folder', 'employee');
    data_set($col3, 'picture.table', 'client');
    data_set($col3, 'picture.fieldid', 'clientid');
    if ($companyid == 19) { //housegem
      data_set($col3, 'ispassenger.label', 'Helper');
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['contact'] = '';
    $data[0]['tel2'] = '';
    $data[0]['type'] = '';
    $data[0]['rem'] = '';
    $data[0]['position'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empname'] = '';
    $data[0]['empcode'] = '';
    $data[0]['start'] = null;

    $data[0]['isemployee'] = '1';
    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isdepartment'] = '0';
    $data[0]['isadmin'] = '0';
    $data[0]['isapprover'] = '0';
    $data[0]['isdriver'] = '0';
    $data[0]['ispassenger'] = '0';
    $data[0]['uv_ischecker'] = '0';
    $data[0]['uv_ispicker'] = '0';
    $data[0]['picture'] = '';
    $data[0]['salesgroupid'] = '0';
    $data[0]['branchid'] = '0';
    $data[0]['branchname'] = '';
    $data[0]['tin'] = '';

    $data[0]['idbarcode'] = '0';

    $data[0]['wh'] = '';
    $data[0]['whname'] = '';
    $data[0]['dropoffwh'] = '0';
    $data[0]['dropoffwhemp'] = '';
    $data[0]['deptcode'] = '';

    // customerid
    $data[0]['customerid'] = 0;
    $data[0]['customercode'] = '';
    $data[0]['customername'] = '';

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isemployee=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "client.client as docno,client.clientid, sgroup.groupname as salesgroup, ifnull(branch.clientname, '') as branchname";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    foreach ($this->otherfields as $key => $value) {
      $fields = $fields . ',info.' . $value;
    }
    $qryselect = "select " . $fields;

    $qry = $qryselect . ", dept.client as dept, dept.clientname as deptname, emp.client as empcode, 
      emp.clientname as empname, client.wh, ifnull(wh.clientname, '') as whname, 
      ifnull(wh2.clientname, '') as dropoffwhemp, ifnull(custid.client, '') as customercode, 
      ifnull(custid.clientname, '') as customername
      from client
      left join client as dept on client.deptid = dept.clientid
      left join client as emp on client.empid = emp.clientid
      left join salesgroup as sgroup on sgroup.line = client.salesgroupid
      left join client as branch on branch.clientid = client.branchid
      left join client as wh on wh.client = client.wh
      left join client as wh2 on wh2.clientid = client.dropoffwh
      left join employee as info on info.empid=client.clientid
      left join client as custid on custid.clientid = client.customerid
      where client.clientid = ? and client.isemployee = 1";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);

    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    $otherdata = [];
    $companyid = $config['params']['companyid'];

    if ($isupdate) {
      unset($this->fields['client']);
      unset($this->fields[0]);
    }

    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if
      }
    }
    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $otherdata[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $otherdata[$key] = $this->othersClass->sanitizekeyfield($key, $otherdata[$key], '', $companyid);
        } //end if
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      array_unshift($this->fields, 'client');
      $clientid = $head['clientid'];
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['isemployee'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    if ($companyid == 16) { //ati
      $empid = $this->coreFunctions->getfieldvalue("employee", "empid", "empid=?", [$clientid]);
      if ($empid == "") {
        $otherdata['empid'] = $clientid;
        $this->coreFunctions->sbcinsert('employee', $otherdata);
      } else {
        $this->coreFunctions->sbcupdate('employee', $otherdata, ['empid' => $clientid]);
      }
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  isemployee=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isemployee=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function deletetrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select lahead.trno as value from lahead where client=?
            union all
            select glhead.trno as value from glhead where clientid=? 
            limit 1";
    $count = $this->coreFunctions->datareader($qry, [$client, $clientid]);


    $qry1 = "
      select empid as value from paytrancurrent where empid=?
      union all
      select empid as value from paytranhistory where empid=?
      union all
      select empid as value from ratesetup where empid=?
      union all
      select empid as value from eschange where empid=?
      union all
      select empid as value from heschange where empid=?
    ";
    $count1 = $this->coreFunctions->datareader($qry1, [$clientid, $clientid, $clientid, $clientid, $clientid]);

    if (($count != '' || $count1 != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    if (strtoupper($systemtype) == 'VSCHED') {
      $qry = "
            select vrhead.trno as value from vrhead where clientid=?
            union all
            select hvrhead.trno as value from hvrhead where clientid=?
            limit 1";
      $count = $this->coreFunctions->datareader($qry, [$clientid, $clientid]);
      if (($count != '')) {
        return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
      }
    }

    $qry = "select clientid as value from client where clientid<? and isemployee=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    $this->othersClass->deleteattachments($config); // attachment delete
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter()
  {
    $fields = [
      'prepared',
      'approved',
      'received',
      'print'
    ];

    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
  ");
  }

  public function generateResult($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = md5($config['params']['dataid']);

    $query = "select client.client,client.clientname,client.addr,client.tel,
    client.tel2,client.tin,client.mobile,client.rem,
    client.email,client.contact,client.fax,client.start,client.status,client.quota,
    client.area,client.province,client.region,client.groupid,client.issupplier,client.iscustomer,
    client.isagent,client.isemployee
    from client where md5(client.clientid)='$clientid'";

    return $this->coreFunctions->opentable($query);
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

    $str = $this->reportplotting($config);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $data = $this->generateResult($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_employee_layout($config, $data);
    } else {
      $str = $this->rpt_employee_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_employee_layout($config, $data)
  {
    $data     = $this->generateResult($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function rpt_employee_PDF($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $count = 55;
    $page = 54;
    $fontsize = "11";
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/VERDANA.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/VERDANA.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/VERDANAB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    switch ($companyid) {
      case 3: //conti
      case 14: //majesty
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        break;

      default:
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        break;
    }
    $this->reportheader->getheader($config);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 30, "EMPLOYEE LEDGER - PROFILE", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 20, "Employee : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(690, 20, '(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '   ' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 20, "Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(690, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
} //end class
