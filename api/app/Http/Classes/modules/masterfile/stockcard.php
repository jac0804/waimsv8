<?php

namespace App\Http\Classes\modules\masterfile;

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
  public $modulename = 'STOCKCARD';
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
    'shortname',
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
    'fg_isfinishedgood',
    'fg_isequipmenttool',
    'isnoninv',
    'isserial',
    'markup',
    'foramt',
    'supplier',
    'partno',
    'subcode',
    'packaging',
    'islabor',
    'dqty',
    'ispositem',
    'isprintable',
    'projectid',
    'moq',
    'mmoq',
    'linkdept',
    'tqty',
    'isofficesupplies',
    'noncomm',
    'isgeneric',
    'othcode',
    'item_length',
    'item_width',
    'item_height',
    'israwmat',
    'barcodeid',
    'avecost',
    'channel',
    'isnonserial',
    'iswireitem',
    'startwire',
    'endwire',
    'maximum',
    'aveleadtime',
    'maxleadtime',
    'minimum',
    'isreversewireitem',
    'isfg'
  ];

  private $iteminfo = ['volume', 'weight', 'engine', 'serialno', 'renewaldate', 'chassisno', 'endinsured', 'dateacquired', 'warrantyend', 'leasedate', 'disposaldate'];

  private $except = ['itemid', 'itemrem'];
  private $blnfields = ['isinactive', 'isvat', 'isimport', 'fg_isfinishedgood', 'fg_isequipmenttool', 'isnoninv', 'isserial', 'islabor', 'ispositem', 'isprintable', 'isofficesupplies', 'noncomm', 'isgeneric', 'israwmat', 'isnonserial', 'iswireitem', 'isreversewireitem', 'isfg'];
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
      'view' => 12,
      'edit' => 13,
      'new' => 14,
      'save' => 15,
      'change' => 16,
      'delete' => 17,
      'print' => 18
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: // afti usd
        $this->prefix = "I";
        $getcols = ['action', 'model_name', 'itemname', 'partno', 'brand', 'itemdescription', 'stockgrp_name', 'barcode', 'uom', 'amt'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[2]['label'] = 'Itemname';
        $cols[9]['label'] = 'SRP';
        $cols[8]['align'] = 'text-left';
        $cols[3]['align'] = 'text-left';
        $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;text-align:left;';
        $cols[7]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';
        $cols[6]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';
        $cols[2]['style'] = 'width:190px;whiteSpace: normal;min-width:190px;text-align:left;';
        $cols[6]['label'] = 'Item Group';
        return $cols;
        break;

      case 14: //majesty
        $action = 0;
        $barcode = 1;
        $itemname = 2;
        $supplier = 3;
        $uom = 4;
        $amt = 5;
        $principal = 6;
        $generic = 7;
        $classification = 8;
        $brand = 9;
        $division = 10;
        $cat_name = 11;
        $subcat_name = 12;
        $department = 13;
        $form = 14;

        $getcols = [
          'action',
          'barcode',
          'itemname',
          'supplier',
          'uom',
          'amt',
          'partno',
          'model',
          'cl_name',
          'brand_desc',
          'stockgrp_name',
          'cat_name',
          'subcat_name',
          'deptname',
          'body'
        ];

        $stockbuttons = ['view'];

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$supplier]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$cat_name]['label'] = 'Category';
        $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$amt]['label'] = 'SRP';
        $cols[$amt]['align'] = 'text-left';

        $cols[$principal]['label'] = 'Principal';
        $cols[$principal]['name'] = 'principal';
        $cols[$principal]['field'] = 'principal';
        $cols[$principal]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$principal]['align'] = 'text-left';

        $cols[$generic]['label'] = 'Generic';
        $cols[$generic]['name'] = 'generic';
        $cols[$generic]['field'] = 'generic';
        $cols[$generic]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$generic]['align'] = 'text-left';

        $cols[$classification]['label'] = 'Classification';
        $cols[$classification]['name'] = 'classification';
        $cols[$classification]['field'] = 'classification';
        $cols[$classification]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$classification]['align'] = 'text-left';

        $cols[$brand]['label'] = 'Brand';
        $cols[$brand]['name'] = 'brand';
        $cols[$brand]['field'] = 'brand';
        $cols[$brand]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$brand]['align'] = 'text-left';

        $cols[$subcat_name]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left;';

        $cols[$division]['label'] = 'Division';
        $cols[$division]['name'] = 'division';
        $cols[$division]['field'] = 'division';
        $cols[$division]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left;';
        $cols[$division]['align'] = 'text-left';

        $cols[$department]['name'] = 'department';
        $cols[$department]['field'] = 'department';
        $cols[$department]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left;';
        $cols[$department]['align'] = 'text-left';

        $cols[$form]['name'] = 'form';
        $cols[$form]['field'] = 'form';
        $cols[$form]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left;';
        $cols[$form]['align'] = 'text-left';

        return $cols;
        break;
      case 23:
      case 41:
      case 52: //technolab
        $action = 0;
        $barcode = 1;
        $itemname = 2;
        $brand = 3;
        $supplier = 4;
        $uom = 5;
        $cat_name = 6;
        $subcat_name = 7;
        $size = 8;
        $amt = 9;

        $getcols = ['action', 'barcode', 'itemname', 'brand', 'supplier', 'uom', 'cat_name', 'subcat_name', 'sizeid', 'amt'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$size]['label'] = 'Packaging';
        $cols[$size]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$supplier]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$cat_name]['label'] = 'Category';
        $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$amt]['label'] = 'SRP';
        $cols[$amt]['align'] = 'text-left';
        return $cols;
        break;

      case 24:
        $action = 0;
        $barcode = 1;
        $itemname = 2;
        $uom = 3;
        $amt = 4;
        $stockgrp_name = 5;
        $cat_name = 6;
        $subcat_name = 7;

        $getcols = ['action', 'barcode', 'itemname', 'uom', 'amt', 'stockgrp_name', 'cat_name', 'subcat_name'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$stockgrp_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$cat_name]['label'] = 'Category';
        $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$amt]['label'] = 'Price';
        $cols[$amt]['align'] = 'text-left';
        return $cols;
        break;

      case 16:
        $action = 0;
        $barcode = 1;
        $itemname = 2;
        $shortname = 3;
        $othcode = 4;
        $uom = 5;
        $cat_name = 6;
        $subcat_name = 7;
        $amt = 8;

        $getcols = ['action', 'barcode', 'itemname', 'shortname', 'othcode', 'uom', 'cat_name', 'subcat_name', 'amt'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$shortname]['label'] = 'Specifications';
        $cols[$shortname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left;';
        $cols[$cat_name]['label'] = 'Category';
        $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$amt]['label'] = 'SRP';
        $cols[$amt]['align'] = 'text-left';
        return $cols;
        break;
      case 42:
        $action = 0;
        $barcode = 1;
        $itemname = 2;
        $uom = 3;
        $group = 4;

        $getcols = ['action', 'barcode', 'itemname',  'uom', 'stockgrp_name'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$group]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$group]['label'] = 'Group';
        return $cols;
        break;

      case 22: //EIPI
        $action = 0;
        $barcode = 1;
        $sku = 2;
        $itemname = 3;
        $supplier = 4;
        $cat_name = 5;
        $stockgrp_name = 6;
        $subcat_name = 7;
        $getcols = ['action', 'barcode', 'sku', 'itemname', 'supplier', 'cat_name', 'stockgrp_name', 'subcat_name'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$sku]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$supplier]['style'] = 'width:250px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$cat_name]['label'] = 'Category 1';
        $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$stockgrp_name]['label'] = 'Category 2';
        $cols[$stockgrp_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$subcat_name]['label'] = 'Category 3';
        $cols[$subcat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        return $cols;
        break;
      case 47:
        $action = 0;
        $barcode = 1;
        $itemname = 2;
        $color = 3;
        $size = 4;
        $uom = 5;
        $amt = 6;
        $status = 7;

        $getcols = ['action', 'barcode', 'itemname', 'color', 'sizeid', 'uom', 'amt', 'activestat'];
        $stockbuttons = ['view', 'listingshowbalance'];

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        $cols[$uom]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$size]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align: left;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$amt]['label'] = 'SRP';
        $cols[$amt]['align'] = 'text-left';

        return $cols;
        break;

      case 60: //transpower
        $duplicateitem = $this->othersClass->checkAccess($config['params']['user'], 5508);

        $allowview = $this->othersClass->checkAccess($config['params']['user'], 5488);
        $getcols = ['action', 'barcode', 'itemname', 'uom', 'namt5', 'namt7', 'amt2',  'disc2', 'namt2', 'amt4', 'disc4', 'namt4', 'activestat'];
        $stockbuttons = ['view'];
        if ($duplicateitem == 1) {
          $stockbuttons = ['view', 'duplicatedoc'];
        }

        foreach ($getcols as $key => $value) {
          $$value = $key;
        }

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        if ($duplicateitem == 1) {
          $cols[$action]['btns']['duplicatedoc']['label'] = 'Duplicate Item';
        }
        // $cols[$itemname]['label'] = 'Itemname';
        $cols[$itemname]['label'] = 'Item Description';
        $cols[$amt2]['label'] = 'Wholesale Base';
        $cols[$disc2]['label'] = 'Wholesale Disc';

        $cols[$namt5]['style'] = 'width: 90px; whiteSpace: normal; min-width:90px; max-width:90px;';
        //$cols[$namt5]['align'] = 'text-right';

        $cols[$namt7]['style'] = 'width: 90px;whiteSpace: normal;min-width:90;max-width:90px;';
        // $cols[$namt7]['align'] = 'text-right';
        // $cols[$amt2]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px; text-align:right;';
        // $cols[$amt2]['align'] = 'text-right';
        // $cols[$namt4]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px; text-align:right;';
        // $cols[$namt4]['align'] = 'text-right';
        $cols[$disc2]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        // $cols[$uom]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px; text-align:center;';
        // $cols[$uom]['align'] = 'text-center';

        $cols[$amt4]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

        $cols[$amt2]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $cols[$amt2]['align'] = 'text-left';

        $cols[$disc4]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $cols[$amt4]['label'] = 'Base Cost';
        // $cols[$activestat]['style'] = 'width: 100px;whiteSpace: normal;min-width:150px;max-width:100px; text-align:center;';
        // $cols[$activestat]['align'] = 'text-center';

        if (!$allowview) {
          $cols[$amt4]['type'] = 'coldel';
          $cols[$disc4]['type'] = 'coldel';
          $cols[$namt4]['type'] = 'coldel';
        }
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
        break;
      default:
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
        if ($config['params']['companyid'] == 40) { //cdo
          $stockbuttons = ['view', 'viewenginehistory'];
        } else {
          $stockbuttons = ['view'];
        }

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$supplier]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$cat_name]['label'] = 'Category';
        $cols[$cat_name]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$amt]['label'] = 'SRP';
        $cols[$amt]['align'] = 'text-left';

        return $cols;
        break;
    }
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti
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
        break;
      case 16: //ATI
        $fields = ['stockcardfilter'];
        $col1 = $this->fieldClass->create($fields);
        $fields = ['start'];
        $col2 = $this->fieldClass->create($fields);
        $fields = ['end'];
        $col3 = $this->fieldClass->create($fields);
        $data = $this->coreFunctions->opentable("select '' as stockcardfilter, now() as start, now() as end");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3]];
        break;
      default:
        return [];
        break;
    }
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

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $addedfields .= ", brand.brand_desc as brand,item.moq,item.mmoq,left(i.itemdescription,50) as itemdescription,p.name as stockgrp_name ";
        $searchfield = ['item.partno', 'brand.brand_desc', 'item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'model.model_name', 'i.itemdescription', 'p.name'];
        $condition .= "where item.isoutsource = 0";
        $joins = "left join iteminfo as i on i.itemid = item.itemid left join projectmasterfile as p on p.line = item.projectid ";
        break;
      case 14: //majesty
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'part.part_name', 'model.model_name', 'cls.cl_name', 'brand.brand_desc', 'grp.stockgrp_name', 'dept.clientname', 'item.body'];
        $condition .= "where 1=1 and item.isgeneric=0 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        $addedfields .= ",ifnull(part.part_name,'') as principal,ifnull(model.model_name,'') as generic,
        ifnull(cls.cl_name,'') as classification,
        ifnull(brand.brand_desc,'') as brand,
        ifnull(grp.stockgrp_name,'') as division,
        dept.clientname as department,
        ifnull(item.body,'') as form";
        $joins = "left join client as dept on dept.clientid = item.linkdept";
        break;

      case 24: //goodfound
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        $addedfields .= ",ifnull(grp.stockgrp_name,'') as stockgrp_name";
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $addedfields .= ", brand.brand_desc as brand,item.sizeid ";
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'brand.brand_desc', 'item.sizeid'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        break;
      case 42: //pdpi mis
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'grp.stockgrp_name'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        $addedfields .= ",ifnull(grp.stockgrp_name,'') as stockgrp_name";
        break;
      case 22: //EIPI
        $searchfield = ['item.itemname', 'item.barcode', 'item.partno', 'supp.clientname', 'cat.name ', 'grp.stockgrp_name', ' subcat.name'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        $addedfields .= ",ifnull(grp.stockgrp_name,'') as stockgrp_name,ifnull(item.partno,'') as sku";
        break;
      case 47: //kitchenstar
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'item.color', 'item.sizeid'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        break;
      case 40: //cdo
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'item.partno', 'cat.name ', 'grp.stockgrp_name', ' subcat.name'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        break;
      case 60: //transpower
        $addedfields .= ", format(item.namt5,2) as namt5,format(item.namt7,2) as namt7,format(item.amt2,2) as amt2,item.disc2, format(item.amt4,2) as amt4, item.disc4, format(item.namt4,2) as namt4 , format(item.namt2,2) as namt2 ";
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'item.partno'];
        $condition .= "where 1=1 and item.isfa=0 and item.isinactive =0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        break;
      default:
        $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'item.partno'];
        $condition .= "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        break;
    }

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
      if ($companyid == 16) { //ati
        $condition = "where 1=1 and item.isfa=0 and item.barcode not in ('#','$','*','**','***','$$','$$$','##')";
        if ($test['stockcardfilter'] != '') {
          switch ($test['stockcardfilter']) {
            case 'Temporary Barcode':
              $addparams = "and left(item.barcode,3)='ITM' and item.barcode=item.othcode";
              break;
            case 'Barcode':
              $searchfield = ['item.barcode'];
              break;
            case 'Itemname':
              $searchfield = ['item.itemname'];
              break;
            case 'Specifications':
              $searchfield = ['item.shortname'];
              break;
            case 'Barcode Name':
              $searchfield = ['item.othcode'];
              break;
            case 'UOM':
              $searchfield = ['item.uom'];
              break;
            case 'Sub Category':
              $searchfield = ['subcat.name'];
              break;
            case 'SRP':
              $searchfield = ['item.amt'];
              break;
            default:
              $searchfield = ['item.itemname', 'item.barcode', 'item.uom', 'item.amt', 'item.partno', 'cat.name', 'subcat.name', 'item.shortname', 'item.othcode'];
              break;
          }
          if ($test['stockcardfilter'] == 'Date Created') {
            if ($test['start'] != '' && $test['end'] != '') {
              $date1 = date('Y-m-d', strtotime($test['start']));
              $date2 = date('Y-m-d', strtotime($test['end']));
              $condition .= " and date(item.createdate) between '" . $date1 . "' and '" . $date2 . "'";
            }
          }
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
        if ($companyid == 16) { //ati
          $filtersearch = $this->othersClass->multisearch($searchfield, $search, true);
        } else {
          $filtersearch = $this->othersClass->multisearch($searchfield, $search);
        }
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      if ($search . $addparams == '') {
        $limit = 'limit ' . $this->companysetup->getmasterlimit($config['params']);
      }
    }

    $sortby = " order by barcode ";
    if ($companyid == 56 || $companyid == 60) { //homeworks | transpower
      $sortby = " order by itemname ";
    }

    // add others link masterfile
    $qry = "select item.itemid, ifnull(model.model_name,'') as model_name, item.itemname, item.barcode, item.uom, item.partno,
    FORMAT(item.amt, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,item.model,
    cat.name as cat_name,
    subcat.name as subcat_name, ifnull(supp.clientname, '') as supplier,item.shortname,item.othcode,if(item.isinactive=1,'Inactive','Active') as activestat,item.color,item.sizeid
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

    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      if (($key = array_search('delete', $btns)) !== false) {
        unset($btns[$key]);
      }
    }

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $btns = array(
          'load',
          'print',
          'logs',
          'backlisting',
          'toggleup',
          'toggledown'
        );
      }
    }

    if ($this->companysetup->getbarcodelength($config['params']) != 0) {
      array_push($btns, 'others');
    } else {
      switch ($companyid) {
        case 23: //labsol cebu
        case 41: //labsol manila
        case 52: //technolab
          array_push($btns, 'others');
          break;
      }
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
    $view_itemprice = $this->othersClass->checkAccess($config['params']['user'], 4860);
    $view_history = $this->othersClass->checkAccess($config['params']['user'], 4862);
    $view_intransaction = $this->othersClass->checkAccess($config['params']['user'], 4863);
    $view_unitofmeasurement = $this->othersClass->checkAccess($config['params']['user'], 4864);
    $view_balanceperwh = $this->othersClass->checkAccess($config['params']['user'], 4866);
    $view_po_jo_order_history = $this->othersClass->checkAccess($config['params']['user'], 4867);
    $view_sales_order_history = $this->othersClass->checkAccess($config['params']['user'], 4868);
    $view_component = $this->othersClass->checkAccess($config['params']['user'], 4869);
    $view_stocklevel = $this->othersClass->checkAccess($config['params']['user'], 4871);
    $view_compatible = $this->othersClass->checkAccess($config['params']['user'], 4873);
    $view_equivalent_sku = $this->othersClass->checkAccess($config['params']['user'], 4875);
    $supplier_list = $this->othersClass->checkAccess($config['params']['user'], 5016);
    $posprice_list = $this->othersClass->checkAccess($config['params']['user'], 5018);

    $pospricescheme_list = $this->othersClass->checkAccess($config['params']['user'], 5390);
    $pospromoperitem_list = $this->othersClass->checkAccess($config['params']['user'], 5391);

    $companyid = $config['params']['companyid'];
    $price = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewitemprice']];
    $baseprice = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewitembaseprice']];

    $history = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardtransactionledger']];
    $intransaction = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardrr']];
    $warehouse = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardwh']];
    $po = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardpo']];
    $so = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardso']];
    $jo = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardjo']];
    $pr = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardpr']];
    $cd = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewstockcardcd']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryuom', 'label' => 'Uom']];
    $uom = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystocklevel', 'label' => 'StockLevel']];
    $stocklevel = $this->tabClass->createtab($tab, []);

    if ($companyid == 56) { //homeworks - transpower
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryminmax', 'label' => 'MIN/MAX']];
      $minmax = $this->tabClass->createtab($tab, []);
    }

    if ($companyid == 20) { //proline
      $component = ['customform' => ['action' => 'customform', 'lookupclass' => 'stockcardcomponent']];
    } else {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycomponent', 'label' => 'component']];
      $component = $this->tabClass->createtab($tab, []);
    }

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'stockcardcompatible', 'label' => 'stockcardcompatible']];
    $compatible = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysku', 'label' => 'sku']];
    $sku = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysku2', 'label' => 'sku']];
    $sku2 = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrypricelist', 'label' => 'Price List']];
    $pricelist = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrystockprice', 'label' => 'Stock Price']];
    $stockprice = $this->tabClass->createtab($tab, []);

    $iteminfod = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewiteminfo']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'supplierlist', 'label' => 'SUPPLIER LIST']];
    $supplierlist = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'pospricelist', 'label' => 'PRICE LIST']];
    $pospricelist = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'viewpricescheme', 'label' => 'PRICE SCHEME']];
    $pospricescheme = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'viewpromoperitem', 'label' => 'PROMO PER ITEM']];
    $pospromoperitem = $this->tabClass->createtab($tab, []);

    $return = [];
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti & afti usd
      $return['ITEM INFORMATION'] = ['icon' => 'fa fa-address-book', 'customform' => $iteminfod];
    }
    if ($companyid == 21) { // kinggeorge
      if ($view_itemprice) {
        $return['ITEM PRICE'] = ['icon' => 'fa fa-tags', 'customform' => $price];
      }
    } else {
      $return['ITEM PRICE'] = ['icon' => 'fa fa-tags', 'customform' => $price];
    }
    if ($companyid == 39) { //cbbsi
      $return['BASE PRICE'] = ['icon' => 'fa fa-tags', 'customform' => $baseprice];
    }

    if ($companyid == 15) { //nathina
      $return['PRICE LIST'] = ['icon' => 'fa fa-tags', 'tab' => $pricelist];
    }

    if ($companyid == 10) { //afti
      $return['STOCK PRICE'] = ['icon' => 'fa fa-tags', 'tab' => $stockprice];
    }


    switch ($companyid) {
      case 21: //kinggeorge
        if ($view_history) {
          $return['HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $history];
        }
        if ($view_intransaction) {
          $return['IN-TRANSACTION'] = ['icon' => 'fa fa-inbox', 'customform' => $intransaction];
        }
        if ($view_unitofmeasurement) {
          $return['UNIT OF MEASUREMENT'] = ['icon' => 'fa fa-weight', 'tab' => $uom];
        }
        if ($view_balanceperwh) {
          $return['BALANCE PER WAREHOUSE'] = ['icon' => 'fa fa-warehouse', 'customform' => $warehouse];
        }
        if ($view_po_jo_order_history) {
          $return['PURCHASE ORDER/ JOB ORDER HISTORY'] = ['icon' => 'fa fa-shopping-basket', 'customform' => $po];
        }
        if ($view_sales_order_history) {
          $return['SALES ORDER HISTORY'] = ['icon' => 'fa fa-cart-arrow-down', 'customform' => $so];
        }
        break;

      default:
        $return['HISTORY'] = ['icon' => 'fa fa-history', 'customform' => $history];
        $return['IN-TRANSACTION'] = ['icon' => 'fa fa-inbox', 'customform' => $intransaction];
        $return['UNIT OF MEASUREMENT'] = ['icon' => 'fa fa-weight', 'tab' => $uom];
        $return['BALANCE PER WAREHOUSE'] = ['icon' => 'fa fa-warehouse', 'customform' => $warehouse];
        $return['PURCHASE ORDER/ JOB ORDER HISTORY'] = ['icon' => 'fa fa-shopping-basket', 'customform' => $po];
        if ($config['params']['companyid'] != 8) { //not maxipro
          $return['SALES ORDER HISTORY'] = ['icon' => 'fa fa-cart-arrow-down', 'customform' => $so];
        }
        break;
    }
    if ($config['params']['companyid'] == 39 || $config['params']['companyid'] == 16) { //cbbsi & ati
      $return['PURCHASE REQUISITION HISTORY'] = ['icon' => 'fa fa-shopping-basket', 'customform' => $pr];
    }

    if ($config['params']['companyid'] == 3) { //CONTI
      $return['CANVASS SHEET'] = ['icon' => 'fa fa-shopping-basket', 'customform' => $cd];
    }
    switch ($companyid) {
      case 20: //proline
        $return['COMPONENT'] = ['icon' => 'fa fa-drafting-compass', 'customform' => $component];
        break;
      case 21: // kinggeorge
        if ($view_component) {
          $return['COMPONENT'] = ['icon' => 'fa fa-drafting-compass', 'tab' => $component];
        }
        if ($view_stocklevel) {
          $return['STOCK LEVEL'] = ['icon' => 'fa fa-level-up-alt', 'tab' => $stocklevel];
        }
        if ($view_compatible) {
          $return['COMPATIBLE'] = ['icon' => 'fa fa-ethernet', 'tab' => $compatible];
        }
        if ($view_equivalent_sku) {
          $return['EQUIVALENT SKU'] = ['icon' => 'fa fa-equals', 'tab' => $sku];
        }
        break;
      default:
        if ($config['params']['companyid'] <> 40) { //not cdo 

          $return['COMPONENT'] = ['icon' => 'fa fa-drafting-compass', 'tab' => $component];
          if ($this->companysetup->getserial($config['params'])) {
            $serial = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewserialhistory']];
            $return['SERIAL HISTORY'] = ['icon' => 'fa fa-qrcode', 'customform' => $serial];
          }

          if ($companyid == 56) { //homeworks
            $return['MIN/MAX'] = ['icon' => 'fa fa-code-branch', 'tab' => $minmax];
            if ($supplier_list) {
              $return['SUPPLIER LIST'] = ['icon' => 'fa fa-user-tag', 'tab' => $supplierlist];
            }
            if ($posprice_list) {
              $return['PRICE LIST'] = ['icon' => 'fa fa-th-list', 'tab' => $pospricelist];
            }

            if ($pospricescheme_list) {
              $return['PRICE SCHEME'] = ['icon' => 'fa fa-tags', 'tab' => $pospricescheme];
            }

            if ($pospromoperitem_list) {
              $return['PROMO PER ITEM'] = ['icon' => 'fa fa-percent', 'tab' => $pospromoperitem];
            }
          } else {
            $return['STOCK LEVEL'] = ['icon' => 'fa fa-level-up-alt', 'tab' => $stocklevel];
            $return['COMPATIBLE'] = ['icon' => 'fa fa-ethernet', 'tab' => $compatible];

            if ($companyid == 63) { //ericco
              $return['BARCODE LIST'] = ['icon' => 'fa fa-equals', 'tab' => $sku];
              $return['SUPPLIER'] = ['icon' => 'fa fa-equals', 'tab' => $sku2];
            } else {
              $return['EQUIVALENT SKU'] = ['icon' => 'fa fa-equals', 'tab' => $sku];
            }
          }
        }
        break;
    }

    if ($config['params']['companyid'] == 39) { //cbbsi
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 4803);
      if ($changecode) {
        $changecode = ['customform' => ['action' => 'customform', 'lookupclass' => 'changebarcode']];
        $return['CHANGE BARCODE'] = ['icon' => 'fa fa-qrcode', 'customform' => $changecode];
      }
    }
    return $return;
  }

  public function tabprice($config) {}

  public function createTab($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti & afti usd
      $this->modulename = 'MAIN ITEMS';
    }
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $isserial = $this->companysetup->getserial($config['params']);
    $ispos =  $this->companysetup->getispos($config['params']);
    $isnonserial = $this->companysetup->getisnonserial($config['params']);
    $viewsupplier_field = $this->othersClass->checkAccess($config['params']['user'], 5485);

    switch ($systemtype) {
      case 'WAIMS':
        $fields = ['barcode', 'itemname', ['cost', 'uom'], ['critical', 'reorder'], 'dclientname', 'partno', 'subcode'];
        break;
      default:

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $fields = ['barcode', 'partno', 'itemname', 'uom', 'dclientname', 'moq', 'mmoq'];
            break;
          case 14: //majesty
            $fields = ['barcode', 'itemname', 'uom', ['critical', 'reorder'], 'dclientname', 'subcode', 'partno'];
            break;

          default:
            $fields = ['barcode', 'itemname', 'uom', ['critical', 'reorder'], 'dclientname', 'partno'];
            break;
        }
        break;
    }


    if ($ispos) {
      array_push($fields, 'shortname');
    }

    switch ($companyid) {
      case 39: //cbbbsi
        array_push($fields, 'barcodeid');
        break;
      case 16: //ati
        array_push($fields, 'shortname', 'othcode');
        break;
      case 19: //housegem
        array_push($fields, 'tqty', 'maximum');
        break;
      case 43: //mighty
        array_push($fields, 'item_length', 'item_width', 'item_height', 'volume', 'weight');
        break;
      case 50: //unitech
        array_push($fields, 'weight');
        break;
      case 60: //transpower
        array_push($fields, 'minimum', 'maximum');
        $viewsupplier_field = $this->othersClass->checkAccess($config['params']['user'], 5485); //
        if (!$viewsupplier_field) {
          $key = array_search('dclientname', $fields);
          if ($key !== false) {
            unset($fields[$key]);
          }
        }
        break;
    }

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

    switch ($companyid) {
      case 14: //majesty
        data_set($col1, 'subcode.label', 'Supp Item Code');
        break;
      case 16: //ati
        data_set($col1, 'shortname.label', 'Specifications');
        data_set($col1, 'shortname.maxlength', 240);
        data_set($col1, 'othcode.label', 'Barcode Name');
        break;
      case 19: //housegem
        data_set($col1, 'critical.label', 'Safety Stock Level');
        break;
      case 21: //kinggeorge
        data_set($col1, 'itemname.maxlength', 50);
        break;
      case 28: //xcomp
        data_set($col1, 'itemname.maxlength', 500);
        break;
      case 40: //cdo
        data_set($col1, 'partno.required', true);
        break;
    }

    switch ($systemtype) {
      case 'WAIMS':
        switch ($companyid) {
          case 6: //mitsukoshi
            $fields = ['categoryname', 'subcatname', 'modelname', 'brandname', 'stockgrp', 'packaging', 'dqty'];
            break;
          default:
            $fields = ['categoryname', 'subcatname', 'partname', 'modelname', 'classname', 'brandname', 'stockgrp', 'packaging'];
            break;
        }
        break;

      default:
        switch ($companyid) {
          case 22: //eipi
            $fields = ['classname', 'brandname', 'categoryname', 'stockgrp', 'subcatname', 'modelname', 'partname'];
            break;
          case 16: //ati
            $fields = ['stockgrp', 'categoryname', 'subcatname'];
            break;
          case 47: //kitchenstar
            $fields = ['modelname', 'classname', 'brandname', 'stockgrp', 'categoryname', 'subcatname'];
            break;
          default:
            $fields = ['partname', 'modelname', 'classname', 'brandname', 'stockgrp', 'categoryname', 'subcatname'];
            break;
        }
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'projectname');
        break;
      case 14: //majesty
        array_push($fields, 'deptname');
        break;
      case 19: //hpusegem
        array_push($fields, ['aveleadtime', 'maxleadtime']);
        break;
      case 43: //mighty
        array_push($fields, 'engine', 'serialno', 'renewaldate');
        break;
      case 47: //kitchenstar
        array_push($fields, ['dqty', 'tqty']);
        break;
      case 56; //homeworks
        array_push($fields, 'channel', 'avecost');
        break;
      case 60: //transpower
        array_push($fields, 'startwire', 'endwire');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
    data_set($col2, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

    data_set($col2, 'dqty.label', 'Per Box/Pack/Set');


    switch ($companyid) {
      case 10:
      case 12: //afti
        data_set($col2, 'projectname.type', 'lookup');
        data_set($col2, 'projectname.action', 'lookupproject');
        data_set($col2, 'projectname.lookupclass', 'stockcardproject');
        data_set($col2, 'projectname.label', 'Item Group');
        data_set($col2, 'projectname.required', true);
        break;
      case 14: //majesty
        data_set($col2, 'partname.label', 'Principal');
        data_set($col2, 'stockgrp.label', 'Divsion');
        data_set($col2, 'modelname.label', 'Generic');
        data_set($col2, 'classname.label', 'Classification');
        data_set($col2, 'deptname.type', 'lookup');
        data_set($col2, 'deptname.action', 'lookupclient');
        data_set($col2, 'deptname.lookupclass', 'lookupitemdept');
        break;
      case 42: //pdpi mis
        data_set($col2, 'stockgrp.required', true);
        break;
      case 40: //cdo
        data_set($col2, 'categoryname.required', true);
        data_set($col2, 'modelname.required', true);
        break;
      case 22: //eipi
        data_set($col2, 'categoryname.label', 'Category 1');
        data_set($col2, 'stockgrp.label', 'Category 2');
        data_set($col2, 'subcatname.label', 'Category 3');
        data_set($col2, 'modelname.label', 'Category 4');
        data_set($col2, 'partname.label', 'Category 5');
        break;
      case 47: //kitchenstar
        data_set($col2, 'tqty.label', 'QTY/CTN');
        data_set($col2, 'dqty.label', 'CBM');
        break;
    }

    switch ($systemtype) {
      case 'MIS':
        $fields = ['body', 'sizeid', 'color'];
        break;
      default:
        if ($companyid == 47) { //kitchenstar
          $fields = ['sizeid', 'color', 'dasset', 'dliability', 'drevenue', 'dexpense', 'dsalesreturn', 'foramt'];
        } else {
          $fields = ['body', 'sizeid', 'color', 'dasset', 'dliability', 'drevenue', 'dexpense', 'dsalesreturn', 'foramt'];
        }
        break;
    }

    switch ($companyid) {
      case 43: //mighty
        array_push($fields, 'chassisno', 'endinsured', 'dateacquired', 'warrantyend', 'leasedate', 'disposaldate');
        break;
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'foramt.type', 'cinput');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        data_set($col3, 'body.type', 'input');
        data_set($col3, 'body.class', '');
        data_set($col3, 'body.label', 'Old barcode');
        break;
      case 14: //majesty
        data_set($col3, 'body.label', 'Form');
        data_set($col3, 'sizeid.label', 'Bin');
        break;
      case 39: //cbbsi
        data_set($col3, 'body.label', 'Bin');
        break;
      case 40: //cdo
        data_set($col3, 'body.label', 'Superseding Part');
        break;
      case 43: //mighty
        data_set($col3, 'endinsured.label', 'Insurance Expiry');
        data_set($col3, 'dateacquired.label', 'Acquired Date');
        data_set($col3, 'warrantyend.label', 'Warranty Expiry');
        data_set($col3, 'leasedate.label', 'In Service Date');
        data_set($col3, 'disposaldate.label', 'Sold/Disposal Date');
        break;
      case 47: //kitchenstar
        data_set($col3, 'foramt.label', 'Floor Price');
        data_set($col3, 'foramt.required', true);
        break;
    }

    switch ($companyid) {
      case 6: // mitsukoshi
        $fields = ['picture', 'rem', ['isinactive', 'isvat'], ['isimport', 'fg_isfinishedgood'], ['fg_isequipmenttool', 'isnoninv']];
        break;
      case 63: // ericco
        $fields = ['picture', 'rem', ['isinactive', 'isvat'], ['isimport', 'isfg'], ['fg_isequipmenttool', 'isnoninv'], ['islabor']];
        break;
      default:
        $fields = ['picture', 'rem', ['isinactive', 'isvat'], ['isimport', 'fg_isfinishedgood'], ['fg_isequipmenttool', 'isnoninv'], 'islabor'];
        break;
    }
    if ($isserial) {
      array_push($fields, 'isserial');
    }
    if ($ispos) {
      array_push($fields, ['ispositem', 'isprintable']);
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'noncomm');
        break;
      case 16: //ati
        array_push($fields, 'isgeneric');
        break;
      case 19: //housegem
      case 24: //goodfound
        array_push($fields, 'isofficesupplies');
        break;
      case 60: //transpower
        array_push($fields, 'iswireitem', 'isreversewireitem');
        break;
    }

    if ($systemtype == 'FAMS') {
      array_push($fields, 'isgeneric');
    }

    if ($isnonserial) {
      array_push($fields, 'isnonserial');
    }



    $col4 = $this->fieldClass->create($fields);
    if ($companyid == 24) { //goodfound
      data_set($col4, 'fg_isequipmenttool.label', 'Materials Item');
      data_set($col4, 'isofficesupplies.label', 'Supplies Item');
    }
    if ($companyid == 43) { //mighty
      data_set($col4, 'fg_isequipmenttool.label', 'Asset/Truck');
    }
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
    $data[0]['shortname'] = '';
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $data[0]['uom'] = 'EA';
    } else {
      $data[0]['uom'] = 'PCS';
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
    $data[0]['salesreturn'] = '';
    $data[0]['salesreturnname'] = '';
    $data[0]['linkdept'] = '0';
    $data[0]['deptname'] = '';
    $data[0]['isinactive'] = '0';
    $data[0]['isvat'] = '0';
    $data[0]['isimport'] = '0';

    if ($companyid == 59) { //roosevelt
      $data[0]['isnoninv'] = '1';
    } else {
      $data[0]['isnoninv'] = '0';
    }

    $data[0]['isserial'] = '0';
    $data[0]['ispositem'] = '0';
    $data[0]['isprintable'] = '0';
    $data[0]['noncomm'] = '0';

    switch ($companyid) {
      case 6: //mitsukoshi
        $data[0]['fg_isfinishedgood'] = '1';
        $data[0]['islabor'] = '0';
        break;
      default:
        $data[0]['fg_isfinishedgood'] = '0';
        $data[0]['islabor'] = '0';
        break;
    }

    $data[0]['fg_isequipmenttool'] = '0';
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
    $data[0]['subcode'] = '';
    $data[0]['packaging'] = '';
    $data[0]['cost'] = 0;
    $data[0]['subcat'] = '0';
    $data[0]['subcatname'] = '';
    $data[0]['color'] = '';
    $data[0]['dqty'] = 0;
    $data[0]['tqty'] = 0;
    $data[0]['othcode'] = $companyid == 16 ? $config['newbarcode'] : ''; //mitsukoshi

    $data[0]['projectid'] = 0;
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['dasset'] = '';
    $data[0]['dliability'] = '';
    $data[0]['dexpense'] = '';
    $data[0]['drevenue'] = '';

    $data[0]['isoutsource'] = '0';
    $data[0]['moq'] = '0';
    $data[0]['mmoq'] = '0';

    $data[0]['isofficesupplies'] = '0';
    $data[0]['isgeneric'] = '0';

    $data[0]['item_length'] = '';
    $data[0]['item_width'] = '';
    $data[0]['item_height'] = '';
    $data[0]['volume'] = '';
    $data[0]['weight'] = '';
    $data[0]['engine'] = '';
    $data[0]['serialno'] = '';
    $data[0]['renewaldate'] = '';
    $data[0]['chassisno'] = '';
    $data[0]['endinsured'] = null;
    $data[0]['dateacquired'] = null;
    $data[0]['warrantyend'] = null;
    $data[0]['leasedate'] = null;
    $data[0]['disposaldate'] = null;
    $data[0]['barcodeid'] = 0;
    $data[0]['avecost'] = 0;
    $data[0]['israwmat'] = '0';
    $data[0]['channel'] = '';
    $data[0]['isnonserial'] = '0';
    $data[0]['iswireitem'] = '0';
    $data[0]['startwire'] = 0;
    $data[0]['endwire'] = 0;
    $data[0]['maximum'] = 0;
    $data[0]['aveleadtime'] = 0;
    $data[0]['maxleadtime'] = 0;
    $data[0]['minimum'] = 0;
    $data[0]['isreversewireitem'] = 0;
    $data[0]['isfg'] = '0';

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $filter = ' and item.isoutsource =0 ';
    }

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

    foreach ($this->iteminfo as $key => $value) {
      $fields = $fields . ',info.' . $value;
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
        '' as dsalesreturn,
        ifnull(dept.clientname,'') as deptname,
        item.linkdept,item.avecost,item.maximum,item.aveleadtime,item.maxleadtime";

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
        left join projectmasterfile as prj on prj.line = item.projectid
        left join client as dept on dept.clientid=item.linkdept
        left join iteminfo as info on info.itemid=item.itemid
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
    $iteminfo = [];

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


    foreach ($this->iteminfo as $key) {
      if (!in_array($key, $this->except)) {
        if (array_key_exists($key, $head)) {
          $iteminfo[$key] = $head[$key];
          $iteminfo[$key] = $this->othersClass->sanitizekeyfield($key, $iteminfo[$key]);
        }
      } //end if    
    }

    if ($companyid == 16 ||  $systemtype == 'FAMS') { //ati
      if ($data['isgeneric']) {
        $data['isnoninv'] = "1";
      }
    }
    if ($companyid == 40) { //cdo
      if ($data['groupid'] != 0) {
        $grpname = $this->coreFunctions->getfieldvalue("stockgrp_masterfile", "stockgrp_name", "stockgrp_id=?", [$data['groupid']]);
        if ($grpname == 'MC UNIT') {
          $data['isserial'] = "1";
        }
      }
      $data['shortname'] = str_replace("-", "", $data['partno']);
    }

    if ($companyid == 63) { //ericco
      if ($data['isfg'] == "1") {
        $data['isnoninv'] = "1";
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['ismirror'] = 0;
    if ($isupdate) {
      $current_uom = $this->coreFunctions->getfieldvalue("item", "uom", "itemid=?", [$head['itemid']]);

      /* FOR NTE ADD SAVING DEFAULT UOM EVEN IF W/ TRANSACTION, BUT HAVE AN UOM ON UOM TAB*/
      switch ($companyid) {
        case 27: //NTE
        case 22: //eipi
        case 36: //ROZLAB
        case 49: //hotmix
          $d_uom = $this->coreFunctions->opentable("select uom as value from uom where itemid = ? and uom = ?", [$head['itemid'], $data['uom']]);
          if ($d_uom) {
            $this->coreFunctions->sbcupdate('item', $data, ['itemid' => $head['itemid']]);
            $itemid = $head['itemid'];
            array_push($this->fields, 'barcode');
            array_push($this->fields, 'picture');
            return $itemid;
          }
          break;
      }
      /* END */

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

      switch ($companyid) {
        case 43: //mighty
        case 50: //unitech
          $exist = $this->coreFunctions->getfieldvalue("iteminfo", "itemid", "itemid=?", [$itemid], '', true);
          if ($exist == 0) {
            $iteminfo['itemid'] = $itemid;
            $this->coreFunctions->sbcinsert("iteminfo", $iteminfo);
          } else {
            $this->coreFunctions->sbcupdate('iteminfo', $iteminfo, ['itemid' => $head['itemid']]);
          }
          break;
      }
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];


      $default1 = 0;
      switch ($companyid) {
        case 27: //NTE
        case 36: //ROZLAB
          $default1 = 1;
          break;
      }

      $itemid = $this->coreFunctions->insertGetId('item', $data);
      $this->coreFunctions->execqry('insert into uom(itemid,uom,factor,isdefault) values(?,?,1,?)', 'INSERT', [$itemid, $data['uom'], $default1]);

      switch ($companyid) {
        case 43:
        case 50:
          $iteminfo['itemid'] = $itemid;
          $this->coreFunctions->sbcinsert("iteminfo", $iteminfo);
          break;
      }

      if ($companyid == 39) { //cbbsi
        //check barcodeid
        $exist  = $this->coreFunctions->datareader("select barcodeid as value from item where barcodeid = '" . $data['barcodeid'] . "' limit 1");
        if ($exist != "") {
          $this->logger->sbcwritelog($itemid, $config, 'Update',  $data['barcodeid'] . " Item ID already exist.");
          $data['barcodeid'] = 0;
        }

        if ($data['barcodeid'] == 0) {
          $this->coreFunctions->execqry("update item set barcodeid = " . $itemid . " where itemid=?", "update", [$itemid]);
        }
      }

      $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $head['barcode'] . ' - ' . $head['itemname']);
    }
    return $itemid;
  } // end function

  public function getlastbarcode($pref, $companyid = 0, $sort = 'barcode')
  {
    $length = strlen($pref);
    $return = '';
    $filter = '';
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $filter = ' and isoutsource = 0';
    }

    if ($companyid == 16) { //ati
      $filter = ' and isfa = 0';
    }

    $ctr = 0;

    checklastcodehere:
    if ($length == 0) {
      $return = $this->coreFunctions->datareader("select barcode as value from item where ''='' " . $filter . " order by " . $sort . " desc limit 1");
    } else {
      $return = $this->coreFunctions->datareader("select barcode as value from item where left(barcode,?)=? " . $filter . " order by " . $sort . " desc limit 1", [$length, $pref]);
    }

    $this->coreFunctions->LogConsole($return);

    if ($companyid == 16) { //ati
      if ($ctr == 0) {
        $exist = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$return], '', true);
        if ($exist != 0) {
          $sort = "itemid";
          $ctr = 1;
          goto checklastcodehere;
        }
      }
    }

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
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $qry = "select itemid as value from item where itemid<? and isinactive=0 and isoutsource=0 order by itemid desc limit 1 ";
    }

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
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $filter = ' and item.isoutsource =0 ';
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
        ifnull(prj.code, '') as projectcode,
        ifnull(prj.name, '') as projectname,
        '' as dasset,
        '' as dliability,
        '' as dexpense,
        '' as drevenue,
        '' as dsalesreturn,
        ifnull(dept.clientname,'') as deptname,
        item.linkdept";

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
        left join projectmasterfile as prj on prj.line = item.projectid
        left join client as dept on dept.clientid=item.linkdept
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

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

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
