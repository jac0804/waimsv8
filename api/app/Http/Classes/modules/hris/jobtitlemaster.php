<?php

namespace App\Http\Classes\modules\hris;

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

class jobtitlemaster
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'JOB TITLE MASTER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'jobthead';
  public $detail = 'jobtdesc';
  public $prefix = 'JT';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = '';
  private $stockselect;
  private $tablenum;

  private $fields = [
    'line',
    'jobtitle',
    'docno'
  ];
  private $except = ['clientid', 'client'];
  private $blnfields = [];
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
      'view' => 1271,
      'edit' => 1272,
      'new' => 1273,
      'save' => 1274,
      'change' => 1717,
      'delete' => 1275,
      'print' => 1718,
      'load' => 1270,
      'additem' => 1341,
      'edititem' => 1342,
      'deleteitem' => 1343
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listdocument', 'jobtitle'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[2]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
    $cols[1]['label'] = 'Code';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['line', 'docno', 'jobtitle'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $qry = "select line as clientid, docno, jobtitle from jobthead 
        where 1=1 " . $filtersearch . "
        order by jobtitle";
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
    $companyid = $config['params']['companyid'];
    $tab = [];

    $tab = [
      'jobdesctab' => ['action' => 'hrisentry', 'lookupclass' => 'entryjobdesc', 'label' => 'JOB DESCRIPTION']
    ];

    if ($companyid != 58) { //cdo
      $tab['skilldesctab'] = ['action' => 'hrisentry', 'lookupclass' => 'entryskillreq', 'label' => 'SKILL REQUIREMENTS'];
    }

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = [];

    $skill = $this->tabClass->createtab($tab, []);

    $return = [];
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
    $fields = [
      'client',
      'jobtitle'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupledgerjobtitle');

    data_set($col1, 'jobtitle.readonly', false);
    data_set($col1, 'jobtitle.required', true);
    data_set($col1, 'jobtitle.type', 'ctextarea');

    return array('col1' => $col1);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['docno'] = '';
    $data[0]['jobtitle'] = '';

    return $data;
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $this->othersClass->val($config['params']['clientid']);
    $center = $config['params']['center'];
    $fields = "line as clientid, docno as client";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',' . $value;
    }

    if ($clientid == 0) $clientid = $this->getlastclient();

    $qryselect = "select " . $fields;
    $qry = $qryselect . " from jobthead
        where line = ? ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      $stock = $this->openstock($clientid, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      return  ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'griddata' => ['inventory' => $stock]];
    } else {
      $head = $this->resetdata();

      return ['reloadtableentry' => true, 'status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    } else {
      $data['docno'] = $head['client'];
      $head['docno'] = $head['client'];
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
      $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
      $clientid = $head['clientid'];
    } else {
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);
    }

    $stock = $this->openstock($clientid, $config);
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid, 'griddata' => ['inventory' => $stock]];
  } // end function

  public function getlastclient($pref = '')
  {
    $length = strlen($pref);
    if ($length == 0) {
      $last_id = $this->coreFunctions->datareader("select docno as value from " . $this->head . " order by line DESC LIMIT 1");
    } else {
      $last_id = $this->coreFunctions->datareader("select docno as value from " . $this->head . " where left(docno,?)=? order by line DESC LIMIT 1", [$length, $pref]);
    }
    return $last_id;
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $docn = $this->coreFunctions->getfieldvalue('jobthead', 'docno', 'line=?', [$clientid]);
    $qry1 = "select job as value from personreq where job=? limit 1";
    $count = $this->coreFunctions->datareader($qry1, [$docn]);
    $qry1 = "select job as value from hpersonreq where job=? limit 1 ";
    $count1 = $this->coreFunctions->datareader($qry1, [$docn]);
    $qry1 = "select emptitle as value from joboffer where emptitle=? limit 1";
    $count2 = $this->coreFunctions->datareader($qry1, [$docn]);
    $qry1 = "select emptitle as value from hjoboffer where emptitle=? limit 1";
    $count3 = $this->coreFunctions->datareader($qry1, [$docn]);


    if ($count != '' || $count1 != '' || $count2 != '' || $count3 != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where line=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from jobtskills where trno=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from jobtdesc where trno=?', 'delete', [$clientid]);

    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function openstock($trno, $config)
  {
    $qry = 'select line, trno, description, "" as bgcolor from jobtdesc where trno=?';

    return $this->coreFunctions->opentable($qry, [$trno]);
  } //end function

  public function openstockline($config)
  {

    if (isset($config['params']['trno'])) {
      $line = $config['params']['line'];
      $trno = $config['params']['trno'];
    } else {
      $trno = $config['params']['row']['trno'];
      $line = $config['params']['row']['line'];
    }
    $qry = 'select line, trno, description,"" as bgcolor from jobtdesc where trno=? and line=?';
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  } // end function


  public function addrow($config)
  {
    $data = [];
    $data['trno'] = $config['params']['trno'];
    $data['line'] = 0;
    $data['description'] = '';
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
    $trno = $config['params']['trno'];
    $description = $config['params']['data']['description'];
    $line = $config['params']['data']['line'];

    $data = [
      'trno' => $trno,
      'line' => $line,
      'description' => $description
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;

      if ($this->coreFunctions->sbcinsert($this->detail, $data)) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        $this->logger->sbcmasterlog(
          $trno,
          $config,
          ' CREATE - LINE: ' . $line . ''
            . ', DESCRIPTION: ' . $description
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } elseif ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      return $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $data['line']]);
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
