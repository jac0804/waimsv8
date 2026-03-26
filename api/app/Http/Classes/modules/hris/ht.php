<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class ht
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TRAINING ENTRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'traininghead';
  public $hhead = 'htraininghead';
  public $detail = 'trainingdetail';
  public $hdetail = 'htrainingdetail';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';


  private $fields = [
    'trno',
    'docno',
    'dateid',
    'title',
    'ttype',
    'venue',
    'tdate1',
    'tdate2',
    'speaker',
    'amt',
    'cost',
    'attendees',
    'remarks',
    'reqtrain'
  ];
  private $except = ['trno'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
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
    $this->reporter = new SBCPDF;
    $this->hrislookup = new hrislookup;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1139,
      'edit' => 1140,
      'new' => 1141,
      'save' => 1142,
      'change' => 1143,
      'delete' => 1144,
      'print' => 1145,
      'post' => 1146,
      'unpost' => 1147,
      'lock' => 1676,
      'unlock' => 1677,
      'changeamt' => 1140,
      'additem' => 1141,
      'edititem' => 1140,
      'deleteitem' => 841
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'title', 'ttype'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;text-align:left';
    $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$title]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$ttype]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;text-align:left';

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['h.docno', 'h.title', 'h.ttype'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, 'DRAFT' as status,h.ttype,h.title
    from " . $this->head . " as h  
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and 
    CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, 'POSTED' as status,h.ttype,h.title
    from " . $this->hhead . " as h  
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and 
    CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    order by docno desc";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);

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
      'post',
      'unpost',
      'lock',
      'unlock',
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
    $columns = ['action', 'empcode', 'empname', 'notes'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$empcode]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$empname]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
    $obj[0][$this->gridname]['columns'][$notes]['style'] = "width:750px;whiteSpace: normal;min-width:750px;";
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['label'] = 'EMPLOYEE';

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addempgrid', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['action'] = 'addempgrid';
    $obj[1]['label'] = 'Save all';
    $obj[2]['label'] = 'Delete all';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'reqtrainname', 'ttype', 'title', 'venue'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'title.type', 'cinput');
    data_set($col1, 'venue.type', 'cinput');

    $fields = ['dateid', 'tdate1', 'tdate2', 'speaker'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'speaker.type', 'cinput');

    $fields = ['amt', 'cost', 'attendees'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'amt.label', 'Budget per Employee');

    data_set($col3, 'amt.type', 'cinput');
    data_set($col3, 'cost.type', 'cinput');
    data_set($col3, 'attendees.type', 'ctextarea');

    $fields = ['remarks'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'remarks.type', 'ctextarea');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    return $this->resetdata($docno);
  }

  public function resetdata($docno = '')
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['ttype'] = '';
    $data[0]['title'] = '';
    $data[0]['venue'] = '';
    $data[0]['tdate1'] = $this->othersClass->getCurrentDate();
    $data[0]['tdate2'] = $this->othersClass->getCurrentDate();
    $data[0]['speaker'] = '';
    $data[0]['amt'] = 0;
    $data[0]['cost'] = 0;
    $data[0]['attendees'] = '';
    $data[0]['remarks'] = '';
    $data[0]['reqtrain'] = 0;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc='TR' and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $qryselect = "select 
    head.trno, 
    head.docno, 
    head.dateid, 
    head.ttype,
    head.title, 
    head.venue, 
    head.tdate1, 
    head.tdate2, 
    head.speaker, 
    head.amt,
    head.cost, 
    head.attendees, 
    head.remarks,
    head.reqtrain,
    req.docno as reqtrainname
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join $tablenum as num on num.trno = head.trno 
    left join htraindev as req on req.trno = head.reqtrain  
    where num.trno = ? and num.doc='HT' and num.center=? 
    union all " . $qryselect . " from " . $htable . " as head
    left join $tablenum as num on num.trno = head.trno 
    left join htraindev as req on req.trno = head.reqtrain  
    where num.trno = ? and num.doc='HT' and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
    }
  } // end function  

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into htraininghead (trno, docno, dateid, title, ttype, venue, tdate1, tdate2,
    speaker, amt, cost, attendees,remarks, createby, createdate, editby, 
    editdate, lockdate, lockuser, viewdate, viewby, doc, reqtrain)
    select trno, docno, dateid, title, ttype, venue, tdate1, tdate2, speaker, amt, cost, attendees, 
    remarks, createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, 
    doc, reqtrain from traininghead where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {

      $qry = "insert into " . $this->hdetail . " (trno, line, empid, empname, notes) select trno, line, empid,empname, notes from " . $this->detail . " where trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
      } else {
        $msg = "Posting failed. Kindly check the detail.";
      }
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = '';

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into traininghead (trno, docno, dateid, title, ttype, venue, tdate1, tdate2, speaker, amt, cost, attendees, remarks, createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc, reqtrain)
    select trno, docno, dateid, title, ttype, venue, tdate1, tdate2, speaker, amt, cost, attendees, remarks, createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc, reqtrain from htraininghead where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {

      $qry = "insert into " . $this->detail . " (trno, line, empid, empname, notes) select trno, line, empid, empname, notes from " . $this->hdetail . " where trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
      } else {
        $msg = "Unposting failed. Kindly check the detail.";
      }
    } else {
      $msg = "Unposting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  private function getstockselect($config)
  {
    $qry = "select '' as bgcolor, i.trno, i.line, i.empid,c.clientid, c.client as empcode, c.clientname as empname, i.notes";
    return $qry;
  }

  public function openstock($trno, $config)
  {
    $select = $this->getstockselect($config);
    $qry = $select . " from " . $this->detail . " as i left join client as c on c.clientid=i.empid  where i.trno=?
        union all "
      . $select . " from " . $this->hdetail . " as i left join client as c on c.clientid=i.empid  where i.trno=?";

    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = $sqlselect . " 
        from " . $this->detail . " as i left join client as c on c.clientid=i.empid  
        where i.trno=? and i.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  } // end function

  public function stockstatus($config)
  {

    $lookupclass = $config['params']['action'];
    switch ($lookupclass) {
      case 'addempgrid':
        return $this->lookupcallback($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $row = $config['params']['rows'];
    $data = [];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");

    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    foreach ($row  as $key2 => $value) {
      $config['params']['data']['line'] = 0;
      $config['params']['data']['trno'] = $id;
      $config['params']['data']['empid'] = $value['clientid'];
      $config['params']['data']['empcode'] = $value['client'];
      $config['params']['data']['empname'] = $value['clientname'];
      $config['params']['data']['notes'] = '';
      $config['params']['data']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config, 'data');

      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } // end function


  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $empid = $config['params']['data']['empid'];
    $empname = $config['params']['data']['empname'];
    $notes = $config['params']['data']['notes'];

    $data = [
      'trno' => $trno,
      'line' => $line,
      'empid' => $empid,
      'empname' => $empname,
      'notes' => $notes
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
        $this->logger->sbcwritelog(
          $trno,
          $config,
          'ADD EMPLOYEE',
          'EMPLOYEE - Line:' . $line
            . ' EMPNAME: ' . $empname
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

  public function save($config, $row = 'row')
  {
    $fields = ['empid', 'empname', 'notes'];
    $data = [];
    $row = $config['params'][$row];
    $doc = $config['params']['doc'];
    $id = $config['params']['trno'];
    $line = 0;

    foreach ($fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    $data['trno'] = $config['params']['trno'];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");

    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    if ($row['line'] == 0) {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
      if (!$line) {
        $line = 0;
      }
      $line = $line + 1;
      $data["line"] = $line;
      if ($this->coreFunctions->sbcinsert($this->detail,  $data)) {
        $config['params']['line'] = $line;
        $returnrow = $this->openstockline($config);
        $this->logger->sbcwritelog(
          $data['trno'],
          $config,
          'ADD EMPLOYEE',
          'EMPLOYEE - Line:' . $line
            . ' EMPNAME: ' . $row['empname']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
        $config['params']['line'] = $line;
        $returnrow = $this->openstockline($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
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
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'DELETE',
      'EMPLOYEE - Line:' . $line
        . ' EMPNAME: ' . $data[0]->empname
    );
    return ['status' => true, 'msg' => 'Successfully deleted employee.'];
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


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

  // report startto
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
}
