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

class agent
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AGENT LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'AG';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "isagent";

  private $fields = [
    'client', 'clientname', 'addr', 'start', 'status', 'tin',
    'groupid', 'area', 'province', 'region', 'class', 'quota', 'rem', 'parent', 'comm',
    'contact', 'tel', 'fax', 'tel2', 'email', 'iscustomer', 'issupplier', 'isagent', 'iswarehouse',
    'isemployee', 'isinactive', 'isdepartment', 'picture', 'agentcode', 'deptid', 'branchid',
    'issynced', 'uv_ispicker', 'uv_ischecker', 'collectorid', 'isoverride', 'salesgroupid', 'nocomm', 'wh', 'pword', 'isleader', 'pemail', 'enddate', 'position', 'alias'
  ];
  private $clinfo = ['fname', 'lname', 'mname'];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isemployee', 'isinactive', 'isdepartment', 'issynced', 'uv_ispicker', 'uv_ischecker', 'isoverride', 'nocomm', 'isleader'];
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 42,
      'edit' => 43,
      'new' => 44,
      'save' => 45,
      'change' => 46,
      'delete' => 47,
      'print' => 48,
      'load' => 41
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $company = $config['params']['companyid'];
    if ($company == 34) { //evergreen
      $this->modulename = 'EMPLOYEE LEDGER';
      $this->prefix = 'EE';
    }
    if ($company == 34) { //evergreen
      $getcols = ['action', 'listclient', 'listclientname', 'listposition'];
    } else {
      $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    if ($company == 34) {
      $cols[2]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
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
    $condition = '';
    $company = $config['params']['companyid'];
    $search = $config['params']['search'];

    $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
    $filtersearch = "";

    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select client.clientid,client.client,client.clientname,client.addr,client.position from client where client.isagent=1 " . $condition . " " . $filtersearch . "
     order by client " . $limit;

    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

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

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      if (($key = array_search('delete', $btns)) !== false) {
        unset($btns[$key]);
      }
    }

    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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
    $companyid = $config['params']['companyid'];

    $return = [];
    switch ($companyid) {
      case 6: //mitsukoshi
      case 14: //majesty
        $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];
        $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
        break;
      case 10: //afti
      case 12: //afti usd
      case 32: //3m
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryagentquota', 'label' => 'agentquo']];
        $agentquo = $this->tabClass->createtab($tab, []);
        $return['AGENT QUOTA'] = ['icon' => 'fa fa-address-book', 'tab' => $agentquo];
        break;
      case 34: //evergreen
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
        $attach = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryagentmembers', 'label' => 'MEMBERS']];
        $contactperson = $this->tabClass->createtab($tab, []);
        $return['MEMBERS'] = ['icon' => 'fa fa-users', 'tab' => $contactperson];

        $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];
        $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
        break;
      case 47: //kitchenstar
        $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];
        $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
        break;
    }

    return $return;
  }

  public function createtabbutton($config)
  {

    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['client', 'clientname', 'addr', 'start', 'clientstatus', 'pricegroup', 'dwhname', 'pword'];
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      array_push($fields, 'agentcode', 'deptname', 'branchname', 'empcode');
    }

    if ($companyid == 52) { //technolab
      $fields = ['client', 'clientname', 'addr', 'start', 'clientstatus', 'alias'];
    }

    if ($companyid == 34) { //evergreen
      $fields = ['client', 'fname', 'mname', 'lname', 'addr', 'start', 'enddate', 'clientstatus', 'rem'];
    }
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'client.label', 'Agent Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'clientstatus.label', 'Agent Status');


    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'agent');
    data_set($col1, 'client.action', 'lookupledgerclient');


    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'clientname.required', true);
    data_set($col1, 'addr.type', 'cinput');

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      data_set($col1, 'deptname.type', 'lookup');
      data_set($col1, 'deptname.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptname.action', 'lookupclient');
      data_set($col1, 'branchname.class', 'csbranchname sbccsreadonly');
      data_set($col1, 'branchname.lookupclass', 'hbranch');
      data_set($col1, 'branchname.required', true);
      data_set($col1, 'branchname.style', '');
      data_set($col1, 'deptname.required', true);
      data_set($col1, 'empcode.required', true);
      data_set($col1, 'empcode.name', 'collectorname');
      data_set($col1, 'empcode.label', 'Collection Officer');
      data_set($col1, 'empcode.lookupclass', 'collector');
      data_set($col1, 'empcode.class', 'cscollectorname sbccsreadonly');
    }

    $fields = ['contact', 'tel', 'fax', 'tel2', 'tin', 'rem'];
    if ($companyid == 34) { // evergreen
      data_set($col1, 'mname.required', false);
      data_set($col1, 'client.label', 'Employee Code');
      data_set($col1, 'clientstatus.label', 'Employee Status');
      data_set($col1, 'rem.required', false);
      $fields = ['tel', 'tel2', 'tin', 'pemail', 'position', 'area', 'quota'];
    }

    if ($companyid == 34) { // evergreen
      array_unshift($fields, "agparent");
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'tel.type', 'cinput');
    data_set($col2, 'fax.type', 'cinput');
    data_set($col2, 'tel2.type', 'cinput');
    data_set($col2, 'tin.type', 'cinput');

    $fields = ['groupid', 'email', 'area', 'province', 'region', 'quota'];
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $fields = ['salesgroup', 'email', 'area', 'province', 'region', 'quota'];
    }

    if ($companyid == 6) { //mitsukoshi
      array_push($fields, "comm", "agparent");
    }

    if ($companyid == 34) { //evergreen
      $fields = ['picture', ['isemployee', 'isleader'], ['isagent', 'isinactive']];
      data_set($col2, 'agparent.label', 'Leader');
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'groupid.lookupclass', 'lookupclientgroupledger');
    data_set($col3, 'groupid.action', 'lookupclientgroupledger');
    data_set($col3, 'groupid.class', 'csgroup');
    data_set($col3, 'groupid.readonly', false);
    data_set($col3, 'email.type', 'cinput');
    data_set($col3, 'quota.type', 'cinput');

    $fields = ['picture', ['iscustomer', 'issupplier'], ['isagent', 'iswarehouse'], ['isemployee', 'isinactive'], 'isdepartment'];
    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      array_push($fields, 'issynced');
    }

    if ($companyid == 10) { //afti
      array_push($fields, 'isoverride', 'nocomm');
    }

    if ($companyid == 14) { //majesty
      array_push($fields, 'uv_ispicker');
      array_push($fields, 'uv_ischecker');
    }

    if ($companyid == 34) { //evergreen
      data_set($col3, 'isagent.class', 'csisagent sbccsreadonly');
      data_set($col3, 'isagent.label', 'Employee');
      data_set($col3, 'isemployee.label', 'Agent');
      $fields = [];
    }

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'isagent.class', 'csisagent sbccsreadonly');
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'agent');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['enddate'] = '';
    $data[0]['status'] = '';
    $data[0]['contact'] = '';
    $data[0]['tin'] = '';
    $data[0]['groupid'] = '';
    $data[0]['area'] = '';
    $data[0]['province'] = '';
    $data[0]['region'] = '';
    $data[0]['grpcode'] = '';
    $data[0]['zipcode'] = '';
    $data[0]['tel'] = '';
    $data[0]['fax'] = '';
    $data[0]['tel2'] = '';
    $data[0]['email'] = '';
    $data[0]['pemail'] = '';
    $data[0]['alias'] = '';
    $data[0]['rem'] = '';
    $data[0]['class'] = '';
    $data[0]['quota'] = '0.00';
    $data[0]['comm'] = '0.00';
    $data[0]['parent'] = '0';
    $data[0]['agentname'] = '';
    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '1';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isemployee'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isdepartment'] = '0';
    $data[0]['isleader'] = '0';
    $data[0]['issynced'] = '0';
    $data[0]['uv_ispicker'] = '0';
    $data[0]['uv_ischecker'] = '0';
    $data[0]['isoverride'] = '0';
    $data[0]['picture'] = '';
    $data[0]['agentcode'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['branchid'] = '0';
    $data[0]['branchname'] = '';
    $data[0]['collectorid'] = '0';
    $data[0]['collectorname'] = '';
    $data[0]['salesgroup'] = '';
    $data[0]['salesgroupid'] = 0;
    $data[0]['nocomm'] = '0';
    $data[0]['wh'] = '';
    $data[0]['whname'] = '';
    $data[0]['dwhname'] = '';
    $data[0]['pword'] = '';
    $data[0]['fname'] = '';
    $data[0]['lname'] = '';
    $data[0]['mname'] = '';
    $data[0]['position'] = '';

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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isagent=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "client.client as docno,client.clientid, ifnull(ag.clientname,'') as agparent, ifnull(dept.client, '') as dept, ifnull(dept.clientname, '') as deptname,
        ifnull(branch.clientname, '') as branchname,ifnull(collector.clientname, '') as collectorname,sgroup.groupname as salesgroup ,
        wh.clientname as whname,
        '' as dwhname,ifnull(ci.fname,'') as fname,ifnull(ci.lname,'') as lname,ifnull(ci.mname,'') as mname ";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    $qryselect = "select " . $fields . " ";

    $qry = $qryselect . " from client 
      left join client as ag on ag.clientid=client.parent
      left join client as dept on client.deptid = dept.clientid
      left join client as branch on branch.clientid = client.branchid
      left join client as collector on collector.clientid = client.collectorid
      left join salesgroup as sgroup on sgroup.line = client.salesgroupid
      left join client as wh on wh.client = client.wh
      left join clientinfo as ci on ci.clientid = client.clientid
      where client.clientid = ? and client.isagent = 1";

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
    $cldata = [];
    $companyid = $config['params']['companyid'];

    if ($companyid == 34) { //evergreen
      if ($head['isinactive'] == "1" && ($head['enddate'] == "" || $head['enddate'] == null)) {
        return ["status" => false, "msg" => "End Date is Required!", "data" => []];
      }
    }

    if ($isupdate) {
      unset($this->fields[0]);
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc'], $companyid);
        } //end if    
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['ismirror'] = 0;

    foreach ($this->clinfo as $key2) {
      $cldata[$key2] = $head[$key2];
      if (!in_array($key2, $this->except)) {
        $cldata[$key2] = $this->othersClass->sanitizekeyfield($key2, $cldata[$key2], $config['params']['doc'], $companyid);
      } //end if    
    }

    if ($cldata['fname'] . (isset($cldata['mname']) ? '' . $cldata['mname'] : '') . (isset($cldata['lname']) ? '' . $cldata['lname'] : '') <> '') {
      $data['clientname'] = $cldata['lname'] . ', ' . (isset($cldata['fname']) ? $cldata['fname'] : '') . (isset($cldata['mname']) ? ' ' . $cldata['mname'] : '');
    }

    $cldata['clientid'] = $head['clientid'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      if ($companyid == 34) { //evergreen
        $exist = $this->coreFunctions->datareader("select ifnull(clientid,0) as value from clientinfo where clientid = ?", [$head['clientid']]);
        if (strlen($exist) != 0) {
          $this->coreFunctions->sbcupdate('clientinfo', $cldata, ['clientid' => $head['clientid']]);
        } else {
          $this->coreFunctions->sbcinsert('clientinfo', $cldata);
        }
      }

      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['isagent'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $cldata['clientid'] = $clientid;

      if ($companyid == 34) { //evergreen
        if (!empty($cldata)) {
          $this->coreFunctions->sbcinsert('clientinfo', $cldata);
        }
      }
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    $this->coreFunctions->execqry("delete from clientdlock where clientid=?", "delete", [$head['clientid']]);
    $this->coreFunctions->execqry("insert into clientdlock (clientid,dlock) values(?,?)", "insert", [$head['clientid'], $this->othersClass->getCurrentTimeStamp()]);

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  isagent=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isagent=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select lahead.trno as value from lahead where client=?
            union all 
            select glhead.trno as value from glhead where clientid=?
            union all
            select lahead.trno as value from lahead where agent=?
            union all 
            select glhead.trno as value from glhead where agentid=?
            union all
            select sohead.trno as value from sohead where client=?
            union all
            select hsohead.trno  as value from hsohead where client=?
            union all
            select sohead.trno as value from sohead where agent=?
            union all
            select hsohead.trno  as value from hsohead where agent=? limit 1";
    $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $clientid, $client, $client, $client, $client]);
    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and isagent=1 order by clientid desc limit 1 ";
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

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
