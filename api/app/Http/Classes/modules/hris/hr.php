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

class hr
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RETURN OF ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'returnitemhead';
  public $hhead = 'hreturnitemhead';
  public $detail = 'returnitemdetail';
  public $hdetail = 'hreturnitemdetail';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $hrislookup;

  private $fields = [
    'trno', 'docno', 'empid', 'deptid', 'dateid', 'jobtitle', 'rem', 'dept'
  ];
  private $except = ['trno'];
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
      'view' => 1159,
      'edit' => 1160,
      'new' => 1161,
      'save' => 1162,
      'change' => 1163,
      'delete' => 1164,
      'print' => 1165,
      'post' => 1166,
      'unpost' => 1167,
      'lock' => 1678,
      'unlock' => 1679,
      'additem' => 1333,
      'edititem' => 1334,
      'deleteitem' => 1335
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;text-align:left';
    $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$empcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$empname]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
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
      $searchfield = ['h.docno', 'c.clientname', 'c.client'];
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
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, 
    c.client as empcode, c.clientname as empname, 'DRAFT' as status
    from " . $this->head . " as h left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, 
    c.client as empcode, c.clientname as empname, 'POSTED' as status
    from " . $this->hhead . " as h left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
    $columns = ['action', 'itemname', 'amt', 'rem', 'ref'];
    foreach ($columns as $key => $value) {
      $$value = $key;
    }
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['label'] = 'ITEMS';
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:550px;whiteSpace: normal;min-width:550px;";
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][$ref]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Description";
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Estimated Value";
    $obj[0][$this->gridname]['columns'][$ref]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$ref]['readonly'] = true;
    $obj[0][$this->gridname]['descriptionrow'] = [];

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
    $tbuttons = ['addrow', 'pendingturnoveritems', 'saveitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "ADD ITEM";
    $obj[1]['label'] = "TURN OVER OF ITEMS";
    $obj[1]['action'] = "pendingturnoveritems";
    $obj[2]['label'] = "SAVE ALL";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'empcode', 'empname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'empcode.action', 'lookupemployee');

    $fields = ['dateid', 'jobtitle', 'dept'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dept.type', 'input');
    data_set($col2, 'dept.readonly', true);

    $fields = ['rem'];
    $col3 = $this->fieldClass->create($fields);

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
    $data[0]['rem'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $this->othersClass->val($config['params']['trno']);
    $center = $config['params']['center'];

    if ($trno == 0) $trno = $this->getlasttrno();
    $config['params']['trno'] = $trno;

    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $qryselect = "select 
                    num.trno, 
                    num.docno, 
                    head.empid,
                    em.client as empcode, 
                    em.clientname as empname, 
                    head.deptid,
                    d.client as dept, 
                    head.dateid,  
                    head.jobtitle, 
                    head.rem
                ";
    $qry = $qryselect . " from " . $table . " as head  
                left join client as em on em.clientid=head.empid
                left join client as d on d.clientid=head.deptid
                left join $tablenum as num on num.trno = head.trno   
                where num.trno = ? and num.doc='" . $doc . "' and num.center=? 
                union all " . $qryselect . " from " . $htable . " as head
                left join client as em on em.clientid=head.empid
                left join client as d on d.clientid=head.deptid
                left join $tablenum as num on num.trno = head.trno   
                where num.trno = ? and num.doc='" . $doc . "' and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $detail = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function getlasttrno()
  {
    $last_id = $this->coreFunctions->datareader("
        select trno as value 
        from " . $this->head . " 
        union all
        select trno as value 
        from " . $this->hhead . " 
        order by value DESC LIMIT 1");

    return $last_id;
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

    $hotrno = $this->coreFunctions->datareader("select refx as value from " . $this->detail . ' where trno=?', [$trno], '', true);

    if ($hotrno != 0) {
      $this->coreFunctions->execqry("update hturnoveritemdetail set qa=0 where trno=?", 'update', [$hotrno]);
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];


    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into " . $this->hhead . " (docno, deptid, empid, jobtitle, rem, trno, dateid,createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc)
                select docno, deptid, empid, jobtitle, rem, trno, dateid,createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc from " . $this->head . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {

      $qry = "insert into " . $this->hdetail . " (trno, line, itemname, amt, rem, refx, linex, ref) select trno, line, itemname, amt, rem, refx, linex, ref from " . $this->detail . " where trno=?";
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

    $qry = "insert into " . $this->head . " (docno, deptid, empid, jobtitle, rem, trno, dateid,createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc)
    select docno, deptid, empid, jobtitle, rem, trno, dateid,createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, doc from " . $this->hhead . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {

      $qry = "insert into " . $this->detail . " (trno, line, itemname, amt, rem, refx, linex, ref) select trno, line, itemname, amt, rem, refx, linex, ref from " . $this->hdetail . " where trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($result === 1) {
      } else {
        $msg = "Unposting failed. Kindly check detail.";
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
    $qry = "line,trno,itemname,amt,rem,ref,'' as bgcolor";
    return $qry;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = "select " . $sqlselect . " 
      FROM $this->detail as detail
      where detail.trno =?
      UNION ALL
      select " . $sqlselect . "
      FROM $this->hdetail as detail
       where detail.trno =? ";


    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = "select " . $sqlselect . "
        FROM $this->detail 
        where trno = ? and line = ? ";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function


  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['itemname'] = '';
    $data['rem'] = '';
    $data['amt'] = '0.00';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
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
      case 'getturnoveritems':
        return $this->pendingturnoveritems($config);
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
    $itemname = $config['params']['data']['itemname'];
    $trno = $config['params']['trno'];
    $rem = $config['params']['data']['rem'];
    $amt = $config['params']['data']['amt'];
    $line = $config['params']['data']['line'];

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemname' => $itemname,
      'amt' => $amt,
      'rem' => $rem
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
          'ADD ITEM',
          'ITEM - Line:' . $line
            . ' ITEMNAME: ' . $itemname
            . ' AMOUNT: ' . $amt
            . ' NOTES: ' . $rem
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

    $reflinex = $this->coreFunctions->opentable("select refx, linex from returnitemdetail where trno=? and line = ?", [$trno, $line]);
    $update = "update hturnoveritemdetail set qa = 0 where trno =? and line = ?";
    $this->coreFunctions->execqry($update, 'update', [$reflinex[0]->refx, $reflinex[0]->linex]);

    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'ITEM', 'REMOVED - Line:' . $line . ' ITEMNAME:' . $data[0]->itemname);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function pendingturnoveritems($config)
  {
    $id = $config['params']['trno'];
    $row = $config['params']['rows'];
    $doc = $config['params']['doc'];
    $data = [];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
      from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");

    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['trno'] = $id;
      $config['params']['row']['linex'] = $value['line'];
      $config['params']['row']['refx'] = $value['trno'];
      $config['params']['row']['ref'] = $value['docno'];
      $config['params']['row']['itemname'] = $value['itemname'];
      $config['params']['row']['amt'] = $value['amt'];
      $config['params']['row']['rem'] = $value['rem'];
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } // end function

  public function save($config, $row = 'row')
  {
    $data = [];
    $row = $config['params'][$row];
    $doc = $config['params']['doc'];
    $stockfields = ['itemname', 'amt', 'rem', 'ref', 'refx', 'linex'];
    foreach ($stockfields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['trno'] = $config['params']['trno'];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
      from hrisnum where trno = '" . $data['trno'] . "' and postdate is not null and doc = '$doc'");

    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!"];
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

        if ($data["refx"] != 0 && $data["linex"] != 0) {
          $this->setserved($data["refx"], $data["linex"], 1);
        }
        $config['params']['line'] = $line;
        $returnrow = $this->openstockline($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $data['trno'], 'line' => $row['line']]) == 1) {
        $config['params']['line'] = $row['line'];
        $returnrow = $this->openstockline($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function


  private function setserved($refx, $linex, $type)
  {

    if ($type == 0) {
      $qry = "update hturnoveritemdetail set qa=qa-1 where trno=? and line=?";
    } else {
      $qry = "update hturnoveritemdetail set qa=qa+1 where trno=? and line=?";
    }

    if ($this->coreFunctions->execqry($qry, 'update', [$refx, $linex]) == 1) {
    } else {
    }
  }

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
