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

class tenant
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TENANT LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'TL';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;

  private $fields = [
    'client', 'clientname', 'addr', 'bstyle', 'category', 'email', 'locid', 'start', 'enddate',
    'tin', 'tel', 'contact', 'isnonvat', 'type', 'istenant', 'isinactive'
  ];

  private $except = ['clientid'];
  private $blnfields = ['istenant', 'isinactive', 'isnonvat'];
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
      'load' => 348,
      'view' => 1745,
      'edit' => 1765,
      'new' => 331,
      'save' => 789,
      'change' => 1685,
      'delete' => 886,
      'print' => 902
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

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$listclient]['align'] = 'text-left';
    $cols[$listclientname]['align'] = 'text-left';
    $cols[$listaddr]['align'] = 'text-left';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = [
        'client.clientid', 'client.client', 'client.clientname',
        'client.addr', 'category.cat_name', 'client.rem'
      ];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select client.clientid,client.client,client.clientname,
    client.addr,category.cat_name, client.rem as notes
    from client 
    left join category_masterfile as category on category.cat_id = client.category
    where istenant = 1 " . $filtersearch . "
    order by client";

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
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $profile = ['customform' => ['action' => 'customform', 'lookupclass' => 'tenant_profile_tab']];
    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryescalation_tab', 'label' => 'ESCALATION']];
    $ar_access = $this->othersClass->checkAccess($config['params']['user'], 4213);
    $ap_access = $this->othersClass->checkAccess($config['params']['user'], 4214);
    $pdc_access = $this->othersClass->checkAccess($config['params']['user'], 4215);
    $tenancy = $this->othersClass->checkAccess($config['params']['user'], 4216);

    $ar = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewar']];
    $ap = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewap']];
    $pdc = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewpdc']];
    $ts = ['customform' => ['action' => 'customform', 'lookupclass' => 'tenancy']];

    $escalation = $this->tabClass->createtab($tab, []);

    $return = [];
    $return['ESCALATION'] = ['icon' => 'fa fa-list-ul', 'tab' => $escalation];
    $return['PROFILE'] = ['icon' => 'fa fa-user', 'customform' => $profile];

    if ($ar_access != 0) {
      $return['ACCOUNT RECEIVABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ar];
    }

    if ($ap_access != 0) {
      $return['ACCOUNT PAYABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ap];
    }

    if ($pdc_access != 0) {
      $return['POSTDATED CHECKS HISTORY'] = ['icon' => 'fa fa-money-check', 'customform' => $pdc];
    }

    if ($tenancy != 0) {
      $return['TENANCY STATUS'] = ['icon' => 'fa fa-money-check', 'customform' => $ts];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    //$obj = $this->tabClass->createtab($tab,$stockbuttons);
    //return $obj;
    return [];
  }

  public function createtabbutton($config)
  {
    //$tbuttons = ['viewap','viewar','viewinv'];
    //$obj = $this->tabClass->createtabbutton($tbuttons);
    //$obj[2]['lookupclass']  = 'viewsupplierinv';
    //return $obj;
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['client', 'clientname', 'addr', 'bstyle', 'category', 'email'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Tenant Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'tenant');
    data_set($col1, 'client.action', 'lookupledgerclient');

    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'addr.type', 'cinput');
    data_set($col1, 'bstyle.type', 'cinput');
    data_set($col1, 'email.type', 'cinput');

    data_set($col1, 'addr.label', 'Business Address');
    data_set($col1, 'category.label', 'Nature of Business');




    $fields = ['loc', 'start', 'enddate', 'tin', 'tel', 'contact'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'loc.action', 'lookuploc_mms');
    data_set($col2, 'loc.lookupclass', 'lookuploc_mms');

    data_set($col2, 'tel.type', 'cinput');
    data_set($col2, 'tel.label', 'Contact No.');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'contact.label', 'Contact Person');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['picture', 'istenant', 'isinactive', 'isnonvat'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'istenant.class', 'csistenant sbccsreadonly');
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'supplier');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function newclient($config)
  {
    $companyid = $config['params']['companyid'];

    $data = [];

    // col1
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    // $data[0]['client'] = $this->getnewclient($config, $this->prefix);
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['category'] = '';
    $data[0]['categoryname'] = '';
    $data[0]['bstyle'] = '';
    $data[0]['email'] = '';

    // col2
    $data[0]['locid'] = '0';
    $data[0]['loc'] = '';
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['enddate'] = '';
    $data[0]['tin'] = '';
    $data[0]['tel'] = '';
    $data[0]['contact'] = '';

    // col4
    $data[0]['picture'] = '';
    $data[0]['istenant'] = '1';
    $data[0]['isinactive'] = '0';
    $data[0]['isnonvat'] = '0';

    $data[0]['type'] = '';

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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where istenant=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'client.clientid';
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    $qryselect = "select " . $fields . ", ifnull(category.cat_name, '') as categoryname,
    ifnull(loc.name, '') as loc";
    $qry = $qryselect . " 
      from client  as client
      left join category_masterfile as category on category.cat_id = client.category
      left join loc as loc on loc.line = client.locid
      where client.clientid = ? and istenant = 1";

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
    if ($isupdate) {
      unset($this->fields[0]);
    }
    $clientid = 0;
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    $msg = '';

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['istenant'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $this->coreFunctions->sbcinsert('tenantinfo', ["clientid" => $clientid]);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];

    
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  istenant=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  istenant=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }

    return $return;
  }

  private function getnewclient($config, $pref)
  {
    $clientlength =  $this->companysetup->getclientlength($config['params']);
    $last = $this->othersClass->getlastclient($pref, 'tenant');
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $clientlength);
    return $newclient;
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
    select sohead.trno as value from sohead where client=?
    union all
    select hsohead.trno as value from hsohead where client=? 
    union all
    select trno as value from vrstock where clientid=?
    union all
    select trno as value from hvrstock where clientid=?
    union all
    select trno as value from eahead where client=?
    union all
    select trno as value from heahead where client=?
    union all
    select trno as value from eainfo where client=?
    union all
    select trno as value from heainfo where client=?
    union all
    select trno as value from lphead where client=?
    union all
    select trno as value from hlphead where client=? limit 1";

    $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $client, $clientid, $clientid, $client, $client, $client, $client, $client, $client]);
    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and issupplier=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from tenantinfo where clientid=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  //printout
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


} //end class
