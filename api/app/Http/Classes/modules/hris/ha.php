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

class ha
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'REQUEST TRAINING AND DEVELOPMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'traindev';
  public $hhead = 'htraindev';
  public $detail = '';
  public $hdetail = '';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IS1';

  private $fields = [
    'trno', 'docno', 'empid', 'dateid',
    'jobtitle', 'type', 'title',
    'venue', 'date1', 'date2', 'purpose', 'budget', 'deptid'
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1129,
      'edit' => 1130,
      'new' => 1131,
      'save' => 1132,
      'change' => 1133,
      'delete' => 1134,
      'print' => 1135,
      'post' => 1136,
      'unpost' => 1137,
      'lock' => 1674,
      'unlock' => 1675
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname', 'title'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;text-align:left';
    $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$empcode]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[$empname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[$title]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';

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
      $searchfield = ['h.docno', 'c.clientname', 'c.client', 'h.title'];
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
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, h.title, 'DRAFT' as status
    from " . $this->head . " as h 
    left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and 
    CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, h.title, 'POSTED' as status
    from " . $this->hhead . " as h 
    left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and 
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
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton 

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'empcode', 'empname', 'dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'empcode.action', 'lookupemployee');

    $fields = ['jobtitle', 'dept', 'type', 'title'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'jobtitle.class', 'csjobtitle sbccsreadonly');
    data_set($col2, 'dept.type', 'input');
    data_set($col2, 'dept.class', 'csdept sbccsreadonly');


    $fields = ['venue', 'start', 'end', 'purpose', 'budget'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'start.name', 'date1');
    data_set($col3, 'end.name', 'date2');

    data_set($col3, 'venue.type', 'cinput');
    data_set($col3, 'purpose.type', 'cinput');
    data_set($col3, 'budget.type', 'cinput');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
    $data[0]['empid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['jobtitle'] = '';
    $data[0]['type'] = '';
    $data[0]['title'] = '';
    $data[0]['venue'] = '';
    $data[0]['date1'] = $this->othersClass->getCurrentDate();
    $data[0]['date2'] = $this->othersClass->getCurrentDate();
    $data[0]['purpose'] = '';
    $data[0]['budget'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';

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
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc='HA' and center=? order by trno desc limit 1", [$doc, $center]);
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
      dept.client as dept, head.trno, head.docno, 
      head.empid, head.dateid, head.empname,
      head.jobtitle, head.type, head.title,
      head.venue, head.date1, head.date2, 
      head.purpose, head.budget,head.deptid,
      emp.client as empcode,
      emp.clientname as empname
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join $tablenum as num on num.trno = head.trno
    left join client as emp on emp.clientid=head.empid
    left join client as dept on dept.clientid=head.deptid
    where num.trno = ? and num.doc='HA' and num.center=? 
    union all
    " . $qryselect . " from " . $htable . " as head
      left join $tablenum as num on num.trno = head.trno
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      where num.trno = ? and num.doc='HA' and num.center=? 
    ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = [];
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
    $qry = "insert into htraindev (trno, docno, empid, dateid, empname,
      jobtitle, department, type, title,
      venue, date1, date2, purpose, budget,
      createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby, doc, deptid)
      select trno, docno, empid, dateid, empname,
      jobtitle, department, type, title,
      venue, date1, date2, purpose, budget,
      createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby,
      doc, deptid
      from traindev where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
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

    $qry = "insert into traindev (trno, docno, empid, dateid, empname,
      jobtitle, department, type, title,
      venue, date1, date2, purpose, budget,
      createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby, doc, deptid)
          select trno, docno, empid, dateid, empname,
      jobtitle, department, type, title,
      venue, date1, date2, purpose, budget,
      createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby,
      doc, deptid
      from htraindev where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
    } else {
      $msg = "Unposting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
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
