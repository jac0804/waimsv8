<?php

namespace App\Http\Classes\modules\outsource;

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

class stockcard
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OUTSOURCE ITEMS';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'item';
  public $prefix = 'A';
  public $tablelogs = 'item_log';
  public $tablelogs_del = 'del_item_log';
  private $stockselect;

  private $fields = [
    'barcode', 'picture', 'itemname', 'uom', 'cost', 'itemrem',
    'part', 'model', 'class', 'brand', 'groupid', 'critical', 'reorder',
    'category', 'subcat', 'body', 'sizeid', 'color', 'asset', 'liability', 'revenue', 'expense',
    'isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool', 'isnoninv', 'isserial',
    'foramt',
    'supplier', 'partno', 'subcode', 'packaging', 'islabor', 'dqty', 'ispositem', 'projectid', 'isoutsource', 'moq', 'mmoq', 'inhouse', 'noncomm'
  ];
  private $except = ['itemid', 'itemrem'];
  private $blnfields = ['isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool', 'isnoninv', 'isserial', 'islabor', 'ispositem', 'noncomm'];
  private $acctg = [];
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
      'view' => 2671,
      'edit' => 131,
      'new' => 132,
      'save' => 148,
      'change' => 149,
      'delete' => 165,
      'print' => 166
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    switch ($config['params']['companyid']) {
      case 10: // for aftech
        $this->prefix = "A";
        $getcols = ['action', 'model_name', 'itemname', 'partno', 'brand', 'itemdescription', 'stockgrp_name', 'barcode', 'uom', 'amt'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[2]['label'] = 'Itemname';
        $cols[8]['label'] = 'SRP';
        $cols[6]['label'] = 'Item Group';
        $cols[8]['align'] = 'text-left';
        $cols[3]['align'] = 'text-left';
        $cols[5]['style'] = 'width:210px;whiteSpace: normal;min-width:210px;';
        $cols[7]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
        $cols[6]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[2]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
        return $cols;
        break;

      default:
        $getcols = ['action', 'barcode', 'itemname', 'uom', 'amt'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[2]['label'] = 'Itemname';
        $cols[4]['label'] = 'SRP';
        $cols[4]['align'] = 'text-left';
        return $cols;
        break;
    }
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 10 || $companyid == 12) {
      $fields = [];
      array_push($fields, 'selectprefix', 'operator', 'docno');
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'docno.type', 'input');
      data_set($col1, 'docno.label', 'Search');
      data_set($col1, 'selectprefix.label', 'Search by');
      data_set($col1, 'selectprefix.type', 'lookup');
      data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
      data_set($col1, 'selectprefix.action', 'lookupsearchby');

      data_set($col1, 'operator.label', '');
      data_set($col1, 'operator.type', 'lookup');
      data_set($col1, 'operator.lookupclass', 'lookupoperator');
      data_set($col1, 'operator.action', 'lookuprandom');

      $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix,'' as operator");

      return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
    } else {
      return [];
    }
  }

  public function loaddoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $addedfields = "";
    $limit = 'limit 5000';
    $addparams = '';

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    switch ($companyid) {
      case 10:
      case 12:
        $addedfields .= ", item.partno, brand.brand_desc as brand,item.moq,item.mmoq,left(i.itemdescription,50) as itemdescription,p.name as stockgrp_name ";
        $joins = "left join iteminfo as i on i.itemid = item.itemid left join projectmasterfile as p on p.line = item.projectid";
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if ($test['selectprefix'] != "") {
        switch ($test['selectprefix']) {
          case 'Item Code':
            if ($test['operator'] == 'Equal To') {
              $addparams = " and (item.partno = '" . $test['docno'] . "')";
            } else {
              $addparams = " and (item.partno like '%" . $test['docno'] . "%')";
            }
            break;
          case 'Item Name':
            if ($test['operator'] == 'Equal To') {
              $addparams = " and (item.itemname = '" . $test['docno'] . "')";
            } else {
              $addparams = " and (item.itemname like '%" . $test['docno'] . "%')";
            }

            break;
          case 'Model':
            if ($test['operator'] == 'Equal To') {
              $addparams =  " and (model.model_name = '" . $test['docno'] . "')";
            } else {
              $addparams = " and (model.model_name '%" . $test['docno'] . "%')";
            }
            break;
          case 'Brand':
            if ($test['operator'] == 'Equal To') {
              $addparams = " and (brand.brand_desc = '" . $test['docno'] . "')";
            } else {
              $addparams = " and (brand.brand_desc '%" . $test['docno'] . "%')";
            }

            break;
          case 'Item Group':
            if ($test['operator'] == 'Equal To') {
              $addparams = " and (p.name = '" . $test['docno'] . "')";
            } else {
              $addparams = " and (p.name '%" . $test['docno'] . "%')";
            }

            break;
        }
      }
    }

    if (isset($config['params']['search'])) {
      $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt'];
      switch ($companyid) {
        case 10:
        case 12:
          array_push($searchfield, 'item.partno', 'brand.brand_desc', 'i.itemdescription', 'model.model_name', 'p.name');
          break;
      }
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
      if ($search . $addparams == '') {
        $limit = 'limit 25';
      }
    }
    // add others link masterfile
    $qry = "select item.itemid, ifnull(model.model_name,'') as model_name, item.itemname, item.barcode, item.uom,
    FORMAT(item.amt, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt
    " . $addedfields . "
    from item
    left join item_class as cls on cls.cl_id=item.class
    left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
    left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
    left join model_masterfile as model on model.model_id = item.model
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    " . $joins . "
    where item.isoutsource = 1 " . $filtersearch . $addparams . " " . $filtersearch . "
    order by barcode  " . $limit;

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
      'toggledown',
      'others'
    );

    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'aftech' => ['label' => 'Generate CSV File', 'todo' => ['type' => 'exportcsv', 'action' => 'exportcsv', 'lookupclass' => 'exportcsv', 'access' => 'view']]
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {

    // $price = $this->tabprice($config);
    $price = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewitemprice']];

    $history = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardtransactionledger']];
    $intransaction = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardrr']];
    $warehouse = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardwh']];
    $po = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardpo']];
    $so = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardso']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryuom', 'label' => 'Uom']];
    $uom = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystocklevel', 'label' => 'StockLevel']];
    $stocklevel = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycomponent', 'label' => 'component']];
    $component = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'stockcardcompatible', 'label' => 'stockcardcompatible']];
    $compatible = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysku', 'label' => 'sku']];
    $sku = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystockprice', 'label' => 'Stock Price']];
    $stockprice = $this->tabClass->createtab($tab, []);

    $iteminfod = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewiteminfo']];

    $return = [];
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // for aftech
      $return['ITEM INFORMATION'] = ['icon' => 'fa fa-address-book', 'customform' => $iteminfod];
    }

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
      $return['STOCK PRICE'] = ['icon' => 'fa fa-tags', 'tab' => $stockprice];
    }

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
      $tab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewoscompute']];
      $return['OS COMPUTE'] = ['icon' => 'fa fa-calculator', 'customform' => $tab];
    }

    $return['ITEM PRICE'] = ['icon' => 'fa fa-tags', 'customform' => $price];
    $return['HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $history];
    $return['IN-TRANSACTION'] = ['icon' => 'fa fa-inbox', 'customform' => $intransaction];
    $return['UNIT OF MEASUREMENT'] = ['icon' => 'fa fa-weight', 'tab' => $uom];
    $return['BALANCE PER WAREHOUSE'] = ['icon' => 'fa fa-warehouse', 'customform' => $warehouse];
    $return['PURCHASE ORDER HISTORY'] = ['icon' => 'fa fa-shopping-basket', 'customform' => $po];
    $return['SALES ORDER HISTORY'] = ['icon' => 'fa fa-cart-arrow-down', 'customform' => $so];
    $return['COMPONENT'] = ['icon' => 'fa fa-drafting-compass', 'tab' => $component];
    $return['STOCK LEVEL'] = ['icon' => 'fa fa-level-up-alt', 'tab' => $stocklevel];
    $return['COMPATIBLE'] = ['icon' => 'fa fa-ethernet', 'tab' => $compatible];
    $return['EQUIVALENT SKU'] = ['icon' => 'fa fa-equals', 'tab' => $sku];
    if ($this->companysetup->getserial($config['params'])) {
      $serial = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewserialhistory']];
      $return['SERIAL HISTORY'] = ['icon' => 'fa fa-qrcode', 'customform' => $serial];
    }

    return $return;
  }


  public function tabprice($config)
  {
    // $fields = ['amt', 'amt2', 'famt', 'amt4', 'amt5'];
    // $col1 = $this->fieldClass->create($fields);
    // data_set($col1, 'amt.type', 'cinput');
    // data_set($col1, 'amt2.type', 'cinput');
    // data_set($col1, 'famt.type', 'cinput');
    // data_set($col1, 'amt4.type', 'cinput');
    // data_set($col1, 'amt5.type', 'cinput');
    // data_set($col1, 'amt4.label', 'TP dollar');

    // $fields = ['disc', 'disc2', 'disc3', 'disc4', 'disc5'];
    // $col2 = $this->fieldClass->create($fields);
    // data_set($col2, 'disc.type', 'cinput');
    // data_set($col2, 'disc2.type', 'cinput');
    // data_set($col2, 'disc3.type', 'cinput');
    // data_set($col2, 'disc4.type', 'cinput');
    // data_set($col2, 'disc5.type', 'cinput');

    // $fields = ['amt6', 'amt7', 'amt8', 'amt9'];
    // $col3 = $this->fieldClass->create($fields);
    // data_set($col3, 'amt6.type', 'cinput');
    // data_set($col3, 'amt7.type', 'cinput');
    // data_set($col3, 'amt8.type', 'cinput');
    // data_set($col3, 'amt9.type', 'cinput');

    // $fields = ['disc6', 'disc7', 'disc8', 'disc9']; //, 'uploadexcel', 'exportcsv','readfile'
    // $col4 = $this->fieldClass->create($fields);
    // data_set($col4, 'disc6.type', 'cinput');
    // data_set($col4, 'disc7.type', 'cinput');
    // data_set($col4, 'disc8.type', 'cinput');
    // data_set($col4, 'disc9.type', 'cinput');

    // $tab = [
    //   'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4], 'label' => 'PRICE'],
    // ];
    // $obj = $this->tabClass->createtab($tab, []);
    
    return [];
  }

  public function createTab($config)
  {

    // -> switch comp this
    //$tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'entrysku', 'label' => 'SKU'];

    //$stockbuttons = [];
    //$obj = $this->tabClass->createtab($tab, $stockbuttons);
    return [];
  }

  public function createtabbutton($config)
  {
    //if ($this->companysetup->getserial($config['params'])) {
    //  $tbuttons = ['viewstockcardtransactionledger', 'viewstockcardrr', 'entrystockcarduom', 'viewstockcardwh', 'viewstockcardpo', 'viewstockcardso', 'viewstockcardstocklevel', 'viewserialhistory', 'entrystockcardcompatible'];
    //} else {
    //  $tbuttons = ['viewstockcardtransactionledger', 'viewstockcardrr', 'entrystockcarduom', 'viewstockcardwh', 'viewstockcardpo', 'viewstockcardso', 'viewstockcardcomponent', 'viewstockcardstocklevel', 'entrystockcardcompatible'];
    //}
    //$obj = $this->tabClass->createtabbutton($tbuttons);
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $isserial = $this->companysetup->getserial($config['params']);

    $fields = ['barcode', 'partno', 'itemname', 'uom', 'dclientname', 'moq', 'mmoq', 'inhouse'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'barcode.lookupclass', 'lookupbarcode');
    data_set($col1, 'barcode.required', true);
    data_set($col1, 'itemname.type', 'cinput');
    data_set($col1, 'uom.type', 'cinput');
    data_set($col1, 'critical.type', 'cinput');
    data_set($col1, 'reorder.type', 'cinput');
    data_set($col1, 'dclientname.lookupclass', 'stockcardsupplier');

    data_set($col1, 'cost.label', 'Fixed Cost');

    switch ($systemtype) {
      case 'WAIMS':
        switch ($companyid) {
          case '6':
            $fields = ['categoryname', 'subcatname', 'modelname', 'brandname', 'stockgrp', 'packaging', 'dqty'];
            break;
          default:
            $fields = ['categoryname', 'subcatname', 'partname', 'modelname', 'classname', 'brandname', 'stockgrp', 'packaging'];
            break;
        }
        break;
      default:
        $fields = ['partname', 'modelname', 'classname', 'brandname', 'stockgrp', 'categoryname', 'subcatname'];
        break;
    }

    if ($companyid == 10 || $companyid == 12) {
      array_push($fields, 'projectname');
    }

    $col2 = $this->fieldClass->create($fields);
    // data_set($col2, 'categoryname.name', 'category');
    // data_set($col2, 'categoryname.action', 'lookupcategoryitem');
    // data_set($col2, 'categoryname.lookupclass', 'lookupcategoryitem');
    data_set($col2, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

    data_set($col2, 'dqty.label', 'Per Box/Pack/Set');
    data_set($col2, 'projectname.type', 'lookup');
    data_set($col2, 'projectname.action', 'lookupproject');
    data_set($col2, 'projectname.lookupclass', 'stockcardproject');
    data_set($col2, 'projectname.label', 'Item Group');
    data_set($col2, 'projectname.required', true);


    $fields = ['body', 'sizeid', 'color', 'dasset', 'dliability', 'drevenue', 'dexpense', 'foramt'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'foramt.type', 'cinput');

    if ($companyid == '1') {
      data_set($col3, 'body.type', 'input');
      data_set($col3, 'body.class', '');
      data_set($col3, 'body.label', 'Old barcode');
    }

    switch ($companyid) {
      case '6': // mitsukoshi
        $fields = ['picture', 'rem', ['isinactive', 'isvat'], ['isimport', 'fg_isfinishedgood'], ['fg_isequipmenttool', 'isnoninv']];
        break;
      default:
        $fields = ['picture', 'rem', ['isinactive', 'isvat'], ['isimport', 'fg_isfinishedgood'], ['fg_isequipmenttool', 'isnoninv'], ['islabor', 'ispositem'], 'noncomm'];
        break;
    }
    if ($isserial) {
      array_push($fields, 'isserial');
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.name', 'itemrem');
    data_set($col4, 'rem.label', 'Item Remark');
    //data_set($col4, 'rem.type', 'wysiwyg');
    data_set($col4, 'picture.folder', 'product');
    data_set($col4, 'picture.table', 'item');
    data_set($col4, 'picture.fieldid', 'itemid');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newstockcard($config)
  {
    $companyid = $config['params']['companyid'];
    $data[0]['itemid'] = 0;
    $data[0]['barcode'] = $config['newbarcode'];
    $data[0]['itemname'] = '';
    if ($companyid == 10 || $companyid == 12) {
      $data[0]['uom'] = 'EA';
      $data[0]['itemname'] = 'A' . $config['barcodeseq'];
      $data[0]['partno'] = 'A-A' . $config['barcodeseq'];
      $data[0]['inhouse'] = 'A' . $config['barcodeseq'];
    } else {
      $data[0]['uom'] = 'PCS';
      $data[0]['inhouse'] = '';
      $data[0]['partno'] = '';
    }


    $data[0]['itemrem'] = '';
    $data[0]['partname'] = '';
    $data[0]['part'] = '0';
    $data[0]['modelname'] = '';
    $data[0]['model'] = '0';
    $data[0]['classic'] = '';
    $data[0]['class'] = '0';
    $data[0]['brand'] = '0';
    $data[0]['brandname'] = '';
    $data[0]['groupid'] = '0';
    $data[0]['stockgrp'] = '';
    $data[0]['critical'] = '';
    $data[0]['reorder'] = '';
    $data[0]['category'] = '0';
    $data[0]['categoryname'] = '';
    $data[0]['body'] = '';
    $data[0]['sizeid'] = '';
    $data[0]['asset'] = '';
    $data[0]['assetname'] = '';
    $data[0]['liability'] = '';
    $data[0]['liabilityname'] = '';
    $data[0]['revenue'] = '';
    $data[0]['revenuename'] = '';
    $data[0]['expense'] = '';
    $data[0]['expensename'] = '';
    $data[0]['isinactive'] = '0';
    $data[0]['isvat'] = '0';
    $data[0]['isimport'] = '0';
    $data[0]['isnoninv'] = '0';
    $data[0]['isserial'] = '0';
    $data[0]['ispositem'] = '0';

    switch ($companyid) {
      case '6':
        $data[0]['fg_isfinishedgood'] = '1';
        $data[0]['islabor'] = '0';
        break;
      default:
        $data[0]['fg_isfinishedgood'] = '0';
        $data[0]['islabor'] = '0';
        break;
    }

    $data[0]['fg_isequipmenttool'] = '0';
    $data[0]['amt'] = '';
    $data[0]['amt2'] = '';
    $data[0]['famt'] = '';
    $data[0]['amt4'] = '';
    $data[0]['amt5'] = '';
    $data[0]['amt6'] = '';
    $data[0]['amt7'] = '';
    $data[0]['amt8'] = '';
    $data[0]['amt9'] = '';
    $data[0]['disc'] = '';
    $data[0]['disc2'] = '';
    $data[0]['disc3'] = '';
    $data[0]['disc4'] = '';
    $data[0]['disc5'] = '';
    $data[0]['disc6'] = '';
    $data[0]['disc7'] = '';
    $data[0]['disc8'] = '';
    $data[0]['disc9'] = '';
    $data[0]['foramt'] = 0;
    $data[0]['picture'] = '';
    $data[0]['supplier'] = 0;
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['subcode'] = '';
    $data[0]['packaging'] = '';
    $data[0]['cost'] = 0;
    $data[0]['subcat'] = '0';
    $data[0]['subcatname'] = '';
    $data[0]['color'] = '';
    $data[0]['dqty'] = 0;

    $data[0]['projectid'] = 0;
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['dasset'] = '';
    $data[0]['dliability'] = '';
    $data[0]['dexpense'] = '';
    $data[0]['drevenue'] = '';
    $data[0]['isoutsource'] = '1';
    $data[0]['noncomm'] = '0';
    $data[0]['moq'] = '0';
    $data[0]['mmoq'] = '0';


    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];

    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    if ($itemid == 0) {
      $itemid = $this->othersClass->readprofile($doc, $config);
      if ($itemid == 0) {
        $itemid = $this->coreFunctions->datareader("select itemid as value from item where isinactive=0 order by itemid desc limit 1");
      }
      $config['params']['itemid'] = $itemid;
    } else {
      $this->othersClass->checkprofile($doc, $itemid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'item.itemid';
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',item.' . $value;
    }
    $qryselect = "select " . $fields . ", ifnull(pmaster.part_name,'') as partname, item.part as partid,
        ifnull(mmaster.model_name,'') as modelname, item.model as model,
        ifnull(itemclass.cl_name,'') as classname,item.class as class,
        ifnull(brand.brand_desc,'') as brandname, ifnull(item.brand,'') as brand,
        ifnull(stockgrp.stockgrp_name,'') as stockgrp, item.groupid as groupid, item.groupid as grid,
        cat.line as category,
        cat.name as categoryname,
        subcat.line as subcat,
        subcat.name as subcatname,
        ifnull(coa1.acnoname,'')  as assetname,
        ifnull(coa2.acnoname,'')  as liabilityname,
        ifnull(coa3.acnoname,'')  as revenuename,
        ifnull(coa4.acnoname,'')  as expensename,
        ifnull(cl.client, '') as client, ifnull(cl.clientname, '') as clientname,
        ifnull(cl.clientid, 0) as supplier, item.partno, item.packaging,
        ifnull(prj.code, '') as projectcode,
        ifnull(prj.name, '') as projectname,
        '' as dasset,
        '' as dliability,
        '' as dexpense,
        '' as drevenue";

    $qry = $qryselect . " from item
        left join part_masterfile as pmaster on pmaster.part_id = item.part
        left join model_masterfile as mmaster on mmaster.model_id = item.model
        left join item_class as itemclass on itemclass.cl_id = item.class
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join coa as coa1 on coa1.acno = item.asset
        left join coa as coa2 on coa2.acno = item.liability
        left join coa as coa3 on coa3.acno = item.revenue
        left join coa as coa4 on coa4.acno = item.expense
        left join client as cl on cl.clientid = item.supplier
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join projectmasterfile as prj on prj.line = item.projectid
        where item.itemid = ? and isoutsource = 1";

    $head = $this->coreFunctions->opentable($qry, [$itemid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['itemid' => $itemid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['itemid']];
    } else {
      $head[0]['itemid'] = 0;
      $head[0]['barcode'] = '';
      $head[0]['itemname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[0]); // barcode
      unset($this->fields[1]); // picture
    }
    $itemid = 0;
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if
      }
    }


    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {

      $current_uom = $this->coreFunctions->getfieldvalue("item", "uom", "itemid=?", [$head['itemid']]);
      if ($current_uom != '' && $current_uom != $head['uom']) {
        if ($this->checkuomtransaction($head['itemid'], $current_uom)) {
          unset($data['uom']);
        } else {
          $isexistinguom = $this->coreFunctions->getfieldvalue("uom", "uom", "itemid=? and uom=?", [$head['itemid'], $head['uom']]);
          if (empty($isexistinguom)) {
            $this->coreFunctions->LogConsole('delete ' . $current_uom);
            $this->coreFunctions->execqry('delete from uom where itemid=? and uom=?', 'DELETE', [$head['itemid'], $current_uom]);
            $this->coreFunctions->execqry('insert into uom (itemid,uom,factor) values(?,?,1)', 'INSERT', [$head['itemid'], $data['uom']]);
          }
          // else {
          //   $this->coreFunctions->sbcupdate('uom', ['isdefault' => 0], ['itemid' => $head['itemid']]);
          //   $this->coreFunctions->sbcupdate('uom', ['isdefault' => 1], ['itemid' => $head['itemid'], 'uom' => $head['uom'] ]);
          // }
          // $this->coreFunctions->execqry('delete from uom where itemid=? and uom=?', 'DELETE', [$head['itemid'], $current_uom]);
          // $this->coreFunctions->execqry('insert into uom (itemid,uom,factor) values(?,?,1)', 'INSERT', [$head['itemid'], $data['uom']]);
        }
      }

      $current_noninv = $this->coreFunctions->getfieldvalue("item", "isnoninv", "itemid=?", [$head['itemid']]);
      if ($current_noninv != $head['isnoninv']) {
        $this->coreFunctions->LogConsole('Current Non Inventory:' . $current_noninv . ' - New:' . $data['isnoninv']);
        if ($this->checkitemtransaction($head['itemid'], $current_noninv)) {
          $bal = $this->coreFunctions->datareader("select sum(bal) as value from rrstatus where itemid = " . $head['itemid']);
          if (floatval($bal) <> 0) {
            $this->coreFunctions->LogConsole('Current Non Inventory:' . $current_noninv . ' existing transaction and balance');
            if ($current_noninv != $data['isnoninv']) {
              unset($data['isnoninv']);
              $this->logger->sbcwritelog($head['itemid'], $config, 'Update', "Change Non Inventory tagging failed.");
            }
          }
        }
      }

      $this->coreFunctions->sbcupdate('item', $data, ['itemid' => $head['itemid']]);
      $itemid = $head['itemid'];
      array_push($this->fields, 'barcode');
      array_push($this->fields, 'picture');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $itemid = $this->coreFunctions->insertGetId('item', $data);
      $this->coreFunctions->execqry('insert into uom(itemid,uom,factor) values(?,?,1)', 'INSERT', [$itemid, $data['uom']]);
      $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $head['barcode'] . ' - ' . $head['itemname']);
    }
    return $itemid;
  } // end function

  public function getlastbarcode($pref, $config = [])
  {
    $length = strlen($pref);
    $return = '';
    $return = $this->coreFunctions->datareader('select barcode as value from item 
        where left(barcode,' . strlen($this->prefix) . ')="' . $this->prefix . '" and isoutsource =1  order by barcode desc limit 1');
    // if ($length == 0) {
    //   $return = $this->coreFunctions->datareader('select barcode as value from item 
    //     where left(barcode,'.strlen($this->prefix).')="'.$this->prefix.'" and isoutsource =1  order by barcode desc limit 1');
    // } else {
    //   $return = $this->coreFunctions->datareader('select barcode as value from item where  left(barcode,?)=? and isoutsource =1 order by barcode desc limit 1', [$length, $pref]);
    // }
    return $return;
  }

  private function checkitemtransaction($itemid, $uom)
  {
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
    $qry = "
        select stock.trno from lastock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "'
        union all        
        select stock.trno from glstock as stock  where stock.itemid=" . $itemid;
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }

  private function checkuomtransaction($itemid, $uom)
  {
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
    $qry = "
        select stock.trno from lastock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "' 
        union all
        select stock.trno from postock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "' 
        union all
        select stock.trno from hpostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "' 
        Union all
        select stock.trno from qsstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "' 
        union all
        select stock.trno from hqsstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "' 
        union all
        select stock.trno from sostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "'  
        union all
        select stock.trno from hsostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom . "'  
        union all
        select stock.trno from glstock as stock  where stock.itemid=" . $itemid . " and stock.uom='" . $uom . "'                                   
    ";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'uploadexceltemplate':
        $origdata = $config['params']['data'];
        $data = [];
        foreach ($origdata as $key => $value) {
          $data[$key] = $value['serial'];
        }
        return ['status' => true, 'msg' => 'Success', 'data' => $data];
        break;
      case 'exportcsv':
        $trno = $config['params']['trno'];
        $config['params']['itemid'] = $trno;
        $data = $this->loadheaddata($config);
        $bcode  = preg_replace('/[^0-9]/', '', $data['head'][0]->barcode);
        $itemdesc = $this->coreFunctions->getfieldvalue('iteminfo', 'itemdescription', 'itemid=?', [$data['head'][0]->itemid]);
        $accessories = $this->coreFunctions->getfieldvalue('iteminfo', 'accessories', 'itemid=?', [$data['head'][0]->itemid]);
        $csv = "partno,barcode,pref,itemname,project,uom,brand,model,description,accessories,isoutsource,isservice,isserial,noninventory\n";
        $csv .= $data['head'][0]->partno . ',' . $bcode . ',' . $data['head'][0]->barcode[0] . ',' . $data['head'][0]->itemname . ',' . $data['head'][0]->projectname . ',' . $data['head'][0]->uom . ',' .
          $data['head'][0]->brandname . ',' . $data['head'][0]->modelname . ',"' . $itemdesc . '",' . $accessories . ',' . $data['head'][0]->isoutsource . ',' . $data['head'][0]->islabor . ',' . $data['head'][0]->isserial . ',' . $data['head'][0]->isnoninv;
        return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => $this->modulename, 'csv' => $csv];
        break;
      case 'readfile':
        $csv = $config['params']['csv'];
        $arrcsv = explode("\r\n", $csv);
        return ['status' => true, 'msg' => 'Readfile Successfully', 'data' => $arrcsv];
        break;
    }
  }




  public function deletetrans($config)
  {
    $itemid = $config['params']['itemid'];
    $doc = $config['params']['doc'];
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
    $qry = "select lastock.trno from lastock where itemid=?
            union all
            select glstock.trno from glstock where itemid=?
            union all
            select sostock.trno from sostock where itemid=?
            union all
            select hsostock.trno from hsostock where itemid=?
            union all
            select postock.trno from postock where itemid=?
            union all
            select hpostock.trno from hpostock where itemid=?
             limit 1";
    $count = $this->coreFunctions->datareader($qry, [$itemid, $itemid, $itemid, $itemid, $itemid, $itemid]);
    if (($count != '')) {
      return ['itemid' => $itemid, 'status' => false, 'msg' => 'Already have transaction...'];
    }
    $qry = "select itemid as value from item where itemid<? and isinactive=0 and isoutsource=1 order by itemid desc limit 1 ";
    $itemid2 = $this->coreFunctions->datareader($qry, [$itemid]);
    $this->coreFunctions->execqry('delete from item where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from uom where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from component where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from itemlevel where itemid=?', 'delete', [$itemid]);
    $this->logger->sbcdel_log($itemid, $config, $barcode);
    return ['itemid' => $itemid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['radiotypeofreport', 'start', 'end', 'wh', 'luom', 'loc', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'wh.lookupclass', 'whs');
    data_set($col1, 'wh.required', true);
    data_set($col1, 'luom.lookupclass', 'uoms');
    data_set($col1, 'luom.required', true);
    data_set($col1, 'loc.lookupclass', 'locs');
    data_set(
      $col1,
      'radiotypeofreport.options',
      [
        ['label' => 'Ledger Report', 'value' => 'ledger', 'color' => 'orange'],
        ['label' => 'Receiving Report', 'value' => 'receiving', 'color' => 'orange'],
        ['label' => 'Purchase Order Report', 'value' => 'po', 'color' => 'orange'],
        ['label' => 'Sales Order Report', 'value' => 'so', 'color' => 'orange']
      ]
    );

    if ($config['params']['companyid'] == 10) { // afti
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }


    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select
    'default' as print,
    'ledger' as typeofreport,
    '' as start,
    '' as end,
    '' as wh,
    '' as loc,
    '' as uom,
    '' as prepared,
    '' as approved,
    '' as received
  ");
  }

  public function QUERY_RESULT($config)
  {
    $reporttype = $config['params']['dataparams']['typeofreport'];

    switch ($reporttype) {
      case 'ledger':
        $query = $this->QUERY_LEDGER($config);
        break;
      case 'receiving':
        $query = $this->QUERY_RECEIVING($config);
        break;
      case 'po':
        $query = $this->QUERY_PO($config);
        break;
      case 'so':
        $query = $this->QUERY_SO($config);
        break;
    }
    
    return $this->coreFunctions->opentable($query);
  }

  public function QUERY_LEDGER($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom       = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $loc = '';
    if ($location != '') {
      $loc = 'and stock.loc = "' . $location . '"';
    }

    $query = "select '' as expiry, '' as posted,  '' as itemname,  '' as barcode, 0 as trno, '' as doc, 'beginning bal.' as docno,null as  dateid, 0 as cost, 0 as rrcost, 0 as qty,
    '' as yourref, '' as ourref,0 as  amt, 0 as iss, '' as disc, md5(itemid) as itemid,'' as  wh,
    '' as loc, '' as type, '' as isimport, 0 as line, 0 as cur, '' as forex,
    0 as factor, '' as rem, '' as encoded, '' as client, '' as clientname, '' as addr, '' as tel,
    '' as  email, '' as tin, '' as mobile, '' as contact, '' as fax, sum(qty-iss) as bal from (

    select '' as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid
    where md5(item.itemid)='$itemid'
    and head.dateid between '$start' and '$end' and wh.client='$whby' $loc
    union all
    select '' as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join cntnum on cntnum.trno=head.trno
    left join client on client.client=head.client
    left join client as wh on wh.clientid=stock.whid
    where md5(item.itemid)='$itemid'
    and head.dateid between '$start' and '$end' and wh.client='$whby' $loc
    order by dateid,trno
    ) as ledger
    group by ledger.itemid

    UNION ALL

    select stock.expiry as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,0 as bal
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid
    where md5(item.itemid)='$itemid'
    and head.dateid between '$start' and '$end' and wh.client='$whby' $loc
    union all
    select stock.expiry as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,0 as bal
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join cntnum on cntnum.trno=head.trno
    left join client on client.client=head.client
    left join client as wh on wh.clientid=stock.whid
    where md5(item.itemid)='$itemid'
    and head.dateid between '$start' and '$end' and wh.client='$whby' $loc
    group by
    stock.expiry,
    item.itemname,
    item.barcode,
    head.trno,
    head.doc,
    head.docno,
    head.dateid,
    stock.disc,
    item.itemid,
    wh.client,
    stock.loc,
    head.isimport,
    stock.line,
    head.cur,
    head.forex,
    head.factor,
    stock.rem,
    stock.encodeddate,
    client.client,
    client.clientname,
    client.addr,
    client.tel,
    client.email,
    client.tin,
    client.mobile,
    client.contact,
    client.fax,
    uom.factor,
    stock.cost,
    stock.rrcost,
    stock.qty,
    stock.amt,
    stock.iss,
    head.yourref,
    head.ourref
    order by dateid,trno";

    return $query;
  }

  public function QUERY_RECEIVING($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom       = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $loc = '';
    if ($location != '') {
      $loc = 'and stock.loc = "' . $location . '"';
    }

    $query = "select cntnum.doc, rrstatus.trno, rrstatus.line,
  client.clientname,
  rrstatus.cost,
  (rrstatus.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
  cast((case when rrstatus.bal=0 then 'applied' else round((rrstatus.bal / (case when ifnull(uom.factor, 0)=0 then 1
  else uom.factor end)),2) end) as char(50)) as status, date(rrstatus.dateid) as dateid,
  rrstatus.whid, rrstatus.uom, rrstatus.disc,
  rrstatus.docno, rrstatus.loc, wh.clientname as whname, stock.rrcost, head.cur, head.forex, item.isinactive, item.isimport,
  item.barcode, item.itemname, brand.brand_desc as brand, model.model_name as model, part.part_name as part, item.sizeid,
  item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
  item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
  from ((((((rrstatus left join client on client.clientid=rrstatus.clientid) left join client as wh on wh.clientid=rrstatus.whid)
  left join item on item.itemid=rrstatus.itemid) left join uom on uom.itemid=rrstatus.itemid and uom.uom='$uom')
  left join cntnum on cntnum.trno=rrstatus.trno) left join glhead as head on head.trno=rrstatus.trno)
  left join glstock as stock on stock.trno=rrstatus.trno and stock.line=rrstatus.line
  left join frontend_ebrands as brand on brand.brandid = item.brand
  left join part_masterfile as part on part.part_id = item.part
  left join model_masterfile as model on model.model_id = item.model
  where md5(rrstatus.itemid)='$itemid' and wh.client='$whby' and rrstatus.dateid between '$start' and '$end'  $loc
  order by rrstatus.dateid";

    return $query;
  }

  public function QUERY_PO($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $query = "select pohead.trno, pohead.doc, pohead.docno, date(pohead.dateid) as dateid, pohead.clientname,
  (postock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
  (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
  item.barcode, item.itemname,
  brand.brand_desc as brand, model.model_name as model, part.part_name as part,
  item.sizeid,
  item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
  item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
  from ((postock left join pohead on pohead.trno=postock.trno) left join item
  on item.itemid=postock.itemid) left join uom on uom.itemid=item.itemid
  and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = pohead.trno
  left join client as wh on wh.clientid=postock.whid
  left join frontend_ebrands as brand on brand.brandid = item.brand
  left join part_masterfile as part on part.part_id = item.part
  left join model_masterfile as model on model.model_id = item.model
   where md5(item.itemid)='$itemid' and wh.client ='$whby'
  and pohead.dateid between '$start' and '$end'
  group by

  pohead.trno, pohead.doc, pohead.docno, pohead.dateid, clientname,
  item.isinactive, item.isimport,
  item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
  item.amt, item.disc, item.amt2, item.disc2,
  item.famt, item.disc3, item.amt4, postock.qty, uom.factor,postock.qa

  union all
  select hpohead.trno, hpohead.doc, hpohead.docno, date(hpohead.dateid) as dateid, hpohead.clientname,
  (hpostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
  (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
  item.barcode, item.itemname,
  brand.brand_desc as brand, model.model_name as model, part.part_name as part,
  item.sizeid,
  item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
  item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
  from ((hpostock left join hpohead on hpohead.trno=hpostock.trno) left join item
  on item.itemid=hpostock.itemid) left join uom on uom.itemid=item.itemid
  and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = hpohead.trno
  left join client as wh on wh.clientid=hpostock.whid
  left join frontend_ebrands as brand on brand.brandid = item.brand
  left join part_masterfile as part on part.part_id = item.part
  left join model_masterfile as model on model.model_id = item.model
   where md5(item.itemid)='$itemid' and wh.client ='$whby'
  and hpohead.dateid between '$start' and '$end'
  group by

  hpohead.trno, hpohead.doc, hpohead.docno, hpohead.dateid, clientname,
  item.isinactive, item.isimport,
  item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
  item.amt, item.disc, item.amt2, item.disc2,
  item.famt, item.disc3, item.amt4, hpostock.qty, uom.factor,hpostock.qa
  order by dateid";

    return $query;
  }

  public function QUERY_SO($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $query = "select sohead.trno, sohead.doc, sohead.docno, date(sohead.dateid) dateid, sohead.clientname,
    (sostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
    (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
    item.barcode, item.itemname, brand.brand_desc as brand, model.model_name as model, part.part_name as part, item.sizeid,
    item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
    item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
    from ((sostock
    left join sohead on sohead.trno=sostock.trno)
    left join item on item.itemid=sostock.itemid)
    left join uom on uom.itemid=item.itemid and uom.uom='$uom'
    left join transnum as cntnum on cntnum.trno = sohead.trno
    left join client as wh on wh.clientid=sostock.whid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join part_masterfile as part on part.part_id = item.part
    left join model_masterfile as model on model.model_id = item.model
     where md5(item.itemid)='$itemid'
    and wh.client ='$whby' and sohead.dateid between '$start' and '$end'
    group by
    sohead.trno, sohead.doc, sohead.docno, sohead.dateid,
    sostock.iss, uom.factor,sostock.qa,
    sohead.clientname, item.isinactive, item.isimport,
    item.barcode, item.itemname, model.model_name, part.part_name, brand.brand_desc, item.sizeid,
    item.amt, item.disc, item.amt2, item.disc2,
    item.famt , item.disc3, item.amt4
    union all
    select hsohead.trno, hsohead.doc, hsohead.docno, date(hsohead.dateid) as dateid,
    hsohead.clientname, (hsostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
    (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
    item.barcode, item.itemname, model.model_name as model, part.part_name as part, brand.brand_desc as brand, item.sizeid,
    item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
    item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
    from ((hsostock
    left join hsohead on hsohead.trno=hsostock.trno)
    left join item on item.itemid=hsostock.itemid)
    left join uom on uom.itemid=item.itemid and uom.uom='$uom'
    left join transnum as cntnum on cntnum.trno = hsohead.trno
    left join client as wh on wh.clientid=hsostock.whid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join part_masterfile as part on part.part_id = item.part
    left join model_masterfile as model on model.model_id = item.model
    where md5(item.itemid)='$itemid' and wh.client ='$whby' and hsohead.dateid between '$start' and '$end'
    group by
    hsohead.trno, hsohead.doc, hsohead.docno, hsohead.dateid,
    hsostock.iss, uom.factor,hsostock.qa,
    hsohead.clientname, item.isinactive, item.isimport,
    item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
    item.amt, item.disc, item.amt2, item.disc2,
    item.famt , item.disc3, item.amt4
    order by dateid";

    return $query;
  }


  public function reportdata($config)
  {
    $reporttype = $config['params']['dataparams']['typeofreport'];

    switch ($reporttype) {
      case 'ledger':
        $str = $this->report_default_LEDGER($config);
        break;
      case 'receiving':
        $str = $this->report_default_RECEIVING($config);
        break;
      case 'po':
        $str = $this->report_default_PO($config);
        break;
      case 'so':
        $str = $this->report_default_SO($config);
        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function report_default_LEDGER($config)
  {
    $result = $this->QUERY_RESULT($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];


    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Avenir";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD LEDGER  ', null, null, false, $border, '', '', $font, '18', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE :' . (isset($result[1]->barcode) ? $result[1]->barcode : ''), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '525', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM NAME :', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col((isset($result[1]->itemname) ? $result[1]->itemname : '') . '', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('DATE RANGE: ' . $start . ' TO ' . $end, '525', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->col('WAREHOUSE: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col($wh, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('UOM: ' . $uom, '325', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');

    $sql = "select factor from uom where itemid = '$itemid' and uom = '$uom'";
    $uomfactor = $this->coreFunctions->opentable($sql);

    $str .= $this->reporter->col('FACTOR: ' . number_format($uomfactor[0]->factor, 2), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '75', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('CLIENT NAME', '230', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('EXPIRY', '70', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('QTY IN ', '75', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('QTY OUT ', '75', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('PARTICULAR', '95', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');

    $bal = 0;
    $totaliss = 0;
    $totalqty = 0;
    $tobal = 0;
    $bal = 0;
    $i = 0;
    foreach ($result as $key => $data) {
      $qty = $data->qty;

      if ($qty == 0) { // change to zero para lumabas yung may mga decimal na qty
        $qty = '-';
      } //end if

      $iss = $data->iss;

      if ($iss == 0) {
        $iss = '-';
      } //end if

      /*if($bal==0){
          $bal = number_format($data->bal,2);
      }//end if*/

      //$bal=$bal+($data->qty-$data->iss);

      if ($i == 0) {
        $bal = $data->bal;
      } else {
        $bal = $bal - $iss;
        $bal = $bal + $qty;
      } //end if

      $tobal = $bal;

      if ($tobal == 0) {
        $tobal = '-';
      } else {
        $tobal = $tobal * -1;
        $tobal = number_format($tobal, 2);
      } //end if


      $str .= $this->reporter->startrow();
      if ($data->docno == 'beginning bal.') {
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '230', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->bal, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp;' . $data->clientname, '230', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->expiry, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($qty, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($iss, '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($tobal, '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '95', null, false, '1psx solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      } //end if
      $totaliss = $totaliss + $iss;
      $totalqty = $totalqty + $qty;
      $i++;
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '75', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp;', '230', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp;', '70', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL QTY : ', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalqty, 2), '65', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totaliss, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($tobal, '70', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp;', '95', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '350', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '225', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '225', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '350', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_RECEIVING($config)
  {
    $result = $this->QUERY_RESULT($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    // $loggeduser = Yii::$app->session['loggeduser']['name'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD - RECEIVING ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('View Accounts from :', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($start . ' to ' . $end, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('View by Unit :', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->barcode) ? $result[0]->barcode : ''), '375', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Price Levels', '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Name:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->itemname) ? $result[0]->itemname : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Retail:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->priceretail) ? number_format($result[0]->priceretail, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    // $str .= $this->reporter->col('Disc 1:','75',null,false,$border,'','L',$font,$fontsize,'','','1px');
    $str .= $this->reporter->col((isset($result[0]->discretail) ? $result[0]->discretail : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->brand) ? $result[0]->brand : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Wholesale:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricewhole) ? number_format($result[0]->pricewhole, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discwhole) ? $result[0]->discwhole : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->model) ? $result[0]->model : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp1) ? number_format($result[0]->pricegrp1, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 3:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp1) ? $result[0]->discgrp1 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('773');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part#:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->part) ? $result[0]->part : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp2) ? number_format($result[0]->pricegrp2, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 4:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp2) ? $result[0]->discgrp2 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Size:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->sizeid) ? $result[0]->sizeid : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isinactive) ? $result[0]->isinactive : '') == 1) {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isimport) ? $result[0]->isimport : '') == 1) {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Name', '175', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Exch Rate', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Purch. Cost', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Landed Cost', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    $totalqty = 0;
    $totalstatus = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '175', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->forex, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrcost, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->status, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $totalqty = $totalqty + $data->qty;
      $totalstatus = $totalstatus + $data->status;
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalstatus, 2), '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '350', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '225', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '225', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '350', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_PO($config)
  {
    $result = $this->QUERY_RESULT($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    //if (Yii::$app->backend->getcompanyid() == 1) {
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD - PO ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('View Accounts from :', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($start . ' to ' . $end, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('View by Unit :', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->barcode) ? $result[0]->barcode : ''), '375', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Price Levels', '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Name:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->itemname) ? $result[0]->itemname : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Retail:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->priceretail) ? number_format($result[0]->priceretail, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discretail) ? $result[0]->discretail : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->brand) ? $result[0]->brand : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Wholesale:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricewhole) ? number_format($result[0]->pricewhole, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discwhole) ? $result[0]->discwhole : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->model) ? $result[0]->model : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp1) ? number_format($result[0]->pricegrp1, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 3:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp1) ? $result[0]->discgrp1 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('773');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part#:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->part) ? $result[0]->part : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp2) ? number_format($result[0]->pricegrp2, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 4:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp2) ? $result[0]->discgrp2 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Size:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->sizeid) ? $result[0]->sizeid : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isinactive) ? $result[0]->isinactive : '') == 1) {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isimport) ? $result[0]->isimport : '') == 1) {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Name', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ordered', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Received', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qa, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_SO($config)
  {
    $result = $this->QUERY_RESULT($config);
    
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD - SO ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('View Accounts from :', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($start . ' to ' . $end, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('View by Unit :', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->barcode) ? $result[0]->barcode : ''), '375', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Price Levels', '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Name:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->itemname) ? $result[0]->itemname : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Retail:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->priceretail) ? number_format($result[0]->priceretail, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discretail) ? $result[0]->discretail : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->brand) ? $result[0]->brand : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Wholesale:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricewhole) ? number_format($result[0]->pricewhole, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discwhole) ? $result[0]->discwhole : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->model) ? $result[0]->model : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp1) ? number_format($result[0]->pricegrp1, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 3:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp1) ? $result[0]->discgrp1 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('773');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part#:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->part) ? $result[0]->part : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp2) ? number_format($result[0]->pricegrp2, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 4:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp2) ? $result[0]->discgrp2 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Size:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->sizeid) ? $result[0]->sizeid : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isinactive) ? $result[0]->isinactive : '') == 1) {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isimport) ? $result[0]->isimport : '') == 1) {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ordered', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sold', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qa, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }
} //end class
