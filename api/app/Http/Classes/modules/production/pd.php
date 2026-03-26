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

class pd
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PRODUCTION ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pdhead';
  public $hhead = 'hpdhead';
  public $stock = 'pdstock';
  public $hstock = 'hpdstock';
  public $process = 'piprocess';
  public $hprocess = 'hpiprocess';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = [
    'trno', 'docno', 'dateid', 'due', 'yourref', 'ourref', 'rem', 'itemid', 'qty', 'uom', 'pitrno', 'sotrno', 'soline', 'wh'
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
      'view' => 3673,
      'edit' => 3674,
      'new' => 3675,
      'save' => 3676,
      'delete' => 3677,
      'print' => 3678,
      'lock' => 3679,
      'unlock' => 3680,
      'post' => 3681,
      'unpost' => 3682,
      'additem' => 3683,
      'edititem' => 3684,
      'deleteitem' => 3684
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
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "  $filtersearch
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
    $fields = ['docno', 'pidocno', 'sodocno', 'itemname', ['qty', 'uom']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'uom.class', 'sbccsreadonly');
    data_set($col1, 'itemname.class', 'sbccsreadonly');
    data_set($col1, 'itemname.label', 'Finish Product');
    data_set($col1, 'itemname.type', 'lookup');
    data_set($col1, 'itemname.lookupclass', 'lookupitemprod');
    data_set($col1, 'itemname.action', 'lookupitem');
    data_set($col1, 'itemname.class', 'sbccsreadonly');
    data_set($col1, 'itemname.addedparams', ['pitrno']);

    data_set($col1, 'sodocno.type', 'lookup');
    data_set($col1, 'sodocno.label', 'Sales Order #');
    data_set($col1, 'sodocno.class', 'cssodocno sbccsreadonly');
    data_set($col1, 'sodocno.action', 'pendingsodetail');
    data_set($col1, 'sodocno.lookupclass', 'pendingsodetail');
    data_set($col1, 'sodocno.readonly', true);

    $fields = [['dateid', 'due'], ['yourref', 'ourref']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'due.label', 'Due Date');

    $fields = ['rem'];
    $col3 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => []];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['pidocno'] = '';
    $data[0]['pitrno'] = 0;
    $data[0]['sotrno'] = 0;
    $data[0]['sodocno'] = '';
    $data[0]['soline'] = 0;
    $data[0]['uom'] = '';
    $data[0]['qty'] = '';
    $data[0]['itemid'] = 0;
    $data[0]['itemname'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
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

    $qryselect = "select num.center, head.trno, head.docno, head.ourref, left(head.dateid,10) as dateid, head.clientname,
      date_format(head.createdate,'%Y-%m-%d') as createdate, head.rem, left(head.due,10) as due, head.yourref, head.qty,
      head.uom, head.itemid, item.itemname, pi.docno as pidocno, head.pitrno, head.wh, so.docno as sodocno, head.sotrno, head.soline";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on head.client = client.client
      left join item on item.itemid=head.itemid
      left join hpihead as pi on pi.trno=head.pitrno
      left join hsohead as so on so.trno=head.sotrno
      where head.trno = ? and num.center = ?
      union all " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on head.client = client.client
      left join item on item.itemid=head.itemid
      left join hpihead as pi on pi.trno=head.pitrno
      left join hsohead as so on so.trno=head.sotrno
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
      $prevdata = $this->coreFunctions->opentable("select sotrno, soline, qty from pdhead where trno=?", [$head['trno']]);
      if ($this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]) == 1) {
        if ($prevdata[0]->sotrno != 0) {
          $this->coreFunctions->execqry("update hsostock set pdqa=pdqa-" . $prevdata[0]->qty . " where trno=? and line=?", 'update', [$prevdata[0]->sotrno, $prevdata[0]->soline]);
          $soqa = $this->coreFunctions->datareader("select iss-pdqa as value from hsostock where trno=? and line=?", [$data['sotrno'], $data['soline']]);
          if ($soqa == '') $soqa = 0;
          if ($data['qty'] > $soqa) {
            $this->coreFunctions->execqry("update pdhead set qty=0 where trno=?", 'update', [$data['trno']]);
          } else {
            $this->coreFunctions->execqry("update hsostock set pdqa=? where trno=? and line=?", 'update', [$data['qty'], $data['sotrno'], $data['soline']]);
          }
        }
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      if ($insert = $this->coreFunctions->sbcinsert($this->head, $data) == 1) {
        if ($data['pitrno'] != 0 && $data['pitrno'] != '') {
          $this->coreFunctions->execqry("update hpihead set refx=? where trno=?", 'update', [$data['trno'], $data['pitrno']]);
          $piprocess = $this->coreFunctions->opentable("select line, stageid, percentage from hpiprocess where trno=?", [$data['pitrno']]);
          if (!empty($piprocess)) {
            foreach ($piprocess as $pip) {
              $qry = "select line as value from piprocess where trno=? order by line desc limit 1";
              $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
              if ($line == '') $line = 0;
              $line = $line + 1;
              $data2 = [
                'trno' => $data['trno'],
                'line' => $line,
                'stageid' => $pip->stageid,
                'percentage' => $pip->percentage
              ];
              $this->coreFunctions->sbcinsert('piprocess', $data2);
            }
          }
          $pistock = $this->coreFunctions->opentable("select trno, line, barcode, itemname, uom, wh, disc, rem, cost, rrqty, rrcost, qty, ext, qa, void, refx, linex, ref, encodeddate, encodedby, sku, loc, stageid from hpistock where trno=?", [$data['pitrno']]);
          if (!empty($pistock)) {
            foreach ($pistock as $pis) {
              $qry = "select line as value from pdstock order by line desc limit 1";
              $line = $this->coreFunctions->datareader($qry);
              if ($line == '') $line = 0;
              $line = $line + 1;
              $qty = $pis->qty;
              if (($head['qty'] != '' && $head['qty'] != 0) && ($pis->qty != '' && $pis->qty != 0)) $qty = $pis->qty * $head['qty'];
              $data2 = [
                'trno' => $data['trno'],
                'line' => $line,
                'barcode' => $pis->barcode,
                'itemname' => $pis->itemname,
                'uom' => $pis->uom,
                'wh' => $pis->wh,
                'disc' => $pis->disc,
                'rem' => $pis->rem,
                'cost' => $pis->cost,
                'rrqty' => $qty,
                'rrcost' => $pis->rrcost,
                'qty' => $qty,
                'ext' => $pis->ext,
                'qa' => $pis->qa,
                'void' => $pis->void,
                'refx' => $pis->refx,
                'linex' => $pis->linex,
                'ref' => $pis->ref,
                'sku' => $pis->sku,
                'loc' => $pis->loc,
                'stageid' => $pis->stageid,
                'encodeddate' => $this->othersClass->getCurrentTimeStamp(),
                'encodedby' => $config['params']['user']
              ];
              $this->coreFunctions->sbcinsert('pdstock', $data2);
            }
          }
        }
        if ($data['sotrno'] != 0 && $data['sotrno'] != '') {
          $soqa = $this->coreFunctions->datareader("select iss-pdqa as value from hsostock where trno=? and line=?", [$data['sotrno'], $data['soline']]);
          if ($soqa == '') $soqa = 0;
          if ($data['qty'] > $soqa) {
            $this->coreFunctions->execqry("update pdhead set qty=0 where trno=?", 'update', [$data['trno']]);
          } else {
            $this->coreFunctions->execqry("update hsostock set pdqa=pdqa+? where trno=? and line=?", 'update', [$data['qty'], $data['sotrno'], $data['soline']]);
          }
        }
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['pidocno'] . ' - ' . $head['sodocno']);
      }
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
    $data = $this->coreFunctions->opentable("select sotrno, soline, qty from " . $this->head . " where trno=?", [$trno]);
    if (!empty($data)) {
      if ($data[0]->sotrno != 0 && $data[0]->sotrno != '') {
        $this->coreFunctions->execqry("update hsostock set pdqa=pdqa-" . $data[0]->qty . " where trno=? and line=?", 'update', [$data[0]->sotrno, $data[0]->soline]);
      }
    }

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
      lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty, pitrno, sotrno, soline)
      select trno, doc, docno, client, clientname, dateid, due, wh, rem, voiddate, yourref, ourref, lockuser, lockdate, openby, users,
      createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty, pitrno, sotrno, soline from " . $this->head . " where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->hprocess . "(trno, line, stageid, percentage, itemid)
        select trno, line, stageid, percentage, itemid from " . $this->process . " where trno=?";
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
      lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty, pitrno, sotrno, soline)
      select trno, doc, docno, client, clientname, dateid, due, wh, rem, voiddate, yourref, ourref, lockuser, lockdate, openby, users,
      createdate, createby, editby, editdate, viewby, viewdate, itemid, uom, qty, pitrno, sotrno, soline from " . $this->hhead . " where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->process . "(trno, line, stageid, percentage, itemid)
        select trno, line, stageid, percentage, itemid from " . $this->hprocess . " where trno=?";
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
          $this->coreFunctions->execqry("delete from " . $this->process . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
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
