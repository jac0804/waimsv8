<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\sbcscript\sbcscript;

class codeconduct
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CODE OF CONDUCT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'codehead';
  public $detail = 'codedetail';
  public $tablelogs = 'masterfile_log';
  public $prefix = '';
  public $tablelogs_del = '';
  private $stockselect;
  public $tablenum = "";

  private $fields = [
    'code',
    'description'
  ];
  private $except = ['clientid', 'client'];
  private $blnfields = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;
  private $sbcscript;


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
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1261,
      'edit' => 1262,
      'new' => 1263,
      'save' => 1264,
      'change' => 1336,
      'delete' => 1265,
      'print' => 1337,
      'load' => 1260,
      'additem' => 1338,
      'edititem' => 1339,
      'deleteitem' => 1340

    );
    return $attrib;
  }

  public function sbcscript($config)
  {
    return $this->sbcscript->codeconduct($config);
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'code', 'description'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $cols[2]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['artid', 'code', 'description'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select artid as clientid, code, description 
                from " . $this->head . " where 1=1 " . $filtersearch;
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
    $tab = [
      'tableentry' => [
        'action' => 'hrisentry', // directory folder where table entry file is located 
        'lookupclass' => 'entrycodeconductsection', // table entry file
        'label' => 'SECTION'
      ]
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [
      'client',
      'description'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupledgercodeconduct');

    return array('col1' => $col1);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  [
      'head' => $data,
      'islocked' => false,
      'isposted' => false,
      'status' => true,
      'isnew' => true,
      'msg' => 'Ready for New Ledger',
      'gridname' => []
    ];
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['code'] = '';
    $data[0]['description'] = '';
    return $data;
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $artid = $this->othersClass->val($config['params']['clientid']);
    $center = $config['params']['center'];
    $fields = "s.artid as clientid, s.code as client";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',s.' . $value;
    }

    // if ($artid == 0) $artid = $this->getlastclient();

    $qryselect = "select " . $fields;
    $qry = $qryselect . " from " . $this->head . " as s
        where s.artid =?";

    $head = $this->coreFunctions->opentable($qry, [$artid]);
    if (!empty($head)) {
      $stock = $this->openstock($artid, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'griddata' => ['inventory' => $stock]];
    } else {
      $head = $this->resetdata();

      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...', 'griddata' => ['inventory' => []]];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['code']);
    } else {
      $data['code'] = $head['client'];
      $head['code'] = $head['client'];
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (isset($head[$key])) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['artid' => $head['clientid']]);
      $clientid = $head['clientid'];
      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'UPDATE' . ' - ' . $data['code'] . ' - ' . $data['description']
      );
    } else {
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);
      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE' . ' - ' . $data['code'] . ' - ' . $data['description']
      );
    }
    $stock = $this->openstock($clientid, $config);
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid, 'griddata' => ['inventory' => $stock]];
  } // end function

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select code as value 
        from " . $this->head . " 
        order by code DESC LIMIT 1");

    return $last_id;
  }

  public function openstock($trno, $config)
  {
    $qry = 'select artid as trno,artid,line,section,description,d1a,d1b,d2a,d2b,d3a,d3b,d4a,d4b,d5a,d5b,"" as bgcolor from codedetail where artid = ?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function openstockline($config)
  {

    if (isset($config['params']['trno'])) {
      $line = $config['params']['line'];
      $trno = $config['params']['trno'];
    } else {
      $trno = $config['params']['row']['trno'];
      $line = $config['params']['row']['line'];
    }

    $qry = 'select artid as trno,artid,line,section,description,d1a,d1b,d2a,d2b,d3a,d3b,d4a,d4b,d5a,d5b,"" as bgcolor from codedetail where artid=? and line=?';
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  } // end function

  public function addrow($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['artid'] = $config['params']['trno'];
    $data['trno'] = $config['params']['trno'];
    $data['section'] = '';
    $data['description'] = '';
    $data['d1a'] = '';
    $data['d1b'] = 0;
    $data['d2a'] = '';
    $data['d2b'] = 0;
    $data['d3a'] = '';
    $data['d3b'] = 0;
    $data['d4a'] = '';
    $data['d4b'] = 0;
    $data['d5a'] = '';
    $data['d5b'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function stockstatusposted($config)
  {
    return 0;
  }

  public function stockstatus($config)
  {

    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $artid = $config['params']['trno'];
    $line = $config['params']['data']['line'];
    $description = $config['params']['data']['description'];
    $section = $config['params']['data']['section'];
    $d1a = $config['params']['data']['d1a'];
    $d1b = $config['params']['data']['d1b'];
    $d2a = $config['params']['data']['d2a'];
    $d2b = $config['params']['data']['d2b'];
    $d3a = $config['params']['data']['d3a'];
    $d3b = $config['params']['data']['d3b'];
    $d4a = $config['params']['data']['d4a'];
    $d4b = $config['params']['data']['d4b'];
    $d5a = $config['params']['data']['d5a'];
    $d5b = $config['params']['data']['d5b'];

    $data = [
      'artid' => $artid,
      'line' => $line,
      'section' => $section,
      'description' => $description,
      'd1a' => $d1a,
      'd1b' => $d1b,
      'd2a' => $d2a,
      'd2b' => $d2b,
      'd3a' => $d3a,
      'd3b' => $d3b,
      'd4a' => $d4a,
      'd4b' => $d4b,
      'd5a' => $d5a,
      'd5b' => $d5b
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where artid=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$artid]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;

      if ($this->coreFunctions->sbcinsert($this->detail, $data)) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } elseif ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      return $this->coreFunctions->sbcupdate($this->detail, $data, ['artid' => $artid, 'line' => $data['line']]);
    }
  } // end function

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

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


  public function deletetrans($config)
  {
    $artid = $config['params']['clientid'];
    $qry1 = "select artcode as value from notice_explain where artid=? limit 1";
    $count = $this->coreFunctions->datareader($qry1, [$artid]);
    $qry1 = "select artcode as value from hnotice_explain where artid=? limit 1";
    $count1 = $this->coreFunctions->datareader($qry1, [$artid]);
    $qry1 = "select docno as value from disciplinary where artid=? limit 1";
    $count2 = $this->coreFunctions->datareader($qry1, [$artid]);
    $qry1 = "select docno as value from hdisciplinary where artid=? limit 1";
    $count3 = $this->coreFunctions->datareader($qry1, [$artid]);

    if ($count != '' || $count1 != '' || $count2 != '' || $count3 != '') {
      return ['clientid' => $artid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where artid=?', 'delete', [$artid]);
    $this->coreFunctions->execqry('delete from codedetail where artid=?', 'delete', [$artid]);

    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
