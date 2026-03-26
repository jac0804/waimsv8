<?php

namespace App\Http\Classes\modules\proline;

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

class qt
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'QUOTATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'qthead';
  public $hhead = 'hqthead';
  public $detail = 'qtinfo';
  public $hdetail = 'hqtinfo';
  public $stock = 'qtstock';
  public $hstock = 'hqtstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'markup', 'tax'];
  public $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'Primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'Primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'Primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'Primary'],
  ];
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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2133,
      'edit' => 2134,
      'new' => 2135,
      'save' => 2136,
      // 'change'=>2137, remove change doc
      'delete' => 2138,
      'print' => 2139,
      'lock' => 2140,
      'unlock' => 2141,
      'changeamt' => 2142,
      'post' => 2143,
      'unpost' => 2144,
      'additem' => 2145,
      'edititem' => 2146,
      'deleteitem' => 2147
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'duplicatedoc'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
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
    $limit = "limit 150";
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
    $details = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewqtdetails']];
    $flraddons = $this->tabClass->createtab(['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryflraddons', 'label' => 'Floor Addons']], []);
    $inaddons = $this->tabClass->createtab(['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryinaddons', 'label' => 'Interior Addons']], []);
    $accaddons = $this->tabClass->createtab(['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryaccaddons', 'label' => 'Accessories Addons']], []);
    $strucrepair = $this->tabClass->createtab(['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystrucrepair', 'label' => 'Structural Repairs']], []);
    $notes = $this->tabClass->createtab(['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryqtnotes', 'label' => 'Notes']], []);
    // $return['DETAILS'] = ['icon' => 'fa fa-tags', 'customform' => $details];
    $return['FLOOR ADDONS'] = ['icon' => 'fa fa-tags', 'tab' => $flraddons];
    $return['INTERIOR ADDONS'] = ['icon' => 'fa fa-tags', 'tab' => $inaddons];
    $return['ACCESSORIES ADDONS'] = ['icon' => 'fa fa-tags', 'tab' => $accaddons];
    $return['STRUCTURAL REPAIRS'] = ['icon' => 'fa fa-tags', 'tab' => $strucrepair];
    $return['NOTES'] = ['icon' => 'fa fa-tags', 'tab' => $notes];

    return $return;
  }

  public function createTab($access, $config)
  {
    $gridcolumn = ['action', 'isqty', 'uom', 'isamt', 'ext', 'itemname', 'barcode'];

    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $fields = [['', 'totalmarkup']];
    $col2 = $this->fieldClass->create($fields);
    $fields = [['totaltax', 'ext']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'ext.label', 'Grand Total');
    data_set($col3, 'ext.class', 'cstax sbccsreadonly');
    data_set($col3, 'ext.readonly', true);
    data_set($col2, 'totaltax.label', 'Total VAT');
    $gridheadinput = ['col0' => [], 'col1' => $col1, 'col2' => $col2, 'col3' => $col3];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $gridcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => [],
        'gridheadinput' => $gridheadinput,
      ],
      'customform' => ['event' => ['action' => 'customform', 'lookupclass' => 'viewqtdetails', 'access' => 'view'], 'label' => 'DETAILS']
    ];
    $stockbuttons = ['save', 'delete', 'showbalance'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'COSTING';

    $obj[0]['inventory']['columns'][0]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;';
    $obj[0]['inventory']['columns'][6]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][6]['label'] = '';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['generateqtcomp', 'additem', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[2]['label'] = "SAVE ALL";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'barcode', 'brandname', 'amt'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'barcode.label', 'Job(Item)');
    data_set($col1, 'barcode.class', 'sbccsreadonly');
    data_set($col1, 'brandname.type', 'input');
    data_set($col1, 'brandname.readonly', false);
    data_set($col1, 'brandname.class', 'csbrand');
    data_set($col1, 'barcode.action', 'lookupitem');
    data_set($col1, 'barcode.lookupclass', 'lookupitemqt');
    data_set($col1, 'amt.label', 'Price');
    data_set($col1, 'amt.required', true);
    data_set($col1, 'barcode.required', true);

    $fields = ['dateid', 'clientname', 'itemname', 'modelname', 'disc'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'modelname.type', 'input');
    data_set($col2, 'modelname.class', 'csmodel');
    data_set($col2, 'modelname.readonly', false);
    data_set($col2, 'disc.label', 'Discounted');
    data_set($col2, 'itemname.class', 'sbccsreadonly');

    $fields = ['yourref', 'ourref', ['plateno', 'leadtime'], ['markup', 'tax']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'markup.label', 'Markup %');
    data_set($col3, 'tax.label', 'VAT %');
    data_set($col3, 'totaltax.label', 'Total VAT');
    data_set($col3, 'tax.class', 'cstax');
    data_set($col3, 'tax.readonly', false);

    $fields = ['rem'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.required', false);
    data_set($col4, 'rem.required', false);


    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
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
    $data[0]['itemid'] = 0;
    $data[0]['barcode'] = '';
    $data[0]['itemname'] = '';
    $data[0]['model'] = 0;
    $data[0]['modelname'] = '';
    $data[0]['brand'] = 0;
    $data[0]['brandname'] = '';
    $data[0]['amt'] = '';
    $data[0]['disc'] = '';
    $data[0]['plateno'] = '';
    $data[0]['leadtime'] = 0;
    $data[0]['tax'] = 0;
    $data[0]['markup'] = 0;
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

    $head = $this->openhead($config);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $gridheaddata = $this->gridheaddata($config);

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'gridheaddata' => $gridheaddata, 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed', 'gridheaddata' => []];
    }
  }

  public function gridheaddata($config)
  {
    $trno = $config['params']['trno'];
    $ext = $this->coreFunctions->datareader("select sum(ext) as value from (
        select sum(ext) as ext from " . $this->stock . " where trno=? union all select sum(ext) as ext from " . $this->hstock . " where trno=?) as s", [$trno, $trno]);

    if ($ext == '') $ext = 0;

    $head = $this->coreFunctions->opentable("select markup, tax from " . $this->head . " where trno=? union all select markup, tax from " . $this->hhead . " where trno=?", [$trno, $trno]);

    $head[0]->totalmarkup = $ext * ($head[0]->markup / 100);
    $head[0]->totaltax = ($ext + $head[0]->totalmarkup) * ($head[0]->tax / 100);
    $head[0]->ext = $ext + $head[0]->totaltax + $head[0]->totalmarkup;

    // $head[0]->totalmarkup = number_format($head[0]->totalmarkup, 2);
    // $head[0]->totaltax = number_format($head[0]->totaltax, 2);
    // $head[0]->ext = number_format($head[0]->ext, 2);

    return $this->coreFunctions->opentable("select FORMAT(" . $head[0]->totalmarkup . ",2) as totalmarkup, FORMAT(" . $head[0]->totaltax . ",2) as totaltax, FORMAT(" . $head[0]->ext . ",0) as ext");
  }

  public function openhead($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         head.clientname,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem, head.tax, head.markup,
         detail.itemid, detail.isamt as amt, detail.disc, detail.plateno, detail.leadtime2 as leadtime,
         head.brand as brandname, head.model as modelname,
         item.barcode, item.itemname";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join qtinfo as detail on detail.trno=head.trno
        left join item on item.itemid=detail.itemid
        left join model_masterfile as model on model.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join hqtinfo as detail on detail.trno=head.trno
        left join item on item.itemid=detail.itemid
        left join model_masterfile as model on model.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        where head.trno = ? and num.center=? ";

    return $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
  }

  public function copyotherinfo($sourcetrno, $config)
  {
    $status = true;
    $msg = '';

    $trno = $config['params']['trno'];

    $exist = $this->coreFunctions->datareader("select trno as value from qtinfo where trno=?", [$trno]);
    if ($exist == '') {
      $exist = 0;
    }

    if (!$exist) {
      $qry = "insert into qtinfo (trno, encodedby, encodeddate, itemid, isamt, disc, plateno, leadtime2, outdimlen, outdimwd, outdimht, indimlen, indimwd, indimht, chassiswd, underchassis, secchassisqty, secchassissz, secchassistk, secchassismat, flrjoistqty, flrjoistqtysz, flrjoistqtytk, flrjoistqtymat, flrtypework, flrtypeworktk, flrtypeworkty, flrtypeworkmat, exttypework, exttypeworkqty, exttypeworkty, inwalltypework, inwalltypeworkqty, inwalltypeworktk, inwalltypeworkty, inceiltypework, inceiltypeworkqty, inceiltypeworktk, inceiltypeworkty, insultk, insulty, reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem, sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem, normlights, lightsrepair, upclrlights, lowclrlights, clrlightsrepair, paintcover, bodycolor, flrcolor, unchassiscolor, paintroof, exterior, interior, sideguards, reseal)
              select " . $trno . ",'" . $config['params']['user'] . "','" . $this->othersClass->getCurrentTimeStamp() . "', itemid, isamt, disc, plateno, leadtime2, outdimlen, outdimwd, outdimht, indimlen, indimwd, indimht, chassiswd, underchassis, secchassisqty, secchassissz, secchassistk, secchassismat, flrjoistqty, flrjoistqtysz, flrjoistqtytk, flrjoistqtymat, flrtypework, flrtypeworktk, flrtypeworkty, flrtypeworkmat, exttypework, exttypeworkqty, exttypeworkty, inwalltypework, inwalltypeworkqty, inwalltypeworktk, inwalltypeworkty, inceiltypework, inceiltypeworkqty, inceiltypeworktk, inceiltypeworkty, insultk, insulty, reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem, sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem, normlights, lightsrepair, upclrlights, lowclrlights, clrlightsrepair, paintcover, bodycolor, flrcolor, unchassiscolor, paintroof, exterior, interior, sideguards, reseal from hqtinfo where trno=" . $sourcetrno;
      $result = $this->coreFunctions->execqry($qry);
      if ($result) {

        $exist = $this->coreFunctions->datareader("select trno as value from qtaddons where trno=?", [$trno]);
        if ($exist == '') {
          $exist = 0;
        }

        if ($exist) {
          $this->coreFunctions->execqry("delete from qtaddons where trno=" . $trno);
        }

        $qry = "insert into qtaddons (trno, addons, rem, qty, side, parts, createby, createdate, addontype)
                select " . $trno . ", addons, rem, qty, side, parts, '" . $config['params']['user'] . "', '" . $this->othersClass->getCurrentTimeStamp() . "', addontype from hqtaddons where trno=" . $sourcetrno;
        $result = $this->coreFunctions->execqry($qry);
        if ($result) {

          $qry = "insert into hqtstock (trno,line,uom,disc,rem,amt,isamt,isqty,iss,ext,encodeddate,encodedby,itemid,whid,projectid)
          select " . $trno . ",line,uom,disc,rem,amt,isamt,isqty,iss,ext,'" . $this->othersClass->getCurrentTimeStamp() . "','" . $config['params']['user'] . "',itemid,whid,projectid from hqtstock where trno=" . $sourcetrno;

          $result = $this->coreFunctions->execqry($qry);

          if ($result) {
            $status = true;
          } else {
            $status = false;
            $msg = 'Failed to duplicate costing';
          }
        } else {
          $status = false;
          $msg = 'Failed to duplicate add-ons';
        }
      } else {
        $status = false;
        $msg = 'Failed to duplicate details';
      }
    }

    if (!$status) {
      $config['params']['trno'] = $trno;
      $this->deletetrans($config);
    }

    return ['status' => $status, 'msg' => $msg];
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
    $data['model'] = $head['modelname'];
    $data['brand'] = $head['brandname'];

    $d = [];
    $d['trno'] = $head['trno'];
    $d['itemid'] = $head['itemid'];
    $d['isamt'] = $head['amt'];
    $d['disc'] = $head['disc'];
    $d['plateno'] = $head['plateno'];
    $d['leadtime2'] = $head['leadtime'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $d['editdate'] = $data['editdate'];
      $d['editby'] = $data['editby'];
      $this->coreFunctions->sbcupdate($this->detail, $d, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $d['encodeddate'] = $data['createdate'];
      $d['encodedby'] = $data['createby'];
      $this->coreFunctions->sbcinsert($this->detail, $d);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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

    //$this->coreFunctions->execqry('delete from '.$this->stock." where trno=?",'delete',[$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from qtaddons where trno=?', 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,branch,deptid,markup,tax)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.branch,head.deptid,head.markup,head.tax FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      if (!$this->othersClass->postingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }
      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hdetail . "(trno, itemid, isamt, disc, plateno, leadtime2, encodeddate, encodedby, editdate, editby, outdimlen, outdimwd, outdimht, indimlen, indimwd, indimht, chassiswd, underchassis, secchassisqty, secchassissz, secchassistk, secchassismat, flrjoistqty, flrjoistqtysz, flrjoistqtytk, flrjoistqtymat, flrtypework, flrtypeworktk, flrtypeworkty, flrtypeworkmat, exttypework, exttypeworkqty, exttypeworkty, inwalltypework, inwalltypeworkqty, inwalltypeworktk, inwalltypeworkty, inceiltypework, inceiltypeworkqty, inceiltypeworktk, inceiltypeworkty, insultk, insulty, reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem, sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem, normlights, lightsrepair, upclrlights, lowclrlights, clrlightsrepair, paintcover, bodycolor, flrcolor, unchassiscolor, paintroof, exterior, interior, sideguards, reseal) select trno, itemid, isamt, disc, plateno, leadtime2, encodeddate, encodedby, editdate, editby, outdimlen, outdimwd, outdimht, indimlen, indimwd, indimht, chassiswd, underchassis, secchassisqty, secchassissz, secchassistk, secchassismat, flrjoistqty, flrjoistqtysz, flrjoistqtytk, flrjoistqtymat, flrtypework, flrtypeworktk, flrtypeworkty, flrtypeworkmat, exttypework, exttypeworkqty, exttypeworkty, inwalltypework, inwalltypeworkqty, inwalltypeworktk, inwalltypeworkty, inceiltypework, inceiltypeworkqty, inceiltypeworktk, inceiltypeworkty, insultk, insulty, reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem, sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem, normlights, lightsrepair, upclrlights, lowclrlights, clrlightsrepair, paintcover, bodycolor, flrcolor, unchassiscolor, paintroof, exterior, interior, sideguards, reseal from " . $this->detail . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into hqtaddons(line, trno, addons, rem, qty, side, parts, createby, createdate, addontype) select line, trno, addons, rem, qty, side, parts, createby, createdate, addontype from qtaddons where trno=?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
            whid,loc,expiry,disc,iss,void,isamt,amt,isqty,ext,
            encodeddate,encodedby,editdate,editby)
            SELECT trno, line, itemid, uom,whid,loc,expiry,disc, iss,void,isamt,amt, isqty, ext,
            encodeddate, encodedby,editdate,editby FROM " . $this->stock . " where trno =?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from qtaddons where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
          } else {
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from hqtaddons where trno=?", 'delete', [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
          }
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Addons'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Details'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }

    $qry = "select trno from " . $this->hhead . " where trno=? and sotrno<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, Job Order already created...'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
      yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,agent,branch,deptid,markup,tax)
      select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
      head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.agent,head.branch,head.deptid,head.markup,head.tax
      from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
      where head.trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }
      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->detail . "(trno, itemid, isamt, disc, plateno, leadtime2, encodeddate, encodedby, editdate, editby, outdimlen, outdimwd, outdimht, indimlen, indimwd, indimht, chassiswd, underchassis, secchassisqty, secchassissz, secchassistk, secchassismat, flrjoistqty, flrjoistqtysz, flrjoistqtytk, flrjoistqtymat, flrtypework, flrtypeworktk, flrtypeworkty, flrtypeworkmat, exttypework, exttypeworkqty, exttypeworkty, inwalltypework, inwalltypeworkqty, inwalltypeworktk, inwalltypeworkty, inceiltypework, inceiltypeworkqty, inceiltypeworktk, inceiltypeworkty, insultk, insulty, reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem, sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem, normlights, lightsrepair, upclrlights, lowclrlights, clrlightsrepair, paintcover, bodycolor, flrcolor, unchassiscolor, paintroof, exterior, interior, sideguards, reseal) select trno, itemid, isamt, disc, plateno, leadtime2, encodeddate, encodedby, editdate, editby, outdimlen, outdimwd, outdimht, indimlen, indimwd, indimht, chassiswd, underchassis, secchassisqty, secchassissz, secchassistk, secchassismat, flrjoistqty, flrjoistqtysz, flrjoistqtytk, flrjoistqtymat, flrtypework, flrtypeworktk, flrtypeworkty, flrtypeworkmat, exttypework, exttypeworkqty, exttypeworkty, inwalltypework, inwalltypeworkqty, inwalltypeworktk, inwalltypeworkty, inceiltypework, inceiltypeworkqty, inceiltypeworktk, inceiltypeworkty, insultk, insulty, reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem, sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem, normlights, lightsrepair, upclrlights, lowclrlights, clrlightsrepair, paintcover, bodycolor, flrcolor, unchassiscolor, paintroof, exterior, interior, sideguards, reseal from " . $this->hdetail . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into qtaddons(line, trno, addons, rem, qty, side, parts, createby, createdate, addontype) select line, trno, addons, rem, qty, side, parts, createby, createdate, addontype from hqtaddons where trno=?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $qry = "insert into " . $this->stock . "(
            trno,line,itemid,uom,whid,loc,expiry,disc,
            amt,iss,void,isamt,isqty,ext,rem,encodeddate,encodedby,editdate,editby)
            select trno, line, itemid, uom,whid,loc,expiry,disc,amt, iss,void, isamt, isqty,
            ext,ifnull(rem,''), encodeddate,encodedby, editdate, editby
            from " . $this->hstock . " where trno=?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hqtaddons where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
          } else {
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from qtaddons where trno=?", 'delete', [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
          }
        } else {
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, addons problemss...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, detail problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $qryselect = "select stock.trno, stock.line, stock.itemid, item.barcode, concat(item.itemname,'\\nBrand: ', ifnull(brand.brand_desc,'')) as itemname,
      format(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt, stock.uom,
      format(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, stock.disc, stock.amt, stock.iss,
      format(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, '' as bgcolor, '' as errcolor ";
    return $qryselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
      FROM $this->stock as stock
      left join item on item.itemid=stock.itemid 
      left join frontend_ebrands as brand on brand.brandid = item.brand
      where stock.trno =? 
      UNION ALL  
      " . $sqlselect . "  
      FROM $this->hstock as stock 
      left join item on item.itemid=stock.itemid 
      left join frontend_ebrands as brand on brand.brandid = item.brand
      where stock.trno =? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
      FROM $this->stock as stock
      left join item on item.itemid=stock.itemid
      left join frontend_ebrands as brand on brand.brandid = item.brand
      where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'generateqtcomp':
        $trno = $config['params']['trno'];
        $itemid = $this->coreFunctions->getfieldvalue("qtinfo", 'itemid', 'trno=?', [$trno]);
        $components = $this->coreFunctions->opentable("select item.itemid, c.isqty, c.uom, c.cost from component as c left join item on item.barcode=c.barcode where c.itemid=?", [$itemid]);
        if (!empty($components)) {
          $ccount = count($components);
          foreach ($components as $key => $c) {
            $last_line = $this->coreFunctions->getfieldvalue('qtstock', 'line', 'trno=?', [$trno], 'line desc');
            if ($last_line == '') $last_line = 0;
            $last_line += 1;
            $data = [
              'trno' => $trno,
              'line' => $last_line,
              'itemid' => $c->itemid,
              'isqty' => $c->isqty,
              'iss' => $c->isqty,
              'uom' => $c->uom,
              'amt' => $c->cost,
              'isamt' => $c->cost,
              'ext' => ($c->cost * $c->isqty),
              'encodeddate' => $this->othersClass->getCurrentTimeStamp(),
              'encodedby' => $config['params']['user']
            ];
            $this->coreFunctions->sbcinsert($this->stock, $data);
            if (($key + 1) == $ccount) {
              $datas = $this->openstock($trno, $config);

              $gridheaddata = $this->gridheaddata($config);
              return ['status' => true, 'msg' => 'Components loaded.', 'inventory' => $datas, 'gridheaddata' => $gridheaddata];
            }
          }
        } else {
          return ['status' => false, 'msg' => 'No components setup on this item', 'inventory' => [], 'gridheaddata' => []];
        }
        break;
      case 'createversion':
        $return = $this->posttrans($config);
        if ($return['status']) {
          return $this->othersClass->createversion($config);
        } else {
          return $return;
        }
        break;
      case 'additem':
        $return =  $this->additem('insert', $config);

        return $return;
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
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
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getopsummary':
        return $this->getopsummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'copyquote':
        return $this->othersClass->generateShortcutTransaction($config);
        break;
      case 'duplicatedoc':
        return $this->othersClass->duplicateTransaction($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so 
     left join hsostock as s on s.trno = so.trno
     where so.trno = ? 
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //SO            
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
        $a = $a + 100;
      }
    }

    //SJ
    $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where stock.refx=?
    group by head.docno, head.dateid, head.trno, ar.bal
    union all 
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem, 
    head.trno
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    where stock.refx=?
    group by head.docno, head.dateid, head.trno";
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

      foreach ($t as $key => $value) {
        //CR
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ?
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ?";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $dmdata[$key2]->docno,
                'label' => $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);

    $gridheaddata = $this->gridheaddata($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);

    $gridheaddata = $this->gridheaddata($config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);

    $gridheaddata = $this->gridheaddata($config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
  } //end function

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
    } else {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0 ) $factor = $item[0]->factor;
    }
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => $computedata['amt'] * $forex,
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'uom' => $uom
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
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Uom:' . $uom . ' Amt:' . $amt . ' Disc:' . $disc . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        $this->loadheaddata($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];

    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');

    $gridheaddata = $this->gridheaddata($config);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => [], 'gridheaddata' => $gridheaddata];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' ext:' . $data[0]->ext);

    $gridheaddata = $this->gridheaddata($config);
    return ['status' => true, 'msg' => 'Item was successfully deleted.', 'gridheaddata' => $gridheaddata];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.isamt <> 0
          UNION ALL
          select head.docno,head.dateid,stock.isamt as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.isamt <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);

    $usdprice = 0;
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      $qry = "select amt,disc,uom from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);
      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, 2);
        }
      }


      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...'];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function

  public function getposummaryqry($config)
  {
    return "select
      head.docno,head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, head.cur, head.forex,
      head.shipto, head.ourref, head.yourref, head.terms, ifnull(head.branch,0) as branch,
      item.itemid,stock.trno,stock.line, item.barcode,stock.uom,stock.amt,(stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,
      round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.disc,stock.loc,stock.expiry,stock.projectid,head.shipto,
      head.agent,head.projectid as hprojectid,wh.client as swh
    FROM hqthead as head
    left join hqtstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as wh on wh.clientid=stock.whid
    where stock.trno=?";
  }

  public function getopsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    $optrno = 0;
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['stageid'] =  $data[$key2]->stageid;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from qthead as head left join qtstock as
    stock on stock.trno=head.trno where head.doc='QT' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from hqthead as head left join hqtstock as stock on stock.trno=
    head.trno where head.doc='QT' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hopstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedinvtagging($refx, $linex)
  {
  }

  // report 

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

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config, $config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
