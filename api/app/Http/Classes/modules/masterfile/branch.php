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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class branch
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BRANCH LEDGER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'BR';
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
    'type',
    'parent',
    'iscustomer',
    'issupplier',
    'isagent',
    'iswarehouse',
    'isinactive',
    'isconsign',
    'picture',
    'isbranch',
    'isallitem',
    'issynced',
    'prefix'
  ];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isconsign', 'isbranch', 'isallitem', 'issynced'];
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
      'view' => 2586,
      'edit' => 2587,
      'new' => 2588,
      'save' => 2589,
      'change' => 2590,
      'delete' => 2591,
      'print' => 2592,
      'load' => 2585,
      'edititem' => 2587,
      'deleteitem' => 2587
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[2]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';
    $cols[3]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';

    $cols[1]['align'] = 'left';
    $cols[2]['align'] = 'left';
    $cols[3]['align'] = 'left';
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
    $limit = "limit 25";
    $condition = "";
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr from client where isbranch=1 " . $condition . " " . $filtersearch . " 
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
    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      if (($key = array_search('delete', $btns)) !== false) {
        unset($btns[$key]);
      }
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tab = [];
    $stockbuttons = [];
    $obj = [];

    if ($this->companysetup->getispos($config['params'])) {
      $tab = [
        'branchstation' => ['action' => 'tableentry', 'lookupclass' => 'entrybranchstation', 'label' => 'STATION'],
        'branchwh' => ['action' => 'tableentry', 'lookupclass' => 'entrybranchwh', 'label' => 'WAREHOUSE'],
        'branchbrand' => ['action' => 'tableentry', 'lookupclass' => 'entrybranchbrand', 'label' => 'BRAND'],
        'branchagent' => ['action' => 'tableentry', 'lookupclass' => 'entrybranchagent', 'label' => 'AGENTS'],
        'branchusers' => ['action' => 'tableentry', 'lookupclass' => 'entrybranchuser', 'label' => 'USERS'],
        'branchbankterminal' => ['action' => 'tableentry', 'lookupclass' => 'entrybankterminal', 'label' => 'BANK TERMINAL'],
      ];
    }
    if ($companyid == 58) { //cdohris
      $tab = ['branchjob' => ['action' => 'tableentry', 'lookupclass' => 'viewbranchjob', 'label' => 'JOB LIST']];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $obj = [];

    return $obj;
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 9: //hrispayrol
        $fields = ['client', 'clientname', 'addr', 'start', 'isinactive'];
        break;
      case 56: //homeworks
        $fields = ['client', 'clientname', 'addr', 'start', 'dparentcodewh', 'prefix'];
        break;
      default:
        $fields = ['client', 'clientname', 'addr', 'start', 'dparentcodewh'];
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Branch Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'branch');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'addr.type', 'cinput');
    if ($companyid == 56) { //homeworks
      data_set($col1, 'prefix.maxlength', 2);
    }

    $fields = ($companyid == 58) ? [] : ['contact', 'tel2', 'rem', 'type']; //cdo
    $col2 = $this->fieldClass->create($fields);
    if ($companyid != 58) {
      data_set($col2, 'contact.label', 'Contact Person');
      data_set($col2, 'rem.required', false);
      data_set($col2, 'type.required', false);
      data_set($col2, 'type.type', 'input');
      data_set($col2, 'type.class', 'cstype');

      data_set($col2, 'contact.type', 'cinput');
      data_set($col2, 'tel2.type', 'cinput');
    }

    if ($this->companysetup->getisconsign($config['params'])) {
      $fields = ['picture', ['isbranch', 'iswarehouse'], ['issupplier', 'iscustomer'], ['isagent', 'isinactive'], 'isallitem', 'isconsign'];
    } else {
      //hrispayroll
      $fields = ($companyid == 58) ? [] :  ['picture', ['isbranch', 'iswarehouse'], ['issupplier', 'iscustomer'], ['isagent', 'isinactive'], 'isallitem'];
    }

    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      array_push($fields, 'issynced');
    }

    $col3 = $this->fieldClass->create($fields);
    if ($companyid != 58) { //not hrispayroll
      data_set($col3, 'isbranch.class', 'csissupplier sbccsreadonly');
      data_set($col3, 'picture.lookupclass', 'client');
      data_set($col3, 'picture.folder', 'warehouse');
      data_set($col3, 'picture.table', 'client');
      data_set($col3, 'picture.fieldid', 'clientid');
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
    $data[0]['start'] = '';
    $data[0]['parent'] = '';
    $data[0]['dparentcodewh'] = '';
    $data[0]['parentnamewh'] = '';

    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isconsign'] = '0';
    $data[0]['isallitem'] = '0';
    $data[0]['isbranch'] = '1';
    $data[0]['issynced'] = '0';
    $data[0]['picture'] = '';
    $data[0]['prefix'] = '';
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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isbranch=1 and center=? order by clientid desc limit 1", [$center]);
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
    $qryselect = "select " . $fields;

    $qry = $qryselect . " , ifnull(parentcode.clientname, '') as parentnamewh
        from client  
        left join client as parentcode on client.parent = parentcode.client
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
      $stock = [];
      if ($this->companysetup->getispallet($config['params'])) {
        $stock = $this->coreFunctions->opentable($this->selectstockqry(), [$clientid]);
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $stock = [];
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      return ['status' => false, 'griddata' => ['inventory' => $stock], 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  private function selectstockqry($line = 0)
  {
    $qry = "select whid,whid as trno,floor,line,'' as bgcolor from floor where whid=? ";
    if ($line != 0) {
      $qry .= " and line=?";
    }
    $qry .= " order by line";
    return $qry;
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
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    if (!empty($data['prefix'])) {
      $data['prefix'] = strtoupper($data['prefix']);
    }
    $data['parent'] = str_replace('\\', '', $data['parent']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['ismirror'] = 0;

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['isbranch'] = 1;
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
      $return = $this->coreFunctions->datareader('select client as value from client where  isbranch=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isbranch=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
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

    $qry = "select clientid as value from client where clientid<? and isbranch=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    $this->othersClass->deleteattachments($config); // attachment delete
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;

      case 'saveperitem':
        return $this->updateitem($config);
        break;

      case 'deleteitem':
        return $this->deleteitem($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function updateitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $result = $this->additem('update', $config);
    if ($result['status']) {
      return ['row' => $result['row'], 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['row' => $result['row'], 'status' => false, 'msg' => $result['msg']];
    }
  }

  public function deleteitem($config)
  {
    $whid = $config['params']['row']['whid'];
    $line = $config['params']['row']['line'];

    $exist = $this->coreFunctions->datareader("select floor as value from location where whid=? and floor=?", [$whid, $line]);
    if ($exist) {
      return ['status' => false, 'msg' => 'Unable to delete floor. Already used in locations'];
    }

    $this->coreFunctions->execqry('delete from floor where whid=? and line=?', 'delete', [$whid, $line]);
    return ['status' => true, 'msg' => 'Delete subject successfully.'];
  }

  public function additem($action, $config)
  {
    $whid = $config['params']['data']['whid'];
    $line = $config['params']['data']['line'];

    $data = [];
    $data['whid'] =  $whid;
    $data['floor'] =  $this->othersClass->sanitizekeyfield("floor", $config['params']['data']['floor']);
    $data['line'] = $line;

    $exist = $this->coreFunctions->datareader("select floor as value from floor where whid=? and floor=?", [$whid, $data['floor']]);

    if (!$exist) {
      if ($line == 0) {
        $line = $this->coreFunctions->insertGetId("floor", $data);
      } else {
        $this->coreFunctions->sbcupdate("floor", $data, ['whid' => $whid, 'line' => $line]);
      }
      $row = $this->openstockline($whid, $line);
      return ['row' => $row, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      $data['bgcolor'] = 'bg-red-2';
      return ['row' => $data, 'status' => false, 'msg' => 'Floor ' . $data['floor'] . ' name already exists'];
    }
  }

  public function openstockline($whid, $line)
  {
    return $this->coreFunctions->opentable($this->selectstockqry($line), [$whid, $line]);
  }

  public function addrow($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['whid'] = $config['params']['trno'];
    $data['floor'] = '';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = [
      'prepared',
      'approved',
      'received',
      'print'
    ];

    $col1 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 10) { // afti
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }
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
    $data = $this->generateResult($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_branch_default_layout($config, $data);
    } else {
      $str = $this->rpt_branch_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_branch_default_layout($config)
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
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BRANCH LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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

  public function rpt_branch_PDF($config, $data)
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
      case 39: //cbbsi
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        break;
      default:
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
        break;
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 30, "BRANCH LEDGER - PROFILE", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 20, "Branch : ", '', 'L', false, 0);
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
