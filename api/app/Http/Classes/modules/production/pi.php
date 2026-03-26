<?php

namespace App\Http\Classes\modules\production;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class pi
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PRODUCTION INSTRUCTION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pihead';
  public $hhead = 'hpihead';
  public $stock = 'pistock';
  public $hstock = 'hpistock';
  public $process = 'piprocess';
  public $hprocess = 'hpiprocess';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = [
    'trno', 'docno', 'dateid', 'due', 'yourref', 'ourref', 'rem', 'wh', 'itemid', 'qty', 'uom'
  ];
  private $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];

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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3633,
      'edit' => 3634,
      'new' => 3635,
      'save' => 3636,
      'delete' => 3637,
      'print' => 3638,
      'lock' => 3639,
      'unlock' => 3640,
      'changeamt' => 3645,
      'post' => 3641,
      'unpost' => 3642,
      'additem' => 3643,
      'edititem' => 3646,
      'deleteitem' => 3644
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $itemname = 4;
    $yourref = 5;
    $ourref = 6;
    $postdate = 7;
    $listpostedby = 8;
    $listcreateby = 8;
    $listeditby = 10;
    $listviewby = 11;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'itemname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$itemname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$itemname]['type'] = 'input';
    $cols[$itemname]['label'] = 'Item';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
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
    $limit = "limit 150";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref', 'item.itemname'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $companyid = $config['params']['companyid'];
    $dateid = "left(head.dateid,10) as dateid";
    $status = "'DRAFT'";
    $leftjoin = "";
    $leftjoin_posted = "";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $status = "'LOCKED'";
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno, head.docno, head.clientname, $dateid, 'DRAFT' as stat, head.createby, head.editby, head.viewby, num.postedby,
      date(num.postdate) as postdate, head.yourref, head.ourref, item.itemname
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     left join item on item.itemid=head.itemid
     " . $leftjoin . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     union all
     select head.trno, head.docno, head.clientname, $dateid, stat.status as stat, head.createby, head.editby, head.viewby, num.postedby,
     date(num.postdate) as postdate, head.yourref, head.ourref, item.itemname
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     left join item on item.itemid=head.itemid
     " . $leftjoin_posted . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.yourref, head.ourref, item.itemname 
     order by dateid desc,docno desc " . $limit;

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
      'help'
    );

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add item/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    return [];
  }


  public function createTab($access, $config)
  {
    $tab = ['tableentry' => [
      'action' => 'production',
      'lookupclass' => 'entrypiprocess',
      'label' => 'PROCESS'
    ]];
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
    $fields = ['docno', 'itemname', 'qty', 'luom'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'itemname.label', 'Finish Product');
    data_set($col1, 'itemname.type', 'lookup');
    data_set($col1, 'itemname.lookupclass', 'lookupitemprod');
    data_set($col1, 'itemname.action', 'lookupitem');
    data_set($col1, 'itemname.class', 'sbccsreadonly');
    data_set($col1, 'luom.lookupclass', 'piuom');
    data_set($col1, 'luom.addedparams', ['itemid']);

    $fields = ['dateid', 'whname', 'rem'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'whname.required', true);
    data_set($col2, 'whname.type', 'lookup');
    data_set($col2, 'whname.action', 'lookupclient');
    data_set($col2, 'whname.lookupclass', 'wh');

    $fields = [['yourref', 'ourref']];
    $col3 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => []];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['qty'] = '';
    $data[0]['uom'] = '';
    $data[0]['itemid'] = 0;
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $qryselect = "select num.center, head.trno, head.docno, client.client, head.ourref, left(head.dateid,10) as dateid, head.clientname, date_format(head.createdate,'%Y-%m-%d') as createdate,
      head.rem, head.wh as wh, warehouse.clientname as whname, left(head.due,10) as due, head.yourref, head.qty, head.uom, head.itemid, item.itemname";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on head.client = client.client
      left join client as warehouse on warehouse.client = head.wh
      left join item on item.itemid=head.itemid
      where head.trno = ? and num.center = ?
      union all " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on head.client = client.client
      left join client as warehouse on warehouse.client = head.wh
      left join item on item.itemid=head.itemid
      where head.trno = ? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $hidetabbtn = ['btndeleteallitem' => false];
      $clickobj = ['button.btnadditem'];
      return  [
        'head' => $head, 'griddata' => ['inventory' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,
        'clickobj' => $clickobj, 'hidetabbtn' => $hidetabbtn
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
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
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->logger->sbcwritelog(
        $head['trno'],
        $config,
        'UPDATE HEAD',
        $head['docno'] . ' - ' . $head['itemname'] . ' - ' . $head['wh'] . ' - ' . $head['itemid'] . ' - ' . $head['qty']
      );
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['itemname'] . ' - ' . $head['wh']);
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

    $this->coreFunctions->execqry("delete from " . $this->process . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);

    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno, doc, docno, client, clientname, dateid, due, wh, rem, voiddate, yourref, ourref,
      lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty)
      select trno, doc, docno, client, clientname, dateid, due, wh, rem, voiddate, yourref, ourref, lockuser, lockdate,
      openby, users, createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty from " . $this->head . " where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->hprocess . "(trno, line, stageid, percentage)
        select trno, line, stageid, percentage from " . $this->process . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $this->hstock . "(trno, line, barcode, itemname, uom, wh, disc, rem, cost, rrqty, rrcost, qty,
          ext, qa, void, refx, linex, ref, encodeddate, encodedby, editdate, editby, sku, loc, stageid)
          select trno, line, barcode, itemname, uom, wh, disc, rem, cost, rrqty, rrcost, qty, ext, qa, void, refx, linex,
          ref, encodeddate, encodedby, editdate, editby, sku, loc, stageid from " . $this->stock . " where trno=?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $date = $this->othersClass->getCurrentTimeStamp();
          $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
          $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from " . $this->process . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
          $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hprocess . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting process'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno, doc, docno, client, clientname, dateid, due, wh, rem, voiddate, yourref, ourref,
      lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty)
      select trno, doc, docno, client, clientname, dateid, due, wh, rem, voiddate, yourref, ourref, lockuser, lockdate,
      openby, users, createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty from " . $this->hhead . " where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->process . "(trno, line, stageid, percentage)
        select trno, line, stageid, percentage from " . $this->hprocess . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $this->stock . "(trno, line, barcode, itemname, uom, wh, disc, rem, cost, rrqty, rrcost, qty,
          ext, qa, void, refx, linex, ref, encodeddate, encodedby, editdate, editby, sku, loc, stageid)
          select trno, line, barcode, itemname, uom, wh, disc, rem, cost, rrqty, rrcost, qty, ext, qa, void, refx, linex,
          ref, encodeddate, encodedby, editdate, editby, sku, loc, stageid from " . $this->hstock . " where trno=?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hprocess . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hprocess . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, process problems...'];
      }
    } else {
      return ['status' => false, 'msg' => 'UNPOST FAILED'];
    }
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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end
























} //end class
