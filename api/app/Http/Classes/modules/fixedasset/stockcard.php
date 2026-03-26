<?php

namespace App\Http\Classes\modules\fixedasset;

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
  public $modulename = 'FIXED ASSET';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'item';
  public $prefix = 'FA';
  public $tablelogs = 'item_log';
  public $tablelogs_del = 'del_item_log';
  private $stockselect;

  private $fields = [
    'barcode', 'picture', 'itemname', 'uom', 'cost', 'itemrem',
    'part', 'model', 'class', 'brand', 'groupid', 'critical', 'reorder',
    'category', 'subcat', 'body', 'sizeid', 'color', 'asset', 'liability', 'revenue', 'expense',
    'isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool',
    'amt', 'amt2', 'famt', 'amt4', 'amt5', 'amt6', 'amt7', 'amt8', 'amt9',
    'disc', 'disc2', 'disc3', 'disc4', 'disc5', 'disc6', 'disc7', 'disc8', 'disc9', 'foramt',
    'supplier', 'partno', 'packaging', 'loa', 'dateid', 'warranty', 'subcode', 'depre', 'saleprice', 'isnsi', 'othcode'
  ];

  private $iteminfo = [
    'subgroup', 'company', 'serialno', 'icondition', 'disposaldate', 'disposaldays', 'insurance', 'startinsured', 'endinsured', 'dateacquired', 'dateacquireddays',
    'purchaserid', 'invoiceno', 'invoicedate', 'pono', 'podate', 'leasedate', 'warrantydays', 'leasedays', 'depreyrs',
    'plateno', 'vinno', 'manufacturer', 'fyear', 'fueltype', 'engine'
  ];

  private $except = ['itemid', 'disposaldays', 'dateacquireddays', 'warrantydays', 'leasedays', 'othcode'];
  private $blnfields = ['isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool', 'isnsi'];
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
      'view' => 2224,
      'edit' => 2225,
      'new' => 2226,
      'save' => 2227,
      'change' => 2228,
      'delete' => 2229,
      'print' => 2230
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $barcode = 1;
    $itemname = 2;
    $serialno = 3;
    $empname = 4;
    $deptname = 5;

    $getcols = ['action', 'barcode', 'itemname', 'serialno', 'empname', 'deptname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$itemname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[$serialno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$empname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$deptname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $cols[$itemname]['label'] = 'Itemname';
    $cols[$empname]['label'] = 'Employee';

    if ($config['params']['companyid'] == 16) { //ati
      $action = 0;
      $barcode = 1;
      $itemname = 2;
      $cat_name = 3;
      $serialno = 4;
      $empname = 5;
      $deptname = 6;
      $getcols = ['action', 'barcode', 'itemname', 'cat_name',  'serialno', 'empname', 'deptname'];
      $stockbuttons = ['view'];
      $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
      $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
      $cols[$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
      $cols[$itemname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
      $cols[$cat_name]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
      $cols[$cat_name]['label'] = 'Category';
      $cols[$serialno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
      $cols[$empname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
      $cols[$deptname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
      $cols[$itemname]['label'] = 'Itemname';
      $cols[$empname]['label'] = 'Employee';
    }


    return $cols;
  }

  public function loaddoclisting($config)
  {
    $limit = "limit 1000";

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'info.serialno'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }

      if ($search != "") {
        $limit = "";
      }
    }


    $add = ", cat.name as cat_name";
    $join = " left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat";


    $qry = "select item.itemid, item.barcode, item.itemname, item.uom, info.serialno,
    emp.clientname as empname, dept.clientname as deptname  $add
    from item left join iteminfo as info on info.itemid=item.itemid 
    left join client as emp on  emp.clientid=info.empid 
    left join client as dept on  dept.clientid=info.locid    $join
    where isfa=1 " .  $filtersearch . " order by item.barcode " . $limit . " ";
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
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtab2($access, $config)
  {
    $loc_history = ['customform' => ['action' => 'customform', 'lookupclass' => 'locationhisotry_fixasset_tab']];
    $ledger = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardtransactionledger']];
    $rr = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardrr']];
    $uom = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystockcarduom']];
    $wh = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardwh']];

    $return['LOCATION HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $loc_history];
    $return['TRANSACTION HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $ledger];
    $return['IN-TRANSACTION'] = ['icon' => 'fa fa-inbox', 'customform' => $rr];
    $return['UOM'] = ['icon' => 'fa fa-weight', 'tableentry' => $uom];
    $return['BALANCE PER WAREHOUSE'] = ['icon' => 'fa fa-inbox', 'customform' => $wh];

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
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = [
      'barcode', 'subcode', 'itemname', 'uom', 'othcode', 'stockgrp', 'subgroup',
      'company', 'lbllocation', 'loc', 'empname', 'building', ['floor', 'room'], 'region'
    ]; // 'amt', 'loa', 'dateid', 'warranty'

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'barcode.label', 'Tag Code');
    data_set($col1, 'barcode.required', true);
    data_set($col1, 'barcode.lookupclass', 'lookupbarcode');
    data_set($col1, 'subcode.label', 'General Item Code');
    data_set($col1, 'subcode.type', 'lookup');
    data_set($col1, 'subcode.action', 'lookupgeneralitem');
    data_set($col1, 'subcode.lookupclass', 'lookupgeneralitem');
    data_set($col1, 'subcode.class', 'sbccsreadonly');
    data_set($col1, 'itemname.type', 'cinput');
    data_set($col1, 'stockgrp.type', 'cinput');
    data_set($col1, 'subgroup.type', 'cinput');
    data_set($col1, 'uom.type', 'cinput');
    // data_set($col1, 'critical.type', 'cinput');
    // data_set($col1, 'reorder.type', 'cinput');
    // data_set($col1, 'amt.label', 'Amount');
    // data_set($col1, 'loa.required', true);

    data_set($col1, 'loc.type', 'input');
    data_set($col1, 'region.type', 'input');
    data_set($col1, 'stockgrp.type', 'input');
    data_set($col1, 'subgroup.type', 'input');
    data_set($col1, 'company.type', 'input');

    // data_set($col1, 'dateid.label', 'Purchase Date');
    data_set($col1, 'othcode.label', 'Barcode Name');

    data_set($col1, 'othcode.class', 'sbccsreadonly');
    data_set($col1, 'uom.class', 'sbccsreadonly');
    // data_set($col1, 'dateid.class', 'sbccsreadonly');
    data_set($col1, 'stockgrp.class', 'sbccsreadonly');
    data_set($col1, 'subgroup.class', 'sbccsreadonly');
    data_set($col1, 'company.class', 'sbccsreadonly');
    data_set($col1, 'building.class', 'sbccsreadonly');
    data_set($col1, 'floor.class', 'sbccsreadonly');
    data_set($col1, 'room.class', 'sbccsreadonly');
    data_set($col1, 'region.class', 'sbccsreadonly');
    data_set($col1, 'loc.class', 'sbccsreadonly');

    // data_set($col1, 'lblrem.label', 'Condition');

    data_set($col1, 'building.required', false);
    data_set($col1, 'floor.required', false);
    data_set($col1, 'room.required', false);

    if ($config['params']['companyid'] == 16) { //ati
      $fields = [
        'categoryname',
        'modelname',
        'brandname',
        'sizeid',
        'classname',
        'partno',
        'serialno',
        ['disposaldate', 'disposaldays'],
        'insurance',
        ['startinsured', 'endinsured'],
        'lblvehicleinfo',
        ['plateno', 'vinno'],
        ['manufacturer', 'fyear'],
        ['fueltype', 'engine']
      ];
    } else {
      $fields = [
        'modelname',
        'brandname',
        'sizeid',
        'classname',
        'partno',
        'serialno',
        ['disposaldate', 'disposaldays'],
        'insurance',
        ['startinsured', 'endinsured'],
        'lblvehicleinfo',
        ['plateno', 'vinno'],
        ['manufacturer', 'fyear'],
        ['fueltype', 'engine']
      ];
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

    $fields = [
      'lblacquisition',
      ['dateacquired', 'dateacquireddays'],
      'dclientname',
      'dpurchaser',
      ['invoiceno', 'invoicedate'],
      ['pono', 'podate'],
      ['amt',],
      ['warranty', 'warrantydays'],
      ['leasedate', 'leasedays'],
      'lbldepreciation',
      ['depre', 'saleprice'],
      ['depreyrs',]
    ];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dclientname.lookupclass', 'stockcardsupplier');
    data_set($col3, 'invoiceno.label', 'Invoice No.');
    data_set($col3, 'invoicedate.label', 'Invoice Date');
    data_set($col3, 'amt.label', 'Price');
    data_set($col3, 'saleprice.label', 'Salvage');

    $fields = ['picture', 'rem', 'lblrem', 'icondition', 'isinactive', 'isnsi'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.name', 'itemrem');
    data_set($col4, 'rem.label', 'Item Remark');
    data_set($col4, 'picture.folder', 'product');
    data_set($col4, 'picture.table', 'item');
    data_set($col4, 'picture.fieldid', 'itemid');
    data_set($col4, 'lblrem.label', 'Condition');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newstockcard($config)
  {
    $data[0]['itemid'] = 0;
    $data[0]['barcode'] = $config['newbarcode'];
    $data[0]['subcode'] = '';
    $data[0]['itemname'] = '';
    $data[0]['uom'] = 'PCS';
    $data[0]['dateid'] = null;

    $data[0]['subgroup'] = '';
    $data[0]['company'] = '';
    $data[0]['serialno'] = '';
    $data[0]['insurance'] = '';
    $data[0]['icondition'] = '0';
    $data[0]['disposaldate'] = null;
    $data[0]['disposaldays'] = 0;
    $data[0]['startinsured'] = null;
    $data[0]['endinsured'] = null;
    $data[0]['dateacquired'] = null;
    $data[0]['dateacquireddays'] = 0;
    $data[0]['depreyrs'] = 0;

    $data[0]['invoiceno'] = '';
    $data[0]['invoicedate'] = null;
    $data[0]['pono'] = '';
    $data[0]['podate'] = null;
    $data[0]['leasedate'] = null;

    $data[0]['plateno'] = '';
    $data[0]['vinno'] = '';
    $data[0]['manufacturer'] = '';
    $data[0]['fyear'] = '';
    $data[0]['fueltype'] = '';
    $data[0]['engine'] = '';

    $data[0]['loc'] = '';
    $data[0]['empname'] = '';
    $data[0]['room'] = '';
    $data[0]['floor'] = '';
    $data[0]['building'] = '';

    $data[0]['itemrem'] = '';
    $data[0]['partname'] = '';
    $data[0]['part'] = '0';
    $data[0]['modelname'] = '';
    $data[0]['model'] = '0';
    $data[0]['categoryname'] = '';
    $data[0]['category'] = '0';
    $data[0]['classic'] = '';
    $data[0]['classname'] = '';
    $data[0]['class'] = '0';
    $data[0]['brand'] = '0';
    $data[0]['brandname'] = '';
    $data[0]['groupid'] = '0';
    $data[0]['stockgrp'] = '';
    $data[0]['critical'] = '';
    $data[0]['reorder'] = '';
    $data[0]['category'] = '';
    $data[0]['body'] = '';
    $data[0]['sizeid'] = '';
    $data[0]['asset'] = '';
    $data[0]['dasset'] = '';
    $data[0]['assetname'] = '';
    $data[0]['liability'] = '';
    $data[0]['dliability'] = '';
    $data[0]['liabilityname'] = '';
    $data[0]['revenue'] = '';
    $data[0]['drevenue'] = '';
    $data[0]['revenuename'] = '';
    $data[0]['expense'] = '';
    $data[0]['expensename'] = '';
    $data[0]['dexpense'] = '';
    $data[0]['isinactive'] = '0';
    $data[0]['isvat'] = '0';
    $data[0]['isimport'] = '0';
    $data[0]['isnsi'] = '0';
    $data[0]['fg_isfinishedgood'] = '0';
    $data[0]['fg_isequipmenttool'] = '0';
    $data[0]['depre'] = '0.00';
    $data[0]['saleprice'] = '0.00';
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
    $data[0]['purchaserid'] = 0;
    $data[0]['purchasercode'] = '';
    $data[0]['purchasername'] = '';
    $data[0]['partno'] = '';
    $data[0]['packaging'] = '';
    $data[0]['cost'] = 0;
    $data[0]['loa'] = 0;
    $data[0]['warranty'] = null;
    $data[0]['subcat'] = '';
    $data[0]['color'] = '';
    $data[0]['othcode'] = '';

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
    $curdate = $this->othersClass->getCurrentDate();
    foreach ($this->iteminfo as $key => $value) {
      switch ($value) {
        case 'disposaldays':
          $fields = $fields . ",TIMESTAMPDIFF(DAY, '" . $curdate . "', iteminfo.disposaldate) as " . $value;
          break;
        case 'warrantydays':
          $fields = $fields . ",TIMESTAMPDIFF(DAY, '" . $curdate . "', item.warranty) as " . $value;
          break;
        case 'leasedays':
          $fields = $fields . ",TIMESTAMPDIFF(DAY, '" . $curdate . "', iteminfo.leasedate) as " . $value;
          break;
        case 'dateacquireddays':
          $fields = $fields . ",TIMESTAMPDIFF(YEAR, iteminfo.dateacquired, '" . $curdate . "') as " . $value;
          break;
        case 'purchaserid':
        case 'depreyrs':
          $fields = $fields . ",ifnull(iteminfo.$value, 0) as $value";
          break;
        default:
          $fields = $fields . ',iteminfo.' . $value;
          break;
      }
    }


    $add = ", cat.name as categoryname, ifnull(genitem.othcode,'') as othcode ";
    $join = "left join itemcategory as cat on cat.line = item.category left join item as genitem on genitem.barcode=item.subcode";

    $qryselect = "select " . $fields . ", ifnull(pmaster.part_name,'') as partname, item.part as partid,
        ifnull(mmaster.model_name,'') as modelname, item.model as model,
        ifnull(itemclass.cl_name,'') as classname,item.class as class,
        ifnull(brand.brand_desc,'') as brandname, ifnull(item.brand,'') as brand,
        ifnull(stockgrp.stockgrp_name,'') as stockgrp, item.groupid as groupid, item.groupid as grid,
        item.category as category,
        ifnull(coa1.acnoname,'')  as assetname,
        ifnull(coa2.acnoname,'')  as liabilityname,
        ifnull(coa3.acnoname,'')  as revenuename,
        ifnull(coa4.acnoname,'')  as expensename,
        ifnull(cl.client, '') as client, ifnull(cl.clientname, '') as clientname, 
        ifnull(cl.clientid, 0) as supplier, item.partno, item.packaging, 
        iteminfo.purchaserid, ifnull(pr.client, '') as purchasercode, ifnull(pr.clientname, '') as purchasername,
        ifnull(loc.clientname, '') as loc, ifnull(loc.building, '') as building, ifnull(loc.floor, '') as floor, ifnull(loc.region, '') as region,
        ifnull(clientinfo.room, '') as room, ifnull(emp.clientname, '') as empname $add ";
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
        left join iteminfo on iteminfo.itemid=item.itemid
        left join client as pr on pr.clientid = iteminfo.purchaserid
        left join client as loc on loc.clientid = iteminfo.locid
        left join client as emp on emp.clientid = iteminfo.empid
        left join clientinfo on clientinfo.clientid = iteminfo.locid $join
        where item.isfa =1 and item.itemid = ? ";

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

    $data = [];
    $iteminfo = [];
    if ($isupdate) {
      unset($this->fields[0]);
      unset($this->fields[1]);
    }
    $itemid = 0;
    foreach ($this->fields as $key) {
      $data[$key] = $head[$key];
      if (!in_array($key, $this->except)) {
        $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
      } //end if    
    }

    foreach ($this->iteminfo as $key) {
      if (!in_array($key, $this->except)) {
        $iteminfo[$key] = $head[$key];
        $iteminfo[$key] = $this->othersClass->sanitizekeyfield($key, $iteminfo[$key]);
      } //end if    
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['isfa'] = 1;

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('item', $data, ['itemid' => $head['itemid']]);
      $itemid = $head['itemid'];
      array_push($this->fields, 'barcode');
      array_push($this->fields, 'picture');
      $exist = $this->coreFunctions->getfieldvalue("iteminfo", "itemid", "itemid=?", [$itemid]);
      if ($exist == '') $exist = 0;
      if ($exist == 0) {
        $iteminfo['itemid'] = $itemid;
        $this->coreFunctions->sbcinsert("iteminfo", $iteminfo);
      } else {
        $this->coreFunctions->sbcupdate('iteminfo', $iteminfo, ['itemid' => $head['itemid']]);
      }
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $itemid = $this->coreFunctions->insertGetId('item', $data);
      $this->coreFunctions->execqry('insert into uom(itemid,uom,factor) values(?,?,1)', 'INSERT', [$itemid, $data['uom']]);
      $iteminfo['itemid'] = $itemid;
      $this->coreFunctions->sbcinsert("iteminfo", $iteminfo);
      $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $head['barcode'] . ' - ' . $head['itemname']);
    }
    return $itemid;
  } // end function

  public function getlastbarcode($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select barcode as value from item  order by barcode desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select barcode as value from item where  left(barcode,?)=? order by barcode desc limit 1', [$length, $pref]);
    }
    return $return;
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
            (select concat(h.docno,' - ',c.center) as value from issueitemstock as s left join issueitem as h on h.trno=s.trno left join transnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from gpstock as s left join gphead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select concat(h.docno,' - ',c.center) as value from hgpstock as s left join hgphead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno where s.itemid=? limit 1)
            union all
            (select 'subitems' as value from subitems as s where s.itemid=? limit 1)";

    $count = $this->coreFunctions->datareader($qry, [$itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid, $itemid]);
    if (($count != '')) {
      return ['itemid' => $itemid, 'status' => false, 'msg' => 'Already have transaction...' . $count];
    }

    $qry = "select itemid as value from item where itemid<? and isinactive=0 order by itemid desc limit 1 ";
    $itemid2 = $this->coreFunctions->datareader($qry, [$itemid]);
    $this->coreFunctions->execqry('delete from item where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from uom where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from component where itemid=?', 'delete', [$itemid]);
    $this->coreFunctions->execqry('delete from itemlevel where itemid=?', 'delete', [$itemid]);

    //auto delete in generated asset tag in RR
    $rrfams = $this->coreFunctions->opentable("select trno from rrfams where itemid=?", [$itemid]);
    foreach ($rrfams as $key => $value) {
      $this->coreFunctions->execqry('delete from rrfams where itemid=? and trno=?', 'delete', [$itemid, $value->trno]);
      $this->logger->sbcwritelog($value->trno, $config, 'DELETE', 'Asset tag deleted ' . $barcode, 'table_log'); //add logs to rr
    }

    $this->logger->sbcdel_log($itemid, $config, $barcode);
    return ['itemid' => $itemid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter()
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
