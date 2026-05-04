<?php

namespace App\Http\Classes\modules\rc952c55ab9eb85660b7cab413fa7c803;

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
use PDO;

class stockcard
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Raw Material Stockcard';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'item';
  public $prefix = 'IT';
  public $tablelogs = 'item_log';
  public $tablelogs_del = 'del_item_log';
  private $stockselect;

  private $fields = [
    'barcode',
    'picture',
    'itemname',
    'uom',
    'cost',
    'itemrem',
    'part',
    'model',
    'class',
    'brand',
    'groupid',
    'critical',
    'reorder',
    'category',
    'subcat',
    'body',
    'sizeid',
    'color',
    'asset',
    'liability',
    'revenue',
    'expense',
    'salesreturn',
    'isinactive',
    'isvat',
    'isimport',
    'isnoninv',
    'markup',
    'foramt',
    'supplier',
    'partno',
    'packaging',
    'israwmat',
    'barcodeid'
  ];


  private $except = ['itemid', 'itemrem'];
  private $blnfields = ['isinactive', 'isvat', 'isimport', 'isnoninv',  'israwmat'];
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
      'view' => 5650,
      'edit' => 5651,
      'new' => 5652,
      'save' => 5653,
      // 'change' => 16,
      'delete' => 5654,
      'print' => 5655
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $barcode = 1;
    $itemname = 2;
    $supplier = 3;
    $uom = 4;
    $cat_name = 5;
    $subcat_name = 6;
    $activestat = 7;
    $amt = 8;

    $getcols = ['action', 'barcode', 'itemname', 'supplier', 'uom', 'cat_name', 'subcat_name', 'activestat', 'amt'];
    $stockbuttons = ['view'];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$itemname]['label'] = 'Itemname';
    $cols[$supplier]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
    $cols[$cat_name]['label'] = 'Category';
    $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
    $cols[$amt]['label'] = 'SRP';
    $cols[$amt]['align'] = 'text-left';

    return $cols;
  }

  public function paramsdatalisting($config)
  {
    return [];
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $companyid = $config['params']['companyid'];
    $addedfields = "";
    $filtersearch = "";
    $condition  = "";
    $searchfield = [];
    $limit = 'limit ' . $this->companysetup->getmasterlimit($config['params']);
    $joins = "";
    $addparams = '';


    $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'item.partno'];
    $condition .= "where 1=1 and item.isfa=0 and item.israwmat=1 and   item.barcode not in ('#','$','*','**','***','$$','$$$','##')";


    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if (isset($test['selectprefix'])) {
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
              $addparams = " and (model.model_name like '%" . $test['docno'] . "%')";
            }
            break;
          case 'Brand':
            $addparams = " and (brand.brand_desc = '" . $test['docno'] . "')";
            break;
          case 'Item Group':
            $addparams = " and (p.name = '" . $test['docno'] . "')";
            break;
        }
      }
    }

    $filtersearch = "";
    $search = '';
    if (isset($config['params']['search'])) {
      $search = $config['params']['search'];
      $search = str_replace('"', "”", $search);
      if ($search != "") {
        $limit = '';
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $sortby = " order by barcode ";

    // add others link masterfile
    $qry = "select item.itemid, ifnull(model.model_name,'') as model_name, item.itemname, item.barcode, item.uom, item.partno,
    FORMAT(item.amt, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,item.model,
    cat.name as cat_name,
    subcat.name as subcat_name, ifnull(supp.clientname, '') as supplier,if(item.isinactive=1,'Inactive','Active') as activestat,item.color,item.sizeid
    " . $addedfields . "
    from item
    left join item_class as cls on cls.cl_id=item.class
    left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
    left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
    left join model_masterfile as model on model.model_id = item.model
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    left join client as supp on supp.clientid = item.supplier
    " . $joins . "
    " . $condition . " " . $filtersearch . $addparams . "
    " . $sortby . " " . $limit;

    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

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

    if ($this->companysetup->getbarcodelength($config['params']) != 0) {
      array_push($btns, 'others');
    }
    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {

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
    $return['ITEM PRICE'] = ['icon' => 'fa fa-tags', 'customform' => $price];
    $return['HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $history];
    $return['IN-TRANSACTION'] = ['icon' => 'fa fa-inbox', 'customform' => $intransaction];
    $return['UNIT OF MEASUREMENT'] = ['icon' => 'fa fa-weight', 'tab' => $uom];
    $return['BALANCE PER WAREHOUSE'] = ['icon' => 'fa fa-warehouse', 'customform' => $warehouse];
    $return['PURCHASE ORDER/ JOB ORDER HISTORY'] = ['icon' => 'fa fa-shopping-basket', 'customform' => $po];
    $return['SALES ORDER HISTORY'] = ['icon' => 'fa fa-cart-arrow-down', 'customform' => $so];
    $return['COMPONENT'] = ['icon' => 'fa fa-drafting-compass', 'tab' => $component];
    $return['STOCK LEVEL'] = ['icon' => 'fa fa-level-up-alt', 'tab' => $stocklevel];
    $return['COMPATIBLE'] = ['icon' => 'fa fa-ethernet', 'tab' => $compatible];
    $return['EQUIVALENT SKU'] = ['icon' => 'fa fa-equals', 'tab' => $sku];

    return $return;
  }

  public function tabprice($config) {}

  public function createTab($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $fields = ['barcode', 'itemname', 'uom', ['critical', 'reorder'], 'dclientname', 'partno'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'barcode.lookupclass', 'lookupbarcode');
    data_set($col1, 'barcode.required', true);
    data_set($col1, 'itemname.type', 'cinput');
    data_set($col1, 'itemname.required', true);
    data_set($col1, 'uom.type', 'cinput');
    data_set($col1, 'critical.type', 'cinput');
    data_set($col1, 'reorder.type', 'cinput');
    data_set($col1, 'dclientname.lookupclass', 'stockcardsupplier');

    data_set($col1, 'cost.label', 'Fixed Cost');

    $fields = ['partname', 'modelname', 'classname', 'brandname', 'stockgrp', 'categoryname', 'subcatname'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

    $fields = ['body', 'sizeid', 'color', 'dasset', 'dliability', 'drevenue', 'dexpense', 'dsalesreturn', 'foramt'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'foramt.type', 'cinput');
    $fields = ['picture', 'rem', ['isinactive', 'isvat'], ['isimport', 'isnoninv'], 'israwmat'];

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.name', 'itemrem');
    data_set($col4, 'rem.label', 'Item Remark');
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
    // if ($companyid == 10 || $companyid == 12) { //afti & afti usd
    //   $data[0]['uom'] = 'EA';
    // } else {
    $data[0]['uom'] = 'PCS';
    // }

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
    $data[0]['salesreturn'] = '';
    $data[0]['salesreturnname'] = '';
    $data[0]['isinactive'] = '0';
    $data[0]['isvat'] = '0';
    $data[0]['isimport'] = '0';

    // if ($companyid == 59) { //roosevelt
    $data[0]['isnoninv'] = '1';
    // // } else {
    // $data[0]['isnoninv'] = '0';
    // // }

    $data[0]['amt'] = '0';
    $data[0]['amt2'] = '0';
    $data[0]['famt'] = '0';
    $data[0]['amt4'] = '0';
    $data[0]['amt5'] = '0';
    $data[0]['amt6'] = '0';
    $data[0]['amt7'] = '0';
    $data[0]['amt8'] = '0';
    $data[0]['amt9'] = '0.';
    $data[0]['markup'] = '0';
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
    $data[0]['partno'] = '';
    $data[0]['packaging'] = '';
    $data[0]['cost'] = 0;
    $data[0]['subcat'] = '0';
    $data[0]['subcatname'] = '';
    $data[0]['color'] = '';

    // $data[0]['projectcode'] = '';
    // $data[0]['projectname'] = '';
    $data[0]['dasset'] = '';
    $data[0]['dliability'] = '';
    $data[0]['dexpense'] = '';
    $data[0]['drevenue'] = '';

    $data[0]['barcodeid'] = 0;
    $data[0]['israwmat'] = 1;

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';


    if ($itemid == 0) {
      $itemid = $this->othersClass->readprofile($doc, $config);
      if ($itemid == 0) {
        $itemid = $this->coreFunctions->datareader("select itemid as value from item where isinactive=0 " . $filter . " order by itemid desc limit 1");
      }
      $config['params']['itemid'] = $itemid;
    } else {
      $this->othersClass->checkprofile($doc, $itemid, $config);
    }
    $center = $config['params']['center'];
    $head = [];

    $fields = 'item.itemid, item.barcode as docno';

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
        ifnull(coa5.acnoname,'')  as salesreturnname,
        ifnull(cl.client, '') as client, ifnull(cl.clientname, '') as clientname,
        ifnull(cl.clientid, 0) as supplier, item.partno, item.packaging,
        '' as dasset,
        '' as dliability,
        '' as dexpense,
        '' as drevenue,
        '' as dsalesreturn";

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
        left join coa as coa5 on coa5.acno = item.salesreturn
        left join client as cl on cl.clientid = item.supplier
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        where item.itemid = ? ";

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
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $data = [];
    if ($isupdate) {
      unset($this->fields[0]);
      unset($this->fields[1]);
    }

    $itemid = 0;
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc'], $companyid);
        } //end if
      }
    }



    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['ismirror'] = 0;
    if ($isupdate) {
      $current_uom = $this->coreFunctions->getfieldvalue("item", "uom", "itemid=?", [$head['itemid']]);


      if ($current_uom != '' && $current_uom != $head['uom']) {
        $this->coreFunctions->LogConsole('Current:' . $current_uom . ' - New:' . $data['uom']);
        if ($this->othersClass->checkuomtransaction($head['itemid'], $current_uom)) {

          $this->coreFunctions->LogConsole('Current:' . $current_uom . ' existing transaction');

          if ($current_uom != $data['uom']) {
            $this->coreFunctions->LogConsole('Current <> new');
            $existing_uom =  $this->coreFunctions->opentable("select uom, factor from uom where itemid=? and uom=?", [$head['itemid'], $data['uom']]);
            $this->coreFunctions->LogConsole('new: ' . json_encode($existing_uom));
            if (empty($existing_uom)) {
              unset($data['uom']);
            } else {
              if ($existing_uom[0]->factor != 1) {
                unset($data['uom']);
              }
            }
          } else {
            unset($data['uom']);
          }
        } else {

          $this->coreFunctions->LogConsole('Current:' . $current_uom . ' no transaction');

          $isexistinguom = $this->coreFunctions->getfieldvalue("uom", "uom", "itemid=? and uom=?", [$head['itemid'], $head['uom']]);
          if (empty($isexistinguom)) {
            $this->coreFunctions->LogConsole('delete ' . $current_uom);
            $this->coreFunctions->execqry('delete from uom where itemid=? and uom=?', 'DELETE', [$head['itemid'], $current_uom]);
            $this->coreFunctions->execqry('insert into uom (itemid,uom,factor,isdefault2) values(?,?,1,1)', 'INSERT', [$head['itemid'], $data['uom']]);
          } else {
            if ($companyid != 50) { //not unitech
              unset($data['uom']);
            }
          }
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

      $exist  = $this->coreFunctions->datareader("select barcodeid as value from item where barcodeid = '" . $data['barcodeid'] . "' limit 1");
      if ($exist != "") {
        $this->logger->sbcwritelog($head['itemid'], $config, 'Update',  $data['barcodeid'] . " Item ID already exist.");
        unset($data['barcodeid']);
      }

      // uom_update:

      $this->coreFunctions->sbcupdate('item', $data, ['itemid' => $head['itemid']]);
      $itemid = $head['itemid'];
      array_push($this->fields, 'barcode');
      array_push($this->fields, 'picture');
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];


      $itemid = $this->coreFunctions->insertGetId('item', $data);
      $this->coreFunctions->execqry('insert into uom(itemid,uom,factor,isdefault) values(?,?,1,?)', 'INSERT', [$itemid, $data['uom']]);


      $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $head['barcode'] . ' - ' . $head['itemname']);
    }
    return $itemid;
  } // end function

  public function getlastbarcode($pref, $companyid = 0, $sort = 'barcode')
  {
    $length = strlen($pref);
    $return = '';
    $filter = '';

    checklastcodehere:
    if ($length == 0) {
      $return = $this->coreFunctions->datareader("select barcode as value from item where ''='' " . $filter . " order by " . $sort . " desc limit 1");
    } else {
      $return = $this->coreFunctions->datareader("select barcode as value from item where left(barcode,?)=? " . $filter . " order by " . $sort . " desc limit 1", [$length, $pref]);
    }

    $this->coreFunctions->LogConsole($return);

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
        return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => 'xxx', 'csv' => 'abc' . "\t" . 'def' . "\t" . 'ghi' . "\t"];
        break;
      case 'readfile':
        $csv = $config['params']['csv'];
        $arrcsv = explode("\r\n", $csv);
        return ['status' => true, 'msg' => 'Readfile Successfully', 'data' => $arrcsv];
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'changecode':
        // return $this->othersClass->changebarcode($config);
        break;
      case 'duplicatedoc':
        return $this->duplicateitem($config);
        break;
    }
  }

  public function deletetrans($config)
  {
    $itemid = $config['params']['itemid'];
    $doc = $config['params']['doc'];
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
    $qry = "(select concat(h.docno,' - ',c.center) as value from lastock as s left join lahead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from glstock as s left join glhead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from sostock as s left join sohead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from hsostock as s left join hsohead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from postock as s left join pohead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from hpostock as s left join hpohead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from prstock as s left join prhead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from hprstock as s left join hprhead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from cdstock as s left join prhead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from hcdstock as s left join hprhead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)    
            union all
            (select concat(h.docno,' - ',c.center) as value from trstock as s left join prhead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from htrstock as s left join hprhead as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)                       
            union all
            (select 'subitems' as value from subitems as s where s.itemid=? limit 1);";
    $count = $this->coreFunctions->datareader($qry, [$itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid]);
    if (($count != '')) {
      return ['itemid' => $itemid, 'status' => false, 'msg' => 'Already have transaction...' . $count];
    }
    $companyid = $config['params']['companyid'];
    $qry = "select itemid as value from item where itemid<? and isinactive=0 order by itemid desc limit 1 ";


    $itemid2 = $this->coreFunctions->datareader($qry, [$itemid]);
    $this->coreFunctions->execqry('delete from item where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from uom where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from component where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from itemlevel where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from pricebracket where itemid=?', 'delete', [$itemid]);
    $this->logger->sbcdel_log($itemid, $config, $barcode);
    return ['itemid' => $itemid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function openqry($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $center = $config['params']['center'];
    $head = [];
    $fields = 'item.itemid, item.barcode as docno';
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
        ifnull(coa5.acnoname,'')  as salesreturnname,
        ifnull(cl.client, '') as client, ifnull(cl.clientname, '') as clientname,
        ifnull(cl.clientid, 0) as supplier, item.partno, item.packaging,
        ifnull(prj.code, '') as projectcode,
        ifnull(prj.name, '') as projectname,
        '' as dasset,
        '' as dliability,
        '' as dexpense,
        '' as drevenue,
        '' as dsalesreturn";

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
        left join coa as coa5 on coa5.acno = item.salesreturn
        left join client as cl on cl.clientid = item.supplier
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        limit 1";
    return $qry;
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function duplicateitem($config)
  {
    $row = $config['params']['row'];

    $barcodelength = $this->companysetup->getbarcodelength($config['params']);

    if ($barcodelength <> 0) {
      $pref = $this->othersClass->GetPrefix($row['barcode']);
      $barcode2 = $this->getlastbarcode($pref);
      $seq = intval(substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
      $seq += 1;

      $newbarcode = $this->othersClass->PadJ($pref . $seq, $barcodelength);
    } else {
      $newbarcode = $row['barcode'];
    }

    $db = env('DB_DATABASE');
    $qry = "SELECT COLUMN_NAME as cols
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = '" . $db . "'
    AND TABLE_NAME = 'item'
    ORDER BY ORDINAL_POSITION";

    $cols = $this->coreFunctions->opentable($qry);

    $insertqry = "insert into item(";
    $vals = " select ";

    foreach ($cols as $k => $v) {
      if ($cols[$k]->cols == 'itemid') {
      } else {
        if ($cols[$k]->cols == 'barcode') {
          $insertqry .= $cols[$k]->cols;
          $vals .= "'" . $newbarcode . "'";
        } elseif ($cols[$k]->cols == 'createdate') {
          $insertqry .= ",`" . $cols[$k]->cols . "`";
          $vals .= ",'" . $this->othersClass->getCurrentTimeStamp() . "'";
        } elseif ($cols[$k]->cols == 'createby') {
          $insertqry .= ",`" . $cols[$k]->cols . "`";
          $vals .= ",'" . $config['params']['user'] . "'";
        } else {
          $insertqry .= ",`" . $cols[$k]->cols . "`";
          $vals .= ",`" . $cols[$k]->cols . "`";
        }
      }
    }

    $insertqry .= ") ";
    $vals .= " from item where itemid =" . $row['itemid'];

    $sql = $insertqry . $vals;
    //$this->coreFunctions->execqry($sql)
    $nitemid = 0;
    if ($this->coreFunctions->execqry($sql)) {
      $nitemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode ='" . $newbarcode . "'");
      $this->logger->sbcwritelog($nitemid, $config, 'CREATE', $nitemid . ' - ' . $newbarcode . ' - ' . $row['itemname']);
      $this->coreFunctions->execqry("insert into uom (itemid,uom,factor) select  itemid,uom,1 from item where itemid = ?", 'insert', [$nitemid]);
      $config['params']['itemid'] = $nitemid;
      return ['status' => true, 'msg' => $newbarcode . ' successfully created.', 'action' => 'loadledgerdata', 'trno' => $nitemid, 'qq' => $nitemid, 'itemid' => $nitemid, 'access' => 'view',  'url' => "/ledgergrid/masterfile/stockcard", 'moduletype' => 'ledgergrid'];
    } else {
      return ['status' => false, 'msg' => 'Failed to copy item'];
    }
  }
} //end class
