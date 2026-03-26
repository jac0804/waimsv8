<?php

namespace App\Http\Classes\modules\receivable;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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
use App\Http\Classes\builder\helpClass;

class dc
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Daily Collection Report';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'dchead';
  public $hhead = 'hdchead';
  public $detail = 'dcdetail';
  public $hdetail = 'hdcdetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $defaultContra = 'AR1';

  private $fields = ['trno', 'docno', 'dateid', 'collector'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4409,
      'edit' => 4411,
      'new' => 4412,
      'save' => 4413,
      'delete' => 4414,
      'print' => 4415,
      'lock' => 4416,
      'unlock' => 4417,
      'post' => 4418,
      'unpost' => 4419,
      'additem' => 4420,
      'edititem' => 4421,
      'deleteitem' => 4422
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listcollector = 3;
    $listdate = 4;
    $postdate = 5;

    $getcols = ['action', 'liststatus', 'listdocument', 'listcollector', 'listdate', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listcollector]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$postdate]['label'] = 'Post Date';
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
    $limit = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.collector', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.trno, head.docno, head.collector, left(head.dateid,10) as dateid, 'DRAFT' as status,
      head.createby, head.editby, head.viewby, num.postedby, date(num.postdate) as postdate
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno, head.docno, head.collector, left(head.dateid,10) as dateid, 'POSTED' as status,
     head.createby, head.editby, head.viewby, num.postedby, date(num.postdate) as postdate
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    $orderby $limit";
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
      'toggledown',
      'help',
      'others'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];
    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {

    return [];
  }


  public function createTab($access, $config)
  {
    $action = 0;
    $client = 1;
    $clientname = 2;
    $amount = 3;

    $columns = ['action', 'client', 'clientname', 'amount'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = ['clientname', 'client', 'Customer'];
    $obj[0][$this->gridname]['columns'][$client]['label'] = 'Code';
    $obj[0][$this->gridname]['columns'][$client]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amount]['label'] = 'Amount Due';
    $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['unpaidpercust', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "DELETE DETAILS";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'collector'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dateid', 'isinclude'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['collector'] = '';
    $data[0]['isinclude'] = '0';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      } else {
        $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
        if ($t == '') {
          $trno = 0;
        }
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

    $qry = "select head.trno, head.docno, head.collector, case when head.isinclude=1 then '1' else '0' end as isinclude, num.center, date(head.dateid) as dateid
      from " . $table . " as head left join " . $tablenum . " as num on num.trno=head.trno
      where head.trno=? and num.doc=? and num.center=?
      union all
      select head.trno, head.docno, head.collector, case when head.isinclude=1 then '1' else '0' end as isinclude, num.center, date(head.dateid) as dateid
      from " . $htable . " as head left join " . $tablenum . " as num on num.trno=head.trno
      where head.trno=? and num.doc=? and num.center=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->opendetail($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'griddata' => [$this->gridname => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => [$this->gridname => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
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
    $data['isinclude'] = 0;
    if ($head['isinclude']) $data['isinclude'] = 1;
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['collector']);
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
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', 'trno=?', [$trno]);
    if ($this->othersClass->isposted($config)) return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, collector, isinclude, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser) select trno, doc, docno, dateid, collector, isinclude, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser from " . $this->head . " where trno=" . $trno;
    if ($this->coreFunctions->execqry($qry, 'insert')) {
      $qry = "insert into " . $this->hdetail . "(trno, line, amount, client, encodeddate, encodedby, editdate, editby) select trno, line, amount, client, encodeddate, encodedby, editdate, editby from " . $this->detail . " where trno=" . $trno;
      if ($this->coreFunctions->execqry($qry, 'insert')) {
        $data = ['postdate' => $this->othersClass->getCurrentTimeStamp(), 'postedby' => $user, 'tmpuser' => ''];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=" . $trno, 'delete');
        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=" . $trno, 'delete');
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=" . $trno, 'delete');
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Kindly check the detail.'];
      }
    } else {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Kindly check the head data.'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $docno = $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', 'trno=?', [$trno]);
    if (!$this->othersClass->isposted($config)) return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, Already unposted...'];
    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, collector, isinclude, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser) select trno, doc, docno, dateid, collector, isinclude, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser from " . $this->hhead . " where trno=" . $trno;
    if ($this->coreFunctions->execqry($qry, 'insert')) {
      $qry = "insert into " . $this->detail . "(trno, line, amount, client, encodeddate, encodedby, editdate, editby) select trno, line, amount, client, encodeddate, encodedby, editdate, editby from " . $this->hdetail . " where trno=" . $trno;
      if ($this->coreFunctions->execqry($qry, 'insert')) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, postedby='' where trno=" . $trno, 'update');
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=" . $trno, 'delete');
        $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=" . $trno, 'delete');
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=" . $trno, 'delete');
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. Kindly check the detail.'];
      }
    } else {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. Kindly check the head data.'];
    }
  } //end function

  public function opendetail($trno, $config)
  {
    $qry = "select d.trno, d.line, format(d.amount,2) as amount, d.client, client.clientname, '' as bgcolor, format(d.amount,2) as db, '0.00' as cr
      from " . $this->detail . " as d left join client on client.client=d.client
      where d.trno=?
      union all
      select d.trno, d.line, format(d.amount,2) as amount, d.client, client.clientname, '' as bgcolor, format(d.amount,2) as db, '0.00' as cr
      from " . $this->hdetail . " as d left join client on client.client=d.client
      where d.trno=? order by line";
    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  }

  public function opendetailline($config)
  {
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select d.trno, d.line, format(d.amount,2) as amount, d.client, client.clientname, '' as bgcolor, format(d.amount,2) as db, '0.00' as cr
      from " . $this->detail . " as d left join client on client.client=d.client
      where d.trno=? and d.line=?";
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'adddetail':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all detail edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'getunpaidselected':
        return $this->getunpaidselected($config);
        break;
      case 'generateewt':
        return $this->generateewt($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->opendetailline($config);
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $isupdate = $this->additem('update', $config);
      if ($isupdate['status'] == false) {
        break;
      }
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key]['db'] == 0 && $data2[$key]['cr'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Some entries have zero value both debit and credit ';
        } else {
          $msg2 = ' Reference Amount is lower than encoded amount ';
        }
      }
    }
    if ($isupdate['status']) {
      return [$this->gridname => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($isupdate['msg'] == '') {
        return [$this->gridname => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return [$this->gridname => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    return [$this->gridname => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    $trno = $config['params']['trno'];
    $client = $config['params']['data']['client'];
    $amount = $config['params']['data']['amount'];

    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }
    $data = [
      'trno' => $trno,
      'line' => $line,
      'client' => $client,
      'amount' => $amount
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'ADD - Line:' . $line . ' Client:' . $client . ' Amount:' . $amount);
        $row = $this->opendetailline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]);
      return ['status' => $return, 'msg' => ''];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL DETAILS');
    return ['status' => true, 'msg' => 'Successfully deleted.', $this->gridname => []];
  }



  public function deleteitem($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $amount = $config['params']['row']['amount'];
    $client = $config['params']['row']['client'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Code:' . $client . ' amount:' . $amount);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function getunpaidselected($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $config['params']['data']['client'] = $data[$key]['client'];
      $config['params']['data']['amount'] = $data[$key]['ar'];

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Accounts Successfull...'];
  } //end function

  public function generateewt($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];
    $status = true;
    $msg = '';
    $entry = [];
    $vatrate = 0;
    $vatrate2 = 0;
    $vatvalue = 0;
    $ewtvalue = 0;
    $dbval = 0;
    $crval = 0;
    $db = 0;
    $cr = 0;
    $damt = 0;
    $line = 0;
    $forex = $data[0]['forex'];
    $cur = $data[0]['cur'];
    $ap = 0;
    $exp = 0;
    $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['WT1']);
    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);

    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT";
    } else {

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");

      foreach ($data as $key => $value) {
        if ($value['isvat'] == true or $value['isewt'] == true or $value['isvewt'] == true) {
          $damt   = $value['damt'];

          if ($value['isvewt'] == 'true') { //for vewt
            if (floatval($value['db']) != 0) {
              $dbval = $damt;
              $ewtvalue = $ewtvalue + (($dbval / 1.12) * ($value['ewtrate'] / 100));
            } else {
              $crval = $damt;
              $ewtvalue = $ewtvalue + ((($crval / 1.12) * ($value['ewtrate'] / 100)) * -1);
            }
          }

          if ($value['isvat']  == 'true') { //for vat computation
            $vatrate = 1.12;
            $vatrate2 = .12;

            if (floatval($value['db']) != 0) {
              $dbval = $damt / $vatrate;
              $vatvalue = $vatvalue + ($dbval * $vatrate2);
            } else {
              $crval = $damt / $vatrate;
              $vatvalue =  $vatvalue + (($crval * $vatrate2) * -1);
            }
          }

          if ($value['isewt']  == 'true') { //for ewt
            if (floatval($value['db']) != 0) {
              if ($value['isvat'] == 'true') {
                $dbval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              } else {
                $dbval = $damt;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              }
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              } else {
                $crval = $damt;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              }
            }
          }

          $exp = $exp + ($dbval - $crval);

          $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");
          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment Amount is greater than Amount Setup";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
            }
          }
        }
      }

      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;


      if ($vatvalue != 0) {
        $entry = [
          'line' => $line, 'acnoid' => $taxacno, 'client' => $data[0]['client'], 'cr' => ($vatvalue < 0 ? abs(round($vatvalue, 2)) : 0), 'db' => ($vatvalue < 0 ? 0 : abs(round($vatvalue, 2))), 'postdate' => $data[0]['dateid'], 'fdb' => ($vatvalue < 0 ? 0 : abs($vatvalue)) * $forex, 'fcr' => ($vatvalue < 0 ? abs($vatvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex
        ];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }



      if ($ewtvalue != 0 && $status == true) {
        $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }

      $ap = ($exp + $vatvalue) - $ewtvalue;


      if ($ap != 0 && $status == true) {
        $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP2']);
        $entry = ['line' => $line, 'acnoid' => $apacno, 'client' => $data[0]['client'], 'cr' => ($ap < 0 ? 0 : abs(round($ap, 2))), 'db' => ($ap < 0 ? abs(round($ap, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ap > 0 ? 0 : abs($ap)) * $forex, 'fcr' => ($ap > 0 ? abs($ap) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      if (!empty($this->acctg)) {
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        foreach ($this->acctg as $key => $value) {
          foreach ($value as $key2 => $value2) {
            $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
          }

          $this->acctg[$key]['editdate'] = $current_timestamp;
          $this->acctg[$key]['editby'] = $config['params']['user'];
          $this->acctg[$key]['encodeddate'] = $current_timestamp;
          $this->acctg[$key]['encodedby'] = $config['params']['user'];
          $this->acctg[$key]['trno'] = $config['params']['trno'];
        }

        if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
          $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
          $status = true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
          $status = false;
        }
      }
    }

    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  } //end function

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //AR
    $crqry = "
    select  head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    where head.trno = ?";
    $crdata = $this->coreFunctions->opentable($crqry, [$config['params']['trno']]);
    if (!empty($crdata)) {
      $startx = 550;
      $a = 0;
      foreach ($crdata as $key2 => $value2) {
        data_set(
          $nodes,
          'cr',
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => 100,
            'w' => 250,
            'h' => 80,
            'type' => $crdata[$key2]->docno,
            'label' => $crdata[$key2]->rem,
            'color' => 'red',
            'details' => [$crdata[$key2]->dateid]
          ]
        );
        array_push($links, ['from' => 'cr', 'to' => 'sj']);

        //CR
        $qry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ?
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ?";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($t)) {
          data_set(
            $nodes,
            'sj',
            [
              'align' => 'left',
              'x' => $startx,
              'y' => 100,
              'w' => 250,
              'h' => 80,
              'type' => $t[0]->docno,
              'label' => $t[0]->rem,
              'color' => 'green',
              'details' => [$t[0]->dateid]
            ]
          );
        }
      }
    }



    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
