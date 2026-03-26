<?php

namespace App\Http\Classes\modules\purchase;

use Illuminate\Http\Request;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class ph
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Price Change';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false, 'showwh' => false];
  public $tablenum = 'transnum';
  public $head = 'phhead';
  public $hhead = 'hphhead';
  public $stock = 'phstock';
  public $hstock = 'hphstock';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;

  private $fields = ['trno', 'docno', 'dateid', 'rem'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];

  private $barcode;

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
    $this->barcode = new  DNS1D;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4393,
      'edit' => 4394,
      'new' => 4395,
      'save' => 4396,
      'delete' => 4397,
      'print' => 4398,
      'lock' => 4399,
      'unlock' => 4400,
      'post' => 4401,
      'unpost' => 4402,
      'additem' => 4403,
      'edititem' => 4404,
      'deleteitem' => 4405
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    return [];
  }

  public function loaddoclisting($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $limit = '';
    $condition = '';
    $projectfilter = '';
    $searchfilter = $config['params']['search'];

    $join = '';
    $hjoin = '';
    $addparams = '';

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }
    $status = "'DRAFT'";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $status = "'LOCKED'";
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $status = "'POSTED'";
    $lstatus = "'DRAFT'";
    $lstatcolor = "'red'";
    $gstatcolor = "'grey'";
    $dateid = "left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.rem', 'head.editby', 'head.viewby', 'num.postedby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $qry = "select head.trno, head.docno, head.rem, date(head.dateid) as dateid, 'DRAFT' as status, head.createby, head.editby, head.viewby, num.postedby, date(num.postdate) as postdate
      from " . $this->head . " as head
        left join " . $this->tablenum . " as num on num.trno=head.trno
        where head.doc=? and num.center=? and convert(head.dateid,date)>=? and convert(head.dateid,date)<=? " . $filtersearch . " " . $condition . "
      union all
      select head.trno, head.docno, head.rem, date(head.dateid) as dateid, 'POSTED' as status, head.createby, head.editby, head.viewby, num.postedby, date(num.postdate) as postdate
      from " . $this->hhead . " as head
        left join " . $this->tablenum . " as num on num.trno=head.trno
        where head.doc=? and num.center=? and convert(head.dateid,date)>=? and convert(head.dateid,date)<=? " . $filtersearch . " " . $condition . " " . $limit;
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

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

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

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];

    $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createTab($access, $config)
  {
    $column = ['action', 'barcode', 'itemname', 'uom', 'amt', 'discr', 'discws', 'disca', 'discb', 'discc', 'discd', 'disce', 'cashamt', 'cashdisc', 'wsamt', 'wsdisc', 'amt1', 'disc1', 'amt2', 'disc2'];
    $tab = [
      $this->gridname => ['gridcolumns' => $column]
    ];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][1]['type'] = 'label';
    $obj[0]['inventory']['columns'][4]['label'] = 'Base Price';
    $obj[0]['inventory']['columns'][16]['label'] = 'Price1';
    $obj[0]['inventory']['columns'][18]['label'] = 'Price2';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['itemlookup', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'rem'];
    $col1 = $this->fieldClass->create($fields);
    $fields = ['dateid'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['rem'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $projectfilter = "";

    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
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

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $qryselect = "select head.trno, head.docno, head.rem";

    $qry = "select head.trno, head.docno, head.dateid, head.rem from " . $table . " as head left join " . $tablenum . " as num on num.trno=head.trno
        where head.trno=? and num.doc=? and num.center=?
      union all
      select head.trno, head.docno, head.dateid, head.rem from " . $htable . " as head left join " . $tablenum . " as num on num.trno=head.trno
        where head.trno=? and num.doc=? and num.center=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => []];
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
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=?", [$trno]);
    if ($this->othersClass->isposted($config)) return ['status' => false, 'msg' => 'Post Failed, Already posted...'];
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, rem, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser) select trno, doc, docno, dateid, rem, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser from " . $this->head . " where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->hstock . "(trno, line, barcode, itemname, uom, amt, discr, discws, disca, discb, discc,
      discd, disce, cashamt, cashdisc, wsamt, wsdisc, amt1, disc1, amt2, disc2, encodeddate, editdate, encodedby, editby) select trno, line, barcode, itemname, uom, amt, discr, discws, disca, discb, discc,
      discd, disce, cashamt, cashdisc, wsamt, wsdisc, amt1, disc1, amt2, disc2, encodeddate, editdate, encodedby, editby from " . $this->stock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $data = $this->coreFunctions->opentable("select barcode, amt, discr, discws, disca, discb, discc, discd, disce, cashamt, cashdisc, wsamt, wsdisc, amt1, disc1, amt2, disc2 from " . $this->hstock . " where trno=" . $trno);
        $dd = [];
        if (!empty($data)) {
          foreach ($data as $d) {
            if ($d->amt > 0) $dd['amt16'] = $d->amt;
            if ($d->discr != '') $dd['disc16'] = $d->discr;
            if ($d->discws != '') $dd['disc17'] = $d->discws;
            if ($d->disca != '') $dd['disc18'] = $d->disca;
            if ($d->discb != '') $dd['disc19'] = $d->discb;
            if ($d->discc != '') $dd['disc20'] = $d->discc;
            if ($d->discd != '') $dd['disc21'] = $d->discd;
            if ($d->disce != '') $dd['disc22'] = $d->disce;
            if ($d->cashamt > 0) $dd['amt'] = $d->cashamt;
            if ($d->cashdisc != '') $dd['disc'] = $cashdisc;
            if ($d->wsamt > 0) $dd['amt2'] = $d->wsamt;
            if ($d->wsdisc != '') $dd['disc2'] = $d->wsdisc;
            if ($d->amt1 > 0) $dd['famt'] = $d->amt1;
            if ($d->disc1 != '') $dd['disc3'] = $d->disc1;
            if ($d->amt2 > 0) $dd['amt4'] = $d->amt2;
            if ($d->disc2 != '') $dd['disc4'] = $d->disc2;
            if (!empty($dd)) {
              $this->coreFunctions->sbcupdate('item', $dd, ['barcode' => $d->barcode]);
            }
          }
        }
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $user];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
    } else {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=" . $trno);
    if (!$this->othersClass->isposted($config)) return ['status' => false, 'msg' => 'Post Failed, Already unposted...'];
    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, rem, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser) select trno, doc, docno, dateid, rem, createby, createdate, editdate, editby, viewby, viewdate, lockdate, lockuser from " . $this->hhead . " where trno=" . $trno . " limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert')) {
      $qry = "insert into " . $this->stock . "(trno, line, barcode, itemname, uom, amt, discr, discws, disca, discb, discc, discd, disce, cashamt,
        cashdisc, wsamt, wsdisc, amt1, disc1, amt2, disc2, encodeddate, editdate, encodedby, editby)
        select trno, line, barcode, itemname, uom, amt, discr, discws, disca, discb, discc, discd, disce, cashamt, cashdisc, wsamt,
        wsdisc, amt1, disc1, amt2, disc2, encodeddate, editdate, encodedby, editby from " . $this->hstock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postedby='', postdate=null where trno=" . $trno, 'update');
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=" . $trno, 'delete');
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=" . $trno, 'delete');
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=" . $trno, 'delete');
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on UnPosting stock'];
      }
    } else {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on UnPosting Head'];
    }
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    stock.uom,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
    round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,
    item.brand,
    stock.rem,
    stock.palletid,
    stock.locid,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,stock.fcost,ifnull(stock.stageid,0) as stageid ,ifnull(st.stage,'') as stage,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.sorefx,stock.solinex,stock.poref,stock.sgdrate,ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $qry = "select stock.trno, stock.line, stock.barcode, stock.itemname, item.itemid, stock.uom, '' as bgcolor,
      format(stock.amt," . $decimalprice . ") as amt, stock.discr, stock.discws, stock.disca, stock.discb, stock.discc, stock.discd,
      stock.disce, format(stock.cashamt," . $decimalprice . ") as cashamt, stock.cashdisc, format(stock.wsamt," . $decimalprice . ") as wsamt, stock.wsdisc,
      format(stock.amt1," . $decimalprice . ") as amt1, stock.disc1, format(stock.amt2," . $decimalprice . ") as amt2, stock.disc2
    from " . $this->stock . " as stock
      left join item on item.barcode=stock.barcode
    where stock.trno=?
    union all
    select stock.trno, stock.line, stock.barcode, stock.itemname, item.itemid, stock.uom, '' as bgcolor,
      format(stock.amt," . $decimalprice . ") as amt, stock.discr, stock.discws, stock.disca, stock.discb, stock.discc, stock.discd,
      stock.disce, format(stock.cashamt," . $decimalprice . ") as cashamt, stock.cashdisc, format(stock.wsamt," . $decimalprice . ") as wsamt, stock.wsdisc,
      format(stock.amt1," . $decimalprice . ") as amt1, stock.disc1, format(stock.amt2," . $decimalprice . ") as amt2, stock.disc2
    from " . $this->hstock . " as stock
      left join item on item.barcode=stock.barcode
    where stock.trno=? order by line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $qry = "select stock.trno, stock.line, stock.barcode, stock.itemname, item.itemid, stock.uom, '' as bgcolor,
      format(stock.amt," . $decimalprice . ") as amt, stock.discr, stock.discws, stock.disca, stock.discb, stock.discc, stock.discd,
      stock.disce, format(stock.cashamt," . $decimalprice . ") as cashamt, stock.cashdisc, format(stock.wsamt," . $decimalprice . ") as wsamt, stock.wsdisc,
      format(stock.amt1," . $decimalprice . ") as amt1, stock.disc1, format(stock.amt2," . $decimalprice . ") as amt2, stock.disc2
    from " . $this->stock . " as stock
      left join item on item.barcode=stock.barcode
    where stock.trno=? and stock.line=?
    union all
    select stock.trno, stock.line, stock.barcode, stock.itemname, item.itemid, stock.uom, '' as bgcolor,
      format(stock.amt," . $decimalprice . ") as amt, stock.discr, stock.discws, stock.disca, stock.discb, stock.discc, stock.discd,
      stock.disce, format(stock.cashamt," . $decimalprice . ") as cashamt, stock.cashdisc, format(stock.wsamt," . $decimalprice . ") as wsamt, stock.wsdisc,
      format(stock.amt1," . $decimalprice . ") as amt1, stock.disc1, format(stock.amt2," . $decimalprice . ") as amt2, stock.disc2
    from " . $this->hstock . " as stock
      left join item on item.barcode=stock.barcode
    where stock.trno=? and stock.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
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
      case 'fiitemlookup':
        return $this->fiitemlookup($config);
        break;
      case 'getjbsummary':
        return $this->getjbsummary($config);
        break;
      case 'getjbdetails':
        return $this->getjbdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'flowchart':
        return $this->flowchart($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function tagreceived($config)
  {
    return ['status' => true, 'msg' => 'Received Successfully', 'data' => []];
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,concat('Total JO Amt: ',round(sum(s.ext),2)) as rem,s.refx from hjohead as po left join hjostock as s on s.trno = po.trno left join glstock as g on g.refx = po.trno and g.linex = s.line where g.trno = ? group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //PO
        data_set($nodes, $t[$key]->docno, ['align' => 'right', 'x' => 200, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'blue', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'ac']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,concat('Total PR Qty: ',round(sum(s.qty),2)) as rem from hprhead as po left join hprstock as s on s.trno = po.trno  where po.trno = ? group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set($nodes, $x[$key2]->docno, ['align' => 'right', 'x' => 10, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $x[$key2]->docno, 'label' => $x[$key2]->rem, 'color' => 'yellow', 'details' => [$x[$key2]->dateid]]);
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $qry = "select head.docno,
    left(head.dateid,10) as dateid,
    concat('Amount: ',round(ifnull(apledger.db, 0)+ifnull(apledger.cr, 0),2),'  -  ','BALANCE: ',
    round(ifnull(apledger.bal, 0),2)) as rem
    from glhead as head
    left join apledger on head.trno = apledger.trno
    where head.trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set($nodes, 'ac', ['align' => 'right', 'x' => $startx, 'y' => 100, 'w' => 250, 'h' => 130, 'type' => $t[0]->docno, 'label' => $t[0]->rem, 'color' => 'green', 'details' => [$t[0]->dateid]]);
    }

    $qry = "select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc != ? and stock.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid = stock.itemid
    where head.doc != ? and stock.refx=?
    ";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno']]);
    if (!empty($t)) {
      $y = 0;
      foreach ($t as $key => $value) {
        data_set($nodes, $t[$key]->docno, ['align' => 'left', 'x' => $startx + 400, 'y' => 50 + $y, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'red', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => 'ac', 'to' => $t[$key]->docno]);
        $y = $y + 120;
      }
    }
    $data['nodes'] = $nodes;
    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function flowchart($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['centerX'] = 1024;
    $data['centerY'] = 140;
    $data['scale'] = 1;
    $qry = "select apledger.docno,left(apledger.dateid,10) as dateid,CAST(concat('Amount: ',round(apledger.db+apledger.cr,2),'  -  ','BALANCE: ',round(apledger.bal,2)) as CHAR) as rem from apledger where trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    array_push($nodes, ['id' => 2, 'x' => -500, 'y' => -120, 'type' => $t[0]->docno, 'label' => $t[0]->rem]);

    array_push($nodes, ['id' => 4, 'x' => -357, 'y' => 80, 'type' => 'Script', 'label' => 'test2']);
    array_push($nodes, ['id' => 6, 'x' => -557, 'y' => 80, 'type' => 'Rule', 'label' => 'test3']);
    $data['nodes'] = $nodes;
    array_push($links, ['id' => 3, 'from' => 2, 'to' => 4]);
    array_push($links, ['id' => 5, 'from' => 2, 'to' => 6]);
    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => 'Error updating item'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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


  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $trno = $config['params']['trno'];
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,'' as disc,'' as locuom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);
    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config, $forex);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) $item[0]['amt'] = $lprice['data'][0]['amt'];
      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }


  public function fiitemlookup($config)
  {
    $rows = [];
    $msg = '';
    $countitem = 0;
    $trno = $config['params']['trno'];
    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['trno'] = $trno;
      $config['params']['data'] = $value;
      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      } else {
        $msg .= $return['msg'];
      }
    }
    if ($msg == '') {
      $msg = 'Successfully saved.';
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'count' => $countitem];
  }

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $barcode = $config['params']['data']['barcode'];
    $itemname = $config['params']['data']['itemname'];
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $line = $config['params']['data']['line'];
    }
    $uom = isset($config['params']['data']['uom']) ? $config['params']['data']['uom'] : '';
    $amt = isset($config['params']['data']['amt']) ? $config['params']['data']['amt'] : '0.000000';
    $discr = isset($config['params']['data']['discr']) ? $config['params']['data']['discr'] : '';
    $discws = isset($config['params']['data']['discws']) ? $config['params']['data']['discws'] : '';
    $disca = isset($config['params']['data']['disca']) ? $config['params']['data']['disca'] : '';
    $discb = isset($config['params']['data']['discb']) ? $config['params']['data']['discb'] : '';
    $discc = isset($config['params']['data']['discc']) ? $config['params']['data']['discc'] : '';
    $discd = isset($config['params']['data']['discd']) ? $config['params']['data']['discd'] : '';
    $disce = isset($config['params']['data']['disce']) ? $config['params']['data']['disce'] : '';
    $cashamt = isset($config['params']['data']['cashamt']) ? $config['params']['data']['cashamt'] : '0.000000';
    $cashdisc = isset($config['params']['data']['cashdisc']) ? $config['params']['data']['cashdisc'] : '';
    $wsamt = isset($config['params']['data']['wsamt']) ? $config['params']['data']['wsamt'] : '0.000000';
    $wsdisc = isset($config['params']['data']['wsdisc']) ? $config['params']['data']['wsdisc'] : '';
    $amt1 = isset($config['params']['data']['amt1']) ? $config['params']['data']['amt1'] : '0.000000';
    $disc1 = isset($config['params']['data']['disc1']) ? $config['params']['data']['disc1'] : '';
    $amt2 = isset($config['params']['data']['amt2']) ? $config['params']['data']['amt2'] : '0.000000';
    $disc2 = isset($config['params']['data']['disc2']) ? $config['params']['data']['disc2'] : '';

    if ($discr != '') {
      $cashamt = $this->othersClass->Discount($amt, $discr);
    }

    if ($discws != '') {
      $wsamt = $this->othersClass->Discount($amt, $discws);
    }

    if ($disca != '') {
      $amt1 = $this->othersClass->Discount($amt, $disca);
    }

    if ($discb != '') {
      $amt2 = $this->othersClass->Discount($amt, $discb);
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'barcode' => $barcode,
      'itemname' => $itemname,
      'uom' => $uom,
      'amt' => str_replace(',', '', $amt),
      'discr' => $discr,
      'discws' => $discws,
      'disca' => $disca,
      'discb' => $discb,
      'discc' => $discc,
      'discd' => $discd,
      'disce' => $disce,
      'cashamt' => $cashamt,
      'cashdisc' => $cashdisc,
      'wsamt' => $wsamt,
      'wsdisc' => $wsdisc,
      'amt1' => $amt1,
      'disc1' => $disc1,
      'amt2' => $amt2,
      'disc2' => $disc2
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $barcode . ' Uom:' . $uom . ' Amt:' . $amt . ' DiscR:' . $discr . ' DiscWS:' . $discws . ' DiscA:' . $disca . ' DiscB:' . $discb . ' DiscC:' . $discc . ' DiscD:' . $discd . ' DiscE:' . $disce . ' CashAmt:' . $cashamt . ' CashDisc:' . $cashdisc . ' WSAmt:' . $wsamt . ' WSDisc:' . $wsdisc . ' Price1:' . $amt1 . ' Price1Disc:' . $disc1 . ' Price2:' . $amt2 . ' Price2Disc:' . $disc2, $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' BasePrice:' . $data[0]['amt'] . ' DiscR:' . $data[0]['discr'] . ' DiscWS:' . $data[0]['discws'] . ' DiscA:' . $data[0]['disca'] . ' DiscB:' . $data[0]['discb'] . ' DiscC:' . $data[0]['discc'] . ' DiscD:' . $data[0]['discd'] . ' DiscE:' . $data[0]['disce'] . ' CashPrice:' . $data[0]['cashamt'] . ' CashDisc:' . $data[0]['cashdisc'] . ' WSPrice:' . $data[0]['wsamt'] . ' WSDisc:' . $data[0]['wsdisc'] . ' Price1:' . $data[0]['amt1'] . ' Price1Disc:' . $data[0]['disc1'] . ' Price2:' . $data[0]['amt2'] . ' Price2Disc:' . $data[0]['disc2']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config, $forex = 1)
  {
    $barcode = $config['params']['barcode'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
    stock.rrcost as amt,stock.uom,stock.disc
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join cntnum on cntnum.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc = 'RR' and cntnum.center = ?
    and item.barcode = ? and stock.rrcost <> 0
    UNION ALL
    select head.docno,head.dateid,stock.rrcost as computeramt,
    stock.uom,stock.disc from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = head.clientid
    left join cntnum on cntnum.trno=head.trno
    where head.doc = 'RR' and cntnum.center = ?
    and item.barcode = ? and stock.rrcost <> 0
    order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
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
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
