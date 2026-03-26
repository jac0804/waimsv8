<?php

namespace App\Http\Classes\modules\warehousing;

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

class forwarder
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DELIVERY';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'FT';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;

  private $fields = [
    'client', 'picture', 'clientname', 'addr', 'start', 'tel2',
    'contact', 'rem', 'type', 'deliverytype', 'plateno',
    'iscustomer', 'issupplier', 'isagent', 'iswareHouse', 'isinactive', 'istrucking', 'classification'
  ];

  private $daysched = ['ismon', 'ismon_am', 'ismon_pm', 'istue', 'istue_am', 'istue_pm', 'iswed', 'iswed_am', 'iswed_pm', 'isthu', 'isthu_am', 'isthu_pm', 'isfri', 'isfri_am', 'isfri_pm', 'issat', 'issat_am', 'issat_pm', 'issun', 'issun_am', 'issun_pm'];
  private $clientinfo = ['capacity'];
  private $except = ['clientid'];
  private $blnfields = [
    'iscustomer', 'issupplier', 'isagent', 'iswareHouse', 'isinactive', 'istrucking',
    'ismon', 'ismon_am', 'ismon_pm', 'istue', 'istue_am', 'istue_pm', 'iswed', 'iswed_am', 'iswed_pm', 'isthu', 'isthu_am', 'isthu_pm', 'isfri', 'isfri_am', 'isfri_pm', 'issat', 'issat_am', 'issat_pm', 'issun', 'issun_am', 'issun_pm'
  ];
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
      'view' => 1878,
      'edit' => 1879,
      'new' => 1880,
      'save' => 1881,
      'change' => 1882,
      'delete' => 1883,
      'print' => 1884,
      'load' => 1877
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    $getcols = ['action', 'listclient', 'listclientname', 'listaddr', 'deliverytypename'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    if ($companyid == 19) { //housegem
      $cols[4]['label'] = 'Truck Type';
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

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr', 'deliverytype.name'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr, deliverytype.name as deliverytypename
    from client left join deliverytype on deliverytype.line = client.deliverytype where istrucking = 1 $filtersearch
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

  public function createTab($access, $config)
  {
    $systype = $this->companysetup->getsystemtype($config['params']);
    if ($systype  == 'VSCHED' || $systype  == 'ATI') {
      $this->modulename = 'VEHICLE';
    }

    if ($config['params']['companyid'] == 19) { //housegem
      $this->modulename = 'TRUCK';
    }

    $tab = [];
    if ($config['params']['companyid'] == 19) { //housegem
      $tab = [
        'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytruckdocuments', 'label' => 'DOCUMENTS']
      ];
    }
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['lookupclass']  = 'viewwhinv';
    return $obj;
  }

  public function createHeadField($config)
  {
    $systype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['client', 'clientname', 'addr', 'start', 'deliverytype', 'plateno', 'classification', 'type'];

    if ($systype == 'VSCHED' || $systype == 'ATI') {
      unset($fields[4]);
    }

    $col1 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 19) { //housegem
      data_set($col1, 'client.label', 'Truck Code');
      data_set($col1, 'deliverytype.label', 'Type');
    } else {
      data_set($col1, 'client.label', 'Forwarder/Truck Code');
    }
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'wh');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'deliverytype.action', 'lookupdeliverytypename');
    data_set($col1, 'deliverytype.name', 'deliverytypename');

    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'addr.type', 'cinput');

    data_set($col1, 'plateno.required', true);

    data_set($col1, 'classification.required', true);
    data_set($col1, 'classification.label', 'Brand');
    data_set($col1, 'classification.lookupclass', 'lookupvehiclebrand');

    data_set($col1, 'type.required', true);
    // data_set($col1, 'type.type', 'input');
    data_set($col1, 'type.class', 'cstype');
    data_set($col1, 'type.label', 'Model');
    data_set($col1, 'type.action', 'lookuprandom');
    data_set($col1, 'type.lookupclass', 'lookupvehiclemodel');

    $fields = ['contact', 'tel2', 'rem', 'capacity'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'tel2.type', 'cinput');

    $fields = ['picture', 'istrucking', 'isinactive'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'istrucking.class', 'csistrucking sbccsreadonly');
    data_set($col3, 'picture.lookupclass', 'client');
    data_set($col3, 'picture.folder', 'warehouse');
    data_set($col3, 'picture.table', 'client');
    data_set($col3, 'picture.fieldid', 'clientid');

    if ($systype  == 'VSCHED' || $systype  == 'ATI') {
      data_set($col3, 'istrucking.label', 'Vehicle');
    }

    $fields = [];
    if ($systype  == 'ATI') {
      array_push($fields, 'lblrem');
      array_push($fields, 'ismon', ['ismon_am', 'ismon_pm']);
      array_push($fields, 'istue', ['istue_am', 'istue_pm']);
      array_push($fields, 'iswed', ['iswed_am', 'iswed_pm']);
      array_push($fields, 'isthu', ['isthu_am', 'isthu_pm']);
      array_push($fields, 'isfri', ['isfri_am', 'isfri_pm']);
      array_push($fields, 'issat', ['issat_am', 'issat_pm']);
      array_push($fields, 'issun', ['issun_am', 'issun_pm']);
    }

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'lblrem.label', 'Schedule');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
    $data[0]['start'] = '';
    $data[0]['deliverytype'] = 0;
    $data[0]['deliverytypename'] = '';
    $data[0]['plateno'] = '';
    $data[0]['classification'] = '';


    $data[0]['istrucking'] = '1';
    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswareHouse'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['picture'] = '';

    $data[0]['ismon'] = '0';
    $data[0]['ismon_am'] = '0';
    $data[0]['ismon_pm'] = '0';
    $data[0]['istue'] = '0';
    $data[0]['istue_am'] = '0';
    $data[0]['istue_pm'] = '0';
    $data[0]['iswed'] = '0';
    $data[0]['iswed_am'] = '0';
    $data[0]['iswed_pm'] = '0';
    $data[0]['isthu'] = '0';
    $data[0]['isthu_am'] = '0';
    $data[0]['isthu_pm'] = '0';
    $data[0]['isfri'] = '0';
    $data[0]['isfri_am'] = '0';
    $data[0]['isfri_pm'] = '0';
    $data[0]['issat'] = '0';
    $data[0]['issat_am'] = '0';
    $data[0]['issat_pm'] = '0';
    $data[0]['issun'] = '0';
    $data[0]['issun_am'] = '0';
    $data[0]['issun_pm'] = '0';

    $data[0]['capacity'] = 0;

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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where istrucking=1 and center=? order by clientid desc limit 1", [$center]);
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

    foreach ($this->daysched as $key => $value) {
      $fields = $fields . ',sched.' . $value;
    }

    foreach ($this->clientinfo as $key => $value) {
      $fields = $fields . ',info.' . $value;
    }

    $qryselect = "select " . $fields;

    $qry = $qryselect . " , ifnull(deliverytype.line, 0) as deliverytype, deliverytype.name as deliverytypename 
        from client  
        left join deliverytype on deliverytype.line = client.deliverytype 
        left join daysched as sched on sched.clientid=client.clientid
        left join clientinfo as info on info.clientid=client.clientid
        where client.clientid = ? ";

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
    $datasched = [];
    $dataclientinfo = [];

    if ($isupdate) {
      unset($this->fields[0]);
      unset($this->fields[1]);
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

    foreach ($this->daysched as $key) {
      if (array_key_exists($key, $head)) {
        $datasched[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $datasched[$key] = $this->othersClass->sanitizekeyfield($key, $datasched[$key]);
        } //end if    
      }
    }
    foreach ($this->clientinfo as $key) {
      if (array_key_exists($key, $head)) {
        $dataclientinfo[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataclientinfo[$key] = $this->othersClass->sanitizekeyfield($key, $dataclientinfo[$key]);
        } //end if    
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $datasched['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $datasched['editby'] = $config['params']['user'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
      $this->coreFunctions->sbcupdate('daysched', $datasched, ['clientid' => $head['clientid']]);
      $this->coreFunctions->sbcupdate('clientinfo', $dataclientinfo, ['clientid' => $head['clientid']]);
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['istrucking'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      if ($clientid != 0) {
        $datasched['clientid'] = $clientid;
        $this->coreFunctions->insertGetId('daysched', $datasched);

        $dataclientinfo['clientid'] = $clientid;
        $this->coreFunctions->sbcinsert("clientinfo", $dataclientinfo);
      }
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  istrucking=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  istrucking=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
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
            union all
            select lahead.trno as value from lahead where wh=?
            union all 
            select glhead.trno as value from glhead where whid=?
            union all
            select lastock.trno as value from lastock where whid=?
            union all 
            select glstock.trno as value from glstock where whid=?
            union all
            select pohead.trno as value from pohead where wh=?
            union all
            select hpohead.trno as value from hpohead where wh=?
            union all
            select sohead.trno as value from sohead where wh=?
            union all
            select hsohead.trno as value from hsohead where wh=? limit 1";
    $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $clientid, $clientid, $clientid, $client, $client, $client, $client]);
    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    if (strtoupper($systemtype) == 'VSCHED' || strtoupper($systemtype) == 'ATI') {
      $qry = "
            select vrhead.trno as value from vrhead where vehicleid=?
            union all
            select hvrhead.trno as value from hvrhead where vehicleid=?
            limit 1";
      $count = $this->coreFunctions->datareader($qry, [$clientid, $clientid]);
      if (($count != '')) {
        return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
      }
    }

    $qry = "select clientid as value from client where clientid<? and istrucking=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
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
      'refresh'
    ];

    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'default' as print,
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

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];


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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $data     = $this->generateResult($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

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
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FORWARDER/TRUCK - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
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
} //end class
