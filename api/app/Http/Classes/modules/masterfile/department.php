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

class department
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DEPARTMENT LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'DEP';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;

  private $fields = [
    'client',
    'clientname',
    'addr',
    'start',
    'tel2',
    'contact',
    'rem',
    'wh',
    'department',
    'rem1',
    'code',
    'rem2',
    'quota',
    'groupid',
    'intclient',
    'iscustomer',
    'issupplier',
    'isagent',
    'iswarehouse',
    'isinactive',
    'isdepartment',
    'isemployee',
    'picture',
    'empid',
    'email'
  ];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isdepartment', 'isemployee'];
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
      'view' => 861,
      'edit' => 862,
      'new' => 863,
      'save' => 864,
      'change' => 865,
      'delete' => 866,
      'print' => 867,
      'load' => 860
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $listclient = 1;
    $listclientname = 2;
    $listaddr = 3;

    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];

    if ($config['params']['companyid'] == '10' || $config['params']['companyid'] == '12') { // afti
      unset($getcols[$listaddr]);
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
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
    $search = $config['params']['search'];

    $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
    $condition = "";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr from client where isdepartment =1 " . $condition . " " . $filtersearch . "
     order by client " . $limit;

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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    return [];
  }

  public function createTab2($access, $config)
  {
    switch ($config['params']['companyid']) { // afti
      case 10: //afti
      case 12: //afti usd
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrymembers', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['MEMBERS'] = ['icon' => 'fa fa-user', 'tab' => $obj];
        return $return;
        break;

      default:
        return [];
        break;
    }
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
    $fields = ['client', 'clientname', 'addr', 'start', 'wh'];
    if ($companyid == 10 || $companyid == 12) { //afti & aftii usd
      unset($fields[2]);
      unset($fields[4]);
      array_push($fields, 'groupid');
    }
    if ($companyid == 16) { //ati
      array_push($fields, 'empname');
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Department Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'department');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'wh.lookupclass', 'whs');

    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'clientname.required', true);
    data_set($col1, 'addr.type', 'cinput');

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      data_set($col1, 'groupid.label', 'Group');
      data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
      data_set($col1, 'groupid.action', 'lookupclientgroupledger');
      data_set($col1, 'groupid.class', 'csgroup');
      data_set($col1, 'groupid.readonly', false);
    }

    data_set($col1, 'empname.label', 'Head');
    data_set($col1, 'empname.type', 'lookup');
    data_set($col1, 'empname.action', 'lookupclient');
    data_set($col1, 'empname.lookupclass', 'employee');

    $fields = ['contact', 'tel2', 'rem', 'quota'];

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      unset($fields[3]);
      unset($fields[4]);
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);
    data_set($col2, 'quota.label', 'Fund');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'tel2.type', 'cinput');
    data_set($col2, 'quota.type', 'cinput');
    data_set($col2, 'tel2.label', 'Contact #');

    $fields = ['dparentdept', 'ddeanhead', 'groupid', 'intclient', 'email'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'groupid.action', 'lookuplevel');
    data_set($col3, 'groupid.lookupclass', 'lookuplevel');
    data_set($col3, 'groupid.label', 'Level');

    data_set($col3, 'groupid.required', false);

    data_set($col3, 'ddeanhead.required', false);

    data_set($col3, 'intclient.type', 'cinput');

    $fields = ['picture', 'isdepartment', 'iswarehouse', 'issupplier', 'iscustomer', 'isagent', 'isinactive', 'isemployee'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'isdepartment.class', 'isdepartment sbccsreadonly');
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'department');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      return array('col1' => $col1, 'col2' => $col2, 'col4' => $col4);
    } else {
      return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
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
    $data[0]['rem'] = '';
    $data[0]['start'] = null;
    $data[0]['wh'] = '';
    $data[0]['department'] = '';
    $data[0]['rem1'] = '';
    $data[0]['code'] = '';
    $data[0]['rem2'] = '';
    $data[0]['quota'] = '0';
    $data[0]['groupid'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empname'] = '';
    $data[0]['intclient'] = '0';

    $data[0]['isdepartment'] = '1';
    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isemployee'] = '0';
    $data[0]['picture'] = '';
    $data[0]['email'] = '';
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isdepartment=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "client.clientid, ifnull(emp.clientname,'') as empname";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    $qryselect = "select " . $fields;

    $qry = $qryselect . " from client left join client as emp on emp.clientid=client.empid
        where client.clientid = ? and client.isdepartment = 1";

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
    $companyid = $config['params']['companyid'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['client']);
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
    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['isdepartment'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  isdepartment=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isdepartment=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }


  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select lahead.trno as value from lahead where deptid=?
            union all 
            select glhead.trno as value from glhead where deptid=? limit 1";
    $count = $this->coreFunctions->datareader($qry, [$clientid, $clientid]);

    $qry1 = "select deptid as value from employee where deptid=? limit 1";
    $count1 = $this->coreFunctions->datareader($qry1, [$clientid]);

    if ($count != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    if ($count1 != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already used in employee ledger...'];
    }

    $qry = "select clientid as value from client where clientid<? and isdepartment=1 order by clientid desc limit 1 ";
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
