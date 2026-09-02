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

class firearms
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'FIRE ARMS LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'firearms';
  public $prefix = 'FR';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $stockselect;

  private $fields = ['code', 'make', 'type', 'expiry', 'serialno', 'licenseno', 'cal'];
  private $except = ['line'];
  private $blnfields = [];
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
      'view' => 5952,
      'edit' => 5953,
      'new' => 5954,
      'save' => 5955,
      'change' => 5956,
      'delete' => 5957,
      'print' => 5958,
      'load' => 5951
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$listclient]['label'] = 'Code';
    $cols[$listclientname]['label'] = 'Make';
    $cols[$listaddr]['label'] = 'Type';
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
      $searchfield = ['fr.code', 'fr.make', 'fr.type','fr.expiry','fr.serialno','fr.licenseno','fr.cal'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select fr.line,fr.code as client,fr.make as clientname,fr.type as addr,fr.expiry,fr.serialno,fr.licenseno,fr.cal from firearms as fr where 1=1 " . $condition . " " . $filtersearch . "
     order by line " . $limit;

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
    return []; 
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['client', 'make', 'type',  'serialno','licenseno'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Fire Arms No.');
    data_set($col1, 'make.readonly', false);
    data_set($col1, 'make.readonly', false);
    data_set($col1, 'type.type', 'input');
    data_set($col1, 'type.class', 'csType');
    data_set($col1, 'type.readonly', false);
    data_set($col1, 'client.lookupclass', 'lookupledger_firearms');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
  
    $fields = ['expiry1', 'cal'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'expiry1.label', 'Expiry');
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function newclient($config)
  {
    $data = [];
    $data[0]['line'] = 0;
    $data[0]['code'] = $config['newclient'];
    
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    
    $data[0]['make'] = '';
    $data[0]['type'] = '';
    $data[0]['expiry1'] = null;
    $data[0]['serialno'] = '';
    $data[0]['licenseno'] = '';
    $data[0]['cal'] = '';
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = isset($config['params']['row']) ? $config['params']['row']['line'] : $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select line as value from firearms where  center=? order by line desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $qry =" select fr.line as clientid, fr.code as client, fr.make, fr.type, fr.expiry as expiry1, fr.serialno, fr.licenseno, fr.cal from firearms as fr where fr.line = ? ";
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
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['line' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $clientid];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['make'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    // var_dump($config['params']);
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];

    $dateTables = ['firearms'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    if ($isupdate) {
      unset($this->fields['code']);
    }
    $clientid = 0;
    $msg = '';

    foreach ($this->fields as $key) {

      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key],$lookups);
        }
      }

      // expiry1 sa head -> expiry sa firearms (dito isesave)
      // kaya to existing para mag ok ang saving dahil nagkakaproblem ng Incorrect datetime value: '08/26/2026' for column 'expiry', wala yung expiry1 sa table ng firearms
      if ($key == 'expiry' && array_key_exists('expiry1', $head)) {
        $data['expiry'] = date('Y-m-d 00:00:00',strtotime($head['expiry1'])
        );
      }

    }

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate('firearms', $data, ['line' => $head['clientid']]);
      $clientid = $head['clientid'];
    } else {
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('firearms', $data);
      $this->logger->sbcmasterlog($clientid, $config, 'CREATE No.' . $clientid . ' - ' . $head['client'] . ' - ' . $head['make']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select code as value from firearms order by line desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select code as value from firearms where left(code,?)=? order by code desc limit 1', [$length, $pref]);
    }
    return $return;
  }


  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('firearms', 'code', 'line=?', [$clientid]);
    $qry = "select line as value from firearms where line<? order by line desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from firearms where line=?', 'delete', [$clientid]);
    $this->logger->sbcdelmaster_log($clientid, $config, 'REMOVE - ' . $client);
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

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
