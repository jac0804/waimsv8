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

class ml
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MECHANIC LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'ML';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "ismechanic";

  private $fields = [
    'client',
    'clientname',
    'addr',
    'tin',
    'tel',
    'fax',
    'mobile',
    'email',
    'contact',
    'iscustomer',
    'ismechanic',
    'isinactive',
    'start',
    'status',
    'area',
    'province',
    'region',
    'picture',
    'rate'
  ];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'ismechanic', 'isinactive'];
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
      'view' => 5859,
      'edit' => 5860,
      'new' => 5861,
      'save' => 5862,
      'delete' => 5863,
      'print' => 5864,
      'load' => 5858
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $company = $config['params']['companyid'];
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $condition = '';
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
    $qry = "select client.clientid,client.client,client.clientname,client.addr from client where client.ismechanic=1 " . $condition . " " . $filtersearch . "
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
    $fields = ['picture'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'picture.lookupclass', 'client');
    data_set($col1, 'picture.folder', 'ml');
    data_set($col1, 'picture.table', 'client');
    data_set($col1, 'picture.fieldid', 'clientid');
    $fields = ['start', 'status', 'area', 'province', 'region'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'status.type', 'input');
    data_set($col2, 'status.class', 'csstatus');
    data_set($col2, 'status.readonly', false);
    data_set($col2, 'area.type', 'input');
    data_set($col2, 'province.type', 'input');
    data_set($col2, 'region.type', 'input');

    $fields = ['rate'];
    $col3 = $this->fieldClass->create($fields);



    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3], 'label' => 'Profile']
    ];
    $tab['tableentry']  =  ['action' => 'autoserventry', 'lookupclass' => 'entrymechanictask', 'label' => 'TASK'];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($access, $config)
  {
    $return = [];
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
    $attach = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];
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
    $fields = ['client', 'clientname', 'addr', 'tin'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Mechanic Code');
    data_set($col1, 'client.required', true);
    data_set($col1, 'addr.type', 'input');
    $fields = ['tel', 'fax', 'mobile', 'email'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'mobile.label', 'Cel#');

    $fields = ['contact', 'isinactive'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'contact.label', 'Contact Person');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['tin'] = '';
    $data[0]['tel'] = '';
    $data[0]['fax'] = '';
    $data[0]['mobile'] = '';
    $data[0]['contact'] = '';
    $data[0]['email'] = '';
    $data[0]['ismechanic'] = '1';
    $data[0]['isinactive'] = '0';

    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['status'] = '';

    $data[0]['area'] = '';
    $data[0]['province'] = '';
    $data[0]['region'] = '';
    $data[0]['status'] = '';
    $data[0]['picture'] = '';

    $data[0]['rate'] = '';

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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where ismechanic=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $qryselect = "select  client.client as docno,client.clientid, client.clientname, ifnull(client.addr, '') as addr,ifnull(client.tin, '') as tin,
    ifnull(client.fax, '') as fax,ifnull(client.mobile, '') as mobile,ifnull(client.email, '') as email,ifnull(client.contact, '') as contact,
    
    client.client, client.tel,  client.iscustomer, client.ismechanic, client.isinactive,
    date(client.start) as start, client.status, client.area, client.province, client.region, client.picture,client.rate ";

    $qry = $qryselect . " from client
      where client.clientid = ? and client.ismechanic = 1";
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
    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['ismechanic'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $cldata['clientid'] = $clientid;
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
      $return = $this->coreFunctions->datareader('select client as value from client where  ismechanic=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  ismechanic=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select amtask.trno as value from amtask where mecline=?
            union all 
            select hamtask.trno as value from hamtask where mecline=?
            union all
            select pttask.trno as value from pttask where mecline=?
            union all
            select hpttask.trno  as value from hpttask where mecline=? limit 1";
    $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $clientid, $client, $client, $client, $client]);
    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and ismechanic=1 order by clientid desc limit 1 ";
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
    // $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);

    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
