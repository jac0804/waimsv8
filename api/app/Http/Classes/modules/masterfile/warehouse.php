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

class warehouse
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'WAREHOUSE LEDGER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'WH';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "iswarehouse";

  private $fields = [
    'client',
    'picture',
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
    'issynced',
    'building',
    'floor',
    'isassetwh',
    'region',
    'nonsaleable',
    'rev',
    'ass'
  ];

  private $clientinfo = ['room'];

  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isconsign', 'issynced', 'nonsaleable', 'isassetwh'];
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
      'view' => 52,
      'edit' => 53,
      'new' => 54,
      'save' => 55,
      'change' => 56,
      'delete' => 57,
      'print' => 58,
      'load' => 51,
      'edititem' => 53,
      'deleteitem' => 53
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
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

    $qry = "select client.clientid,client.client,client.clientname,client.addr from client where iswarehouse =1 " . $condition . " " . $filtersearch . "
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

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $btns = array(
          'load',
          'print',
          'logs',
          'backlisting',
          'toggleup',
          'toggledown'
        );
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
    $documenttab_access = $this->othersClass->checkAccess($config['params']['user'], 2731);
    $nodstab_access = $this->othersClass->checkAccess($config['params']['user'], 2732);
    $jobreqtab_access = $this->othersClass->checkAccess($config['params']['user'], 2733);

    $tab = [];
    $stockbuttons = [];

    if ($this->companysetup->getispallet($config['params'])) {
      $tab = [$this->gridname => ['gridcolumns' => ['action', 'floor']]];
      $stockbuttons = ['save', 'delete', 'showwhloc'];
    }

    switch ($config['params']['companyid']) {
      case 3: //conti
      case 43: //mighty
        $tab = [
          'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrywhdocuments', 'label' => 'DOCUMENTS'],
          'jobdesctab' => ['action' => 'tableentry', 'lookupclass' => 'entrywhnods', 'label' => 'NODS'],
          'skilldesctab' => ['action' => 'tableentry', 'lookupclass' => 'entrywhjobreq', 'label' => 'JOB REQUESTS']
        ];

        if ($documenttab_access == 0) {
          unset($tab['tableentry']);
        }
        if ($nodstab_access == 0) {
          unset($tab['jobdesctab']);
        }
        if ($jobreqtab_access == 0) {
          unset($tab['skilldesctab']);
        }
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($this->companysetup->getispallet($config['params'])) {
      $obj[0][$this->gridname]['columns'][0]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
      $obj[0][$this->gridname]['columns'][1]['label'] = 'Floor (1,2,3...)';

      $obj[0][$this->gridname]['showtotal'] = false;
      $obj[0][$this->gridname]['descriptionrow'] = [];
      $obj[0][$this->gridname]['label'] = 'Location';
    }

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $return = [];
    switch ($config['params']['companyid']) {
      case 3: //conti
      case 43: //mighty
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        break;
    }
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['viewinv']; //'entrylocation'

    if ($this->companysetup->getispallet($config['params'])) {
      array_push($tbuttons, 'addrow');
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['lookupclass']  = 'viewwhinv';

    if ($this->companysetup->getispallet($config['params'])) {
      $obj[1]['access']  = 'edititem';

      $obj[1]['label']  = 'Add Floor';
    }

    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if ($companyid == 8) {
      $fields = ['client', 'clientname', 'addr', 'start'];
    } else {
      $fields = ['client', 'clientname', 'addr', 'start', 'dparentcodewh'];
    }
    if ($systemtype == 'FAMS' || $systemtype == 'ATI') {
      array_push($fields, 'region', 'building', 'floor', 'room');
    }

    if ($companyid == 40) { //cdo
      array_push($fields, 'dsalesacct', 'daracct');
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Warehouse Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'wh');
    data_set($col1, 'client.action', 'lookupledgerclient');

    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'clientname.required', true);
    data_set($col1, 'addr.type', 'cinput');

    if ($companyid == 40) { //cdo
      data_set($col1, 'dsalesacct.label', 'Due/from Account(Spareparts)');
      data_set($col1, 'dsalesacct.lookupclass', 'DUESP');
      data_set($col1, 'daracct.label', 'Due/from Account(Motorcycle)');
      data_set($col1, 'daracct.lookupclass', 'DUEMC');
    }

    $fields = ['contact', 'tel2', 'rem', 'type'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);
    data_set($col2, 'type.required', false);
    data_set($col2, 'type.type', 'input');
    data_set($col2, 'type.class', 'cstype');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'tel2.type', 'cinput');

    if ($this->companysetup->getisconsign($config['params'])) {
      $fields = ['picture', ['iswarehouse', 'issupplier'], ['iscustomer', 'isagent'], ['isinactive', 'isconsign']];
    } else {
      $fields = ['picture', ['iswarehouse', 'issupplier'], ['iscustomer', 'isagent'], 'isinactive'];
      if ($companyid == 10 || $companyid == 12) {
        $fields = ['picture', ['iswarehouse', 'issupplier'], ['iscustomer', 'isagent'], ['isinactive', 'nonsaleable']];
      }
    }

    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      array_push($fields, 'issynced');
    }

    if ($companyid == 16) {
      array_push($fields, 'isassetwh');
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'iswarehouse.class', 'csissupplier sbccsreadonly');
    data_set($col3, 'picture.lookupclass', 'client');
    data_set($col3, 'picture.folder', 'warehouse');
    data_set($col3, 'picture.table', 'client');
    data_set($col3, 'picture.fieldid', 'clientid');

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
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['parent'] = '';
    $data[0]['dparentcodewh'] = '';
    $data[0]['parentnamewh'] = '';

    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '1';
    $data[0]['isinactive'] = '0';
    $data[0]['isconsign'] = '0';
    $data[0]['issynced'] = '0';
    $data[0]['picture'] = '';
    $data[0]['nonsaleable'] = '0';
    $data[0]['isassetwh'] = '0';

    $data[0]['building'] = '';
    $data[0]['floor'] = '';
    $data[0]['room'] = '';
    $data[0]['region'] = '';
    $data[0]['rev'] = '';
    $data[0]['acnoname'] = '';
    $data[0]['ass'] = '';
    $data[0]['assetname'] = '';
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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where iswarehouse=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'client.clientid, client.client as docno';
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    foreach ($this->clientinfo as $key => $value) {
      $fields = $fields . ',clientinfo.' . $value;
    }
    $qryselect = "select " . $fields;

    $qry = $qryselect . " , ifnull(parentcode.clientname, '') as parentnamewh,ifnull(coa.acnoname, '') as acnoname, 
        ifnull(ar.acnoname, '') as assetname
        from client  
        left join client as parentcode on client.parent = parentcode.client
        left join clientinfo on clientinfo.clientid = client.clientid
        left join coa on coa.acno = client.rev
        left join coa as ar on ar.acno = client.ass
        where client.clientid = ? and client.iswarehouse = 1";

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
    $companyid = $config['params']['companyid'];
    $data = [];
    $clientinfo = [];
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
    foreach ($this->clientinfo as $key) {
      if (!in_array($key, $this->except)) {
        if (array_key_exists($key, $head)) {
          $clientinfo[$key] = $head[$key];
          $clientinfo[$key] = $this->othersClass->sanitizekeyfield($key, $clientinfo[$key], $config['params']['doc']);
        }
      } //end if    
    }
    $data['parent'] = str_replace('\\', '', $data['parent']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['ismirror'] = 0;
    $clientinfo['ismirror'] = 0;

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client', 'picture');
      $exist = $this->coreFunctions->getfieldvalue("clientinfo", "clientid", "clientid=?", [$clientid]);
      if (floatval($exist) == 0) {
        $clientinfo['clientid'] = $clientid;
        $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
      } else {
        $this->coreFunctions->sbcupdate('clientinfo', $clientinfo, ['clientid' => $head['clientid']]);
      }
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['iswarehouse'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $clientinfo['clientid'] = $clientid;
      $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  iswarehouse=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  iswarehouse=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
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

    $qry = "select clientid as value from client where clientid<? and iswarehouse=1 order by clientid desc limit 1 ";
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
    return ['status' => true, 'msg' => 'Delete Subject successful'];
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
