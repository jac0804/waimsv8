<?php

namespace App\Http\Classes\modules\reportlist\items;

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



use PDF;
use TCPDF_FONTS;
use DateTime;
use Illuminate\Support\Facades\Storage;

class item_list
{
  public $modulename = 'Item List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1000px;max-width:1000px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'brandid', 'class'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'brandid.name', 'brandid');

        unset($col1['divsion']['labeldata']);
        unset($col1['class']['labeldata']);
        unset($col1['labeldata']['divsion']);
        unset($col1['labeldata']['class']);
        data_set($col1, 'divsion.name', 'stockgrp');
        data_set($col1, 'class.name', 'classic');

        $fields = ['radiosortby', 'radioreportitemtype', 'radioreportitemstatus', 'radioitemsort'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radiosortby.options', array(
          ['label' => 'Group', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Brand', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Class', 'value' => '2', 'color' => 'orange'],
          ['label' => 'Item Name', 'value' => '3', 'color' => 'orange'],
        ));

        data_set($col2, 'radioitemsort.options', array(
          ['label' => 'Retail', 'value' => 'amt', 'color' => 'orange'],
          ['label' => 'Whole', 'value' => 'amt2', 'color' => 'orange'],
          ['label' => 'Price 1', 'value' => 'famt', 'color' => 'orange'],
          ['label' => 'Price 2', 'value' => 'amt4', 'color' => 'orange'],
        ));

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        break;

      default:
        $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'categoryname', 'subcatname'];
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            array_push($fields, 'project');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'project.required', false);
            data_set($col1, 'project.label', 'Item Group/Project');
            break;
          case 21: //kinggeorge
            array_push($fields, 'dclientname');
            $col1 = $this->fieldClass->create($fields);
            break;
          case 56:
            $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'categoryname', 'subcatname', 'dclientname'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'radioprint.options', [
              // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
              ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
              ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
            ]);
            data_set($col1, 'dclientname.lookupclass', 'wasupplier');
            break;
          case 60: //transpower
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'radioprint.options', [
              ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
              ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
            ]);
            break;
          default:
            $col1 = $this->fieldClass->create($fields);
            break;
        }

        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'brandid.name', 'brandid');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

        unset($col1['divsion']['labeldata']);
        unset($col1['class']['labeldata']);
        unset($col1['labeldata']['divsion']);
        unset($col1['labeldata']['class']);
        data_set($col1, 'divsion.name', 'stockgrp');
        data_set($col1, 'class.name', 'classic');

        $fields = ['radioreporttype', 'radioreportitemtype', 'radioreportitemstatus'];
        if ($companyid == 56) { // homeworks
          //'radiolayoutformat',
          $fields = ['radioisassettag', 'radioreportitemtype', 'radioreportitemstatus'];
        }
        $col2 = $this->fieldClass->create($fields);
        if ($companyid == 56) { // homeworks
          // data_set(
          //   $col2,
          //   'radiolayoutformat.options',
          //   [
          //     ['label' => 'View Price', 'value' => '0', 'color' => 'orange'],
          //     ['label' => 'View Cost', 'value' => '1', 'color' => 'orange'],
          //     ['label' => 'View Cost and Price', 'value' => '2', 'color' => 'orange']
          //   ]
          // );
          data_set($col2, 'radioisassettag.label', 'Item List');
          data_set(
            $col2,
            'radioisassettag.options',
            [
              ['label' => 'Fix/Asset Items', 'value' => '1', 'color' => 'teal'],
              ['label' => 'Items', 'value' => '2', 'color' => 'teal']
            ]
          );
        } else {
          data_set(
            $col2,
            'radioreporttype.options',
            [
              ['label' => 'Item List', 'value' => '0', 'color' => 'green'],
              ['label' => 'Price Group', 'value' => '1', 'color' => 'green']
            ]
          );
        }


        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        break;
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $isactive = "(0,1)";
    if ($companyid == 56) { // homeworks
      $isactive = "(0)";
    }
    $paramstr = "select 
      'default' as print,
      0 as itemid,
      '' as itemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as categoryid,
      '' as categoryname,
      '' as brandname,
      '' as brand,
      0 as classid,
      '' as classic,
      '' as ditemname,
      '' as divsion,
      '' as category,
      '' as subcatname,
      '' as subcat,
      '(0,1)' as itemtype,
      '" . $isactive . "' as itemstatus,
      '' as class,
      '0' as sortby,
      'amt' as itemsort,
      '0' as reporttype, 
      '' as project, 
      0 as projectid, 
      '' as projectname, 
      '' as client, 
      0 as clientid, 
      '' as clientname, 
      '' as dclientname,
      '2' as isassettag,
      '0' as layoutformat";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $result = $this->reportDefault($config);
    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];
    $print = $config['params']['dataparams']['print'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_Layout($config, $result);
        break;

      case 37: //mcpc
        switch ($reporttype) {
          case 1:
            $result = $this->reportPriceGroupLayout($config, $result);
            break;

          default:
            $result = $this->mcpc_layout($config, $result);
            break;
        }
        break;
      case 56: //homeworks
        // switch ($reporttype) {
        //   case 0:
        // if ($print == 'PDFM') {
        //   return $this->homework_PDF_layout_item($config, $result);
        //   // return $this->test_homework_PDF_layout_item($config, $result);
        // }
        //   $result = $this->homework_layout_item($config, $result);
        //   break;
        // case 1:
        // if ($print == 'PDFM') {
        //   return $this->homework_PDF_layout_with_promo($config, $result);
        // }
        // $result = $this->homework_layout_item_with_promo($config, $result);
        $result = $this->homework_layout_item_with_promo_test($config, $result); // testing
        // break;
        // case 2: // price group layouyt
        //   if ($print == 'PDFM') {
        //     return $this->homework_PDF_layout_pricegroup($config, $result);
        //   }
        //   $result = $this->reportPriceGroupLayout($config, $result);
        //   break;
        // }
        break;

         case 60: //transpower
        switch ($reporttype) {
          case 1:
            $result = $this->reportPriceGroupLayout($config, $result);
            break;

          default: //itemlist
            $result = $this->transpower_layout($config, $result);
            break;
        }
        break;

      default:
        switch ($reporttype) {
          case 1:
            $result = $this->reportPriceGroupLayout($config, $result);
            break;

          default:
            $result = $this->reportDefaultLayout($config, $result);
            break;
        }
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $query = $this->VITALINE_QUERY($config);
        break;
      case 56: // homeworks
        $query = $this->homeworks_query($config);
        break;
      case 60://transpower
           $query = $this->TRANSPOWER_QUERY($config);
         break;

      default: // default
        $query = $this->DEFAULT_QUERY($config);
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  // QUERY
  public function DEFAULT_QUERY($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";
    $filter1 = "";
    $addselect = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }

    $leftjoins = "";
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and item.projectid = $projectid";
      }
      $addselect = ",item.uom,item.partno, p.name as stockgrp_name, left(i.itemdescription,50) as itemdescription";
      $leftjoins = "left join iteminfo as i on i.itemid = item.itemid 
      left join projectmasterfile as p on p.line = item.projectid ";
    } else {
      $filter1 .= "";
    }

    if ($companyid == 21) { //kinggeorge
      $client = $config['params']['dataparams']['client'];
      if ($client != "") {
        $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
        $filter .= " and supplier='" . $clientid . "' ";
      }
    }

    if ($companyid == 28) { //xcomp
      $order = " order by barcode";
    } else {
      $order = " order by ifnull(parts.part_name,''),brand,itemname";
    }

    if ($companyid == 14) { //majesty
      $exclude = " and item.itemid not in (4742,4743,4744,4745,4746,4747,4748,4749)";
    } else {
      $exclude = "";
    }

    if ($reporttype == 1) {
      $addselect = ",amt as retail,amt2 as wholesale,famt as priceA,amt4 as priceB,amt5 as priceC,amt6 as priceD,
       amt7 as priceE,amt8 as priceF,amt9 as priceG ";
    }
    switch ($companyid) {
      case '37': ///mcpc
        if ($reporttype == 0) {
          $addselect = ",concat(cl.client,'~',cl.clientname) as sup_name,item.shortname,item.uom,item.partno,cat.name as catname,item.color";
          $leftjoins = "left join client as cl on cl.clientid = item.supplier ";
        }
        break;
    }


    $query = "select sizeid as size,current_timestamp as print_date, 0 as sort, barcode, itemname,
    ifnull(stockgrp.stockgrp_name,'') as groupid, frontend_ebrands.brand_desc as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,
    item.body,item.class,item.supplier,item.cost, amt as price, item.isinactive,ifnull(itclass.cl_name,'') as cl_name,
    item.category,subcat.name $addselect
    from item 
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
    left join part_masterfile as parts on parts.part_id = item.part
    left join model_masterfile as mm on mm.model_id=item.model
    left join item_class as itclass on item.class = itclass.cl_id
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    $leftjoins
    where item.barcode <> '' and item.isinactive in $itemstatus and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0 $exclude
    $order";
    return $query;
  }

  public function VITALINE_QUERY($config)
  {
    $barcode    = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $price = $config['params']['dataparams']['itemsort'];

    $filter = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }

    switch ($config['params']['dataparams']['sortby']) {
      case 0:
        $order = " order by groupid";
        break;
      case 1:
        $order = " order by brand";
        break;
      case 2:
        $order = " order by cl_name";
        break;
      default:
        $order = " order by itemname";
        break;
    }

    $query = "select sizeid as size,current_timestamp as print_date, 0 as sort, barcode, itemname,
    ifnull(stockgrp.stockgrp_name,'') as groupid, frontend_ebrands.brand_desc as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,
    body,class,supplier,cost, " . $price . " as price, item.isinactive,ifnull(itclass.cl_name,'') as cl_name
    from item 
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
    left join part_masterfile as parts on parts.part_id = item.part
    left join model_masterfile as mm on mm.model_id=item.model
    left join item_class as itclass on item.class = itclass.cl_id
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    where item.barcode <> '' and item.isinactive in $itemstatus and item.isimport in $itemtype $filter $order";

    return $query;
  }
  public function homeworks_query($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $clientid = $config['params']['dataparams']['clientid'];
    $isassettag = $config['params']['dataparams']['isassettag'];


    $filter = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($clientid != 0) {
      $filter .= " and item.supplier='$clientid'";
    }

    $order = " order by cat.name,itclass.cl_name,stockgrp.stockgrp_name,ifnull(parts.part_name,''),frontend_ebrands.brand_desc,itemname";
    // if ($reporttype == 2) { // for price group add
    //   $addselect = ",amt as retail,amt2 as wholesale,famt as priceA,amt4 as priceB,amt5 as priceC,amt6 as priceD,
    //    amt7 as priceE,amt8 as priceF,amt9 as priceG ";
    // }
    // 08-04-2025-J
    // $query = "select  item.itemid,barcode, itemname,
    // frontend_ebrands.brand_desc as brand,ifnull(stockgrp.stockgrp_name,'') as groupid,
    // ifnull(parts.part_name,'') as part,ifnull(stockgrp.stockgrp_name,'') as stockgrp,
    // item.body,item.class,item.supplier,item.cost, amt as price,item.amt2 as price2,item.avecost, item.isinactive,ifnull(itclass.cl_name,'') as cl_name,
    // item.category,cat.name as catname,cl.clientname as sup_name,cl.client as sup_code $addselect
    // from item 
    // left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
    // left join part_masterfile as parts on parts.part_id = item.part
    // left join item_class as itclass on item.class = itclass.cl_id
    // left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    // left join itemcategory as cat on cat.line = item.category
    // left join client as cl on cl.clientid = item.supplier
    // where item.barcode <> '' and item.isfa=0 and item.isinactive in $itemstatus and item.isimport in $itemtype $filter and item.isofficesupplies=0
    // $order";
    $addfield = "";

    if ($isassettag == '1') {
      $filter .= " and item.isfa=1";
    } else {
      if ($isassettag == '2') {
        $filter .= " and item.isfa=0";
      }
    }
    $current = $this->othersClass->getCurrentDate();
    // $addfield = ",(select  concat(date_format(startdate,'%m-%d-%y'), ' / ', date_format(enddate,'%m-%d-%y')) as promo
    // from pricelist
    // where '" . $current . "' between date(startdate) and date(enddate)
    // and clientid = 0 and itemid = item.itemid limit 1) as promo,
    // (select amount
    // from pricelist
    // where '" . $current . "' between date(startdate) and date(enddate)
    // and clientid = 0 and itemid = item.itemid limit 1) as amount ";


    $addfield = ",(select concat(date_format(head.dateid,'%m-%d-%y'), ' / ', date_format(head.due,'%m-%d-%y')) as promo from 
    hpahead as head
    left join hpastock as stock on stock.trno = head.trno
    where stock.itemid = item.itemid and '" . $current . "' between date(head.dateid) and date(head.due) limit 1) as promo
    ,(select stock.ext from 
    hpahead as head
    left join hpastock as stock on stock.trno = head.trno
    where stock.itemid = item.itemid and '" . $current . "' between date(head.dateid) and date(head.due) limit 1) as amount";


    $query = "
    select  item.itemid,barcode, itemname,
    frontend_ebrands.brand_desc as brand,ifnull(stockgrp.stockgrp_name,'') as groupid,
    ifnull(parts.part_name,'') as part,ifnull(stockgrp.stockgrp_name,'') as stockgrp,
    item.body,item.class,item.supplier,item.cost, amt as price,item.amt2 as price2,item.avecost, item.isinactive,ifnull(itclass.cl_name,'') as cl_name,
    item.category,cat.name as catname,cl.clientname as sup_name,cl.client as sup_code $addfield
    
    from item 
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
    left join part_masterfile as parts on parts.part_id = item.part
    left join item_class as itclass on item.class = itclass.cl_id
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join itemcategory as cat on cat.line = item.category
    left join client as cl on cl.clientid = item.supplier
	  where item.barcode <> '' and item.isinactive in $itemstatus 
	  and item.isimport in $itemtype $filter  and item.isofficesupplies=0 $order";
    return $query;
  }


    public function TRANSPOWER_QUERY($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $barcode    = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";
    $addselect = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }


    if ($reporttype == 1) {
      $addselect = ",amt as retail,amt2 as wholesale,famt as priceA,amt4 as priceB,amt5 as priceC,amt6 as priceD,
       amt7 as priceE,amt8 as priceF,amt9 as priceG ";
    }

    $query=" select 0 as sort, item.barcode, item.itemname, cat.name as maincat,subcat.name as subcatname,
       frontend_ebrands.brand_desc as brand,item.uom as unit,
       item.amt5 as invoiceprice, item.disc5 as invoicedisc,item.namt5 as netinvoice,
       item.amt as baseprice,item.disc as basedisc,
       item.amt2 as wholesaleprice, item.disc2 as wholesaledisc,item.namt2 as netwholesale,
       item.amt4 as cost, item.disc4 as costdisc, item.namt4 as netcost,
       item.famt as distr,item.disc3 as distrdisc, item.nfamt as netdistr,
       item.amt6 as lowestp,item.amt6 as lowestdisc, item.namt6 as netlow,
       item.amt7 as drp, item.disc7 as drdisc,item.namt7  as netdr,
       item.minimum, item.maximum,
       item.startwire,item.endwire, item.iswireitem as itemwiretag, item.isreversewireitem as reversewiretag,
       item.isinactive as inactiveitemtag $addselect
    from item
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as mm on mm.model_id=item.model
    left join item_class as itclass on item.class = itclass.cl_id
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where item.barcode <> '' and item.isinactive in $itemstatus and item.isimport in $itemtype $filter  and item.isofficesupplies=0
    order by brand,itemname";
    return $query;
  }


  

  private function MAJESTY_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }


    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM LISTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . $rgroup, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand :' . $rbrand, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class :' . $rclass, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function MAJESTY_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('PRINCIPAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BRAND NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('GROUP / CATEGORY', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '125', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function MAJESTY_Layout($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $this->reporter->linecounter = 0;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);

    $str .= $this->MAJESTY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $part = "";
    $brand = "";
    foreach ($result as $key => $data) {
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      $str .= $this->reporter->col($part, '100', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->groupid, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price, '125', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($isinactive, '50', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $brand = strtoupper($data->brand);
      $part = $data->part;

      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    $supp = '';
    if ($companyid == 21) $supp = $config['params']['dataparams']['client']; //kinggeorge

    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM LISTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . $ritem, '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . $rgroup, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $rclass, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . $itemtype, '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . $rbrand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Status : ' . $itemstatus, '130', null, '', $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else if ($companyid == 21) { //kinggeorge
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item :' . $ritem, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $rgroup, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $rbrand, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page', '250');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Class :' . $rclass, '250', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Category : ' . ($categoryname == '' ? 'ALL' : $categoryname), '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Sub-Category: ' . ($subcatname == '' ? 'ALL' : $subcatname), '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . ($supp == '' ? 'ALL' : $supp), '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $rgroup, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $rbrand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $rclass, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      }

      $str .= $this->reporter->pagenumber('Page', '100');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('GROUP / CATEGORY', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function afti_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model Name', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Itemname', '175', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SKU/Part No.', '175', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Brand', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Item Description', '175', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Item Group', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $count = 45;
        $page = 45;
        $str .= $this->afti_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        foreach ($result as $key => $data) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data->model, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '175', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->partno, '175', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->brand, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->itemdescription, '175', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
          $str .= $this->reporter->endrow();

          if ($this->reporter->linecounter >= $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();

            $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
            if (!$allowfirstpage) {
              $str .= $this->default_displayHeader($config);
            }
            $str .= $this->afti_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
            $str .= $this->reporter->addline();
            $page = $page + $count;
          } //end if

        }
        break;

      default:

        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $part = "";
        $brand = "";
        foreach ($result as $key => $data) {
          if (strtoupper($part) == strtoupper($data->part)) {
            $part = "";
          } else {
            $part = $data->part;
          } //end if

          if (strtoupper($brand) == strtoupper($data->brand)) {
            $brand = "";
          } else {
            $brand = strtoupper($data->brand);
          } //end if

          $price = number_format($data->price, 2);
          if ($price == 0) {
            $price = '-';
          } //end if

          if ($companyid != 28) { //not xcomp
            if ($part != "") {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($part, '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
              $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
            if ($brand != "") {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($brand, '150', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          if ($data->isinactive) {
            $isinactive = 'INACTIVE';
          } else {
            $isinactive = 'ACTIVE';
          } //end if

          $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->groupid, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($price, '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($isinactive, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

          $brand = strtoupper($data->brand);
          $part = $data->part;

          $str .= $this->reporter->endrow();

          if ($companyid != 28) { //not xcomp
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->page_break();

              $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
              if (!$allowfirstpage) {
                $str .= $this->default_displayHeader($config);
              }
              $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
              $str .= $this->reporter->addline();
              $page = $page + $count;
            } //end if
          }
        }
        break;
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function PriceGroup_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $layoutsize = '1700';

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM LISTS  (Price Group)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . $rgroup, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand :' . $rbrand, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class :' . $rclass, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function PriceGroup_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('GROUP / CATEGORY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Retail Price', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Wholesale Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price A', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price B', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price C', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price D', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price E', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price F', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Price G', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportPriceGroupLayout($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $this->reporter->linecounter = 0;

    $count = 35; //61
    $page = 35; //60
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1700';
    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => $layoutsize];

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->PriceGroup_displayHeader($config);

    $str .= $this->PriceGroup_table_cols($layoutsize, $border, $font, $fontsize11, $config);

    $part = "";
    $brand = "";
    foreach ($result as $key => $data) {
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      if ($part != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }
      if ($brand != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->groupid, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->price == 0 ? '-' : number_format($data->price, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($isinactive, '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col(($data->retail == 0 ? '-' : number_format($data->retail, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->wholesale == 0 ? '-' : number_format($data->wholesale, 6)), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceA == 0 ? '-' : number_format($data->priceA, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceB == 0 ? '-' : number_format($data->priceB, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceC == 0 ? '-' : number_format($data->priceC, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceD == 0 ? '-' : number_format($data->priceD, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceE == 0 ? '-' : number_format($data->priceE, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceF == 0 ? '-' : number_format($data->priceF, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->priceG == 0 ? '-' : number_format($data->priceG, 6)), '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $brand = strtoupper($data->brand);
      $part = $data->part;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->PriceGroup_displayHeader($config);
        $str .= $this->PriceGroup_table_cols($layoutsize, $border, $font, $fontsize11, $config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function mobileLayout($config, $result)
  {
    $str = [];
    $printerLen = 32;

    // text sample
    array_push($str, $this->reporter->mrow(['Tenant:', '', 'C'], ['Jad Oelzon Parnaso', '', 'R']));
    array_push($str, $this->reporter->mrow(['Stall No.:'], ['1', '', 'R']));
    array_push($str, $this->reporter->mrow(['Location Code:'], ['7', '', 'R']));
    array_push($str, $this->reporter->mrow(['Date:'], ['2023-03-17', '', 'R']));
    array_push($str, $this->reporter->mrow(['Collector:'], ['Lorene Parnaso']));
    array_push($str, $this->reporter->mrow(['Ticket No.:'], ['123', '', 'R']));
    array_push($str, $this->reporter->mrow(['Outstanding Balance:'], ['15,000.00', '', 'C']));
    array_push($str, $this->reporter->mrow(['Rent:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Payment:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Net Balance:'], ['14,900.00']));
    array_push($str, $this->reporter->mrow([$this->othersClass->repeatstring('-', $printerLen)]));
    array_push($str, $this->reporter->mrow(['Payment:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Vat:'], ['12']));
    array_push($str, $this->reporter->mrow([$this->othersClass->repeatstring('-', $printerLen)]));
    array_push($str, $this->reporter->mrow(['TOTAL:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Thank you for your payment', '', 'C']));
    array_push($str, $this->reporter->mrow(['2023-03-17 22:07:00', '', 'C']));

    $string = $this->reporter->generatemreport($str, $printerLen);
    return ['view' => $string['view'], 'print' => $string['print'], 'printerLen' => $printerLen];
  }

  private function VITALINE_ITEM_LIST_HEADER($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM LISTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    $str .= $this->reporter->col('Group :' . $rgroup, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    $str .= $this->reporter->col('Brand :' . $rbrand, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }
    $str .= $this->reporter->col('Class :' . $rclass, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '400', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('GROUP / CATEGORY', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '200', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function VITALINE_ITEM_LIST($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 38;
    $page = 40;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->VITALINE_ITEM_LIST_HEADER($config);

    $part = "";
    $brand = "";
    foreach ($result as $key => $data) {
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if

      if ($part != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }
      if ($brand != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '150', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->groupid, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price, '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($isinactive, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $brand = strtoupper($data->brand);
      $part = $data->part;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->VITALINE_ITEM_LIST_HEADER($config);
        $page = $page + $count;
      } //end if
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function header_default($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $layoutsize = '1600';

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }


    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM LISTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . $rgroup, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand :' . $rbrand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class :' . $rclass, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', '100');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    return $str;
  }
  private function mcpc_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SHORT DESCRIPTION', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SUPPLIER', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SKU / PART NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('GROUP', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CLASS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SIZE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('COLOR', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->endrow();

    return $str;
  }
  public function mcpc_layout($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';

    $layoutsize = '1400';
    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => $layoutsize];
    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;');
    $str .= $this->header_default($config);


    $str .= $this->mcpc_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $part = "";
    $brand = "";
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if


      if ($part != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }
      if ($brand != "") {
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }


      $str .= $this->reporter->startrow();
      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      $str .= $this->reporter->col("'" . $data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->shortname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sup_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->partno, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->groupid, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->catname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->brand, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cl_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->size, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->color, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($isinactive, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');


      $brand = strtoupper($data->brand);
      $part = $data->part;

      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->header_default($config);
        }
        $str .= $this->mcpc_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if

    }



    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
  public function header_default_homeworks($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $layoutsize = '1400';

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }


    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $reporttitle = "ITEM LISTS";

    if ($reporttype == '1') {
      $reporttitle = "ITEM LISTS/PROMO";
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col($reporttitle, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . $rgroup, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand :' . $rbrand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class :' . $rclass, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', '100');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    return $str;
  }
  public function homework_table_cols($border, $font, $fontsize, $config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];
    $str = '';
    $layoutsize = '1400';
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SRP 1', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SRP 2', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SUPPLIER CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SUPPLIER NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CLASS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SUBCLASS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PROMO PRICE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PROMO PERIOD DATE', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function homework_layout_item($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '8';
    $fontsize11 = 10;
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';

    $layoutsize = '1300';
    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '65px;margin-top:10px;');
    $str .= $this->header_default_homeworks($config);
    $str .= $this->homework_table_cols($border, $font, $fontsize11, $config);

    $part = "";
    $brand = "";
    // $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if
      $price2 = number_format($data->price2, 2);
      if ($price2 == 0) {
        $price2 = '-';
      } //end if
      $cost = number_format($data->avecost, 2);
      if ($cost == 0) {
        $cost = '-';
      } //end if




      if ($part != "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      if ($brand != "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }



      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("'" . $data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price2, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($cost, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->brand, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->catname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sup_code, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sup_name, '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cl_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->stockgrp, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($isinactive, '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $brand = strtoupper($data->brand);
      $part = $data->part;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->header_default_homeworks($config);
        $str .= $this->homework_table_cols($border, $font, $fontsize11, $config);
        $page = $page + $count;
      } //end if

    }
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
  public function homework_layout_item_with_promo($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';

    $layoutsize = '1200';
    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;');
    $str .= $this->header_default_homeworks($config);
    $str .= $this->homework_table_cols($layoutsize, $border, $font, $font_size, $config);

    $part = "";
    $brand = "";
    // $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if
      $price2 = number_format($data->price2, 2);
      if ($price2 == 0) {
        $price2 = '-';
      } //end if
      $cost = number_format($data->cost, 2);
      if ($cost == 0) {
        $cost = '-';
      } //end if
      $promoprice = number_format($data->avecost, 2);
      if ($cost == 0) {
        $promoprice = '-';
      } //end if

      if ($part != "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      if ($brand != "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }



      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if


      $prilist_query = "select concat(date(startdate),'-',date(enddate)) as promo from pricelist  
      where itemid = '" . $data->itemid . "' and curdate() between date(startdate) and date(enddate) order by startdate desc limit 1";
      $price_data = $this->coreFunctions->opentable($prilist_query);
      $promo = '';
      if (!empty($price_data)) {
        $promo = $price_data[0]->promo;
      }
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("'" . $data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price2, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($cost, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->brand, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->catname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sup_code, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->sup_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cl_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->stockgrp, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($isinactive, '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($promoprice, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($promo, '110', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $brand = strtoupper($data->brand);
      $part = $data->part;

      // if ($this->reporter->linecounter == $page) {
      //   $str .= $this->reporter->page_break();
      //   $str .= $this->header_default_homeworks($config);
      //   $str .= $this->homework_table_cols($layoutsize, $border, $font, $fontsize11, $config);
      //   $page = $page + $count;
      // } //end if

    }
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
  public function homework_table_header_PDF($config)
  {
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];

    $font = "";
    $fontbold = "";
    $fontsize = 7;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }


    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    if ($categoryname == "") {
      $categoryname = 'ALL';
    }
    if ($subcatname == '') {
      $subcatname = 'ALL';
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(135, 25, "Item: " . $ritem, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    // PDF::MultiCell(120, 25, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "Group: " . $rgroup, '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "Brand: " . $rbrand, '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "Class: " . $rclass, '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(170, 25, "Category: " . $categoryname, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(115, 25, "Sub-Category: " . $subcatname, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }
  public function header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];


    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();


    $font = "";
    $fontbold = "";
    $fontsize = 7;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)


    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 6);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    // $this->reportheader->getheader($params);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");
    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '2':
        $reporttitle = "ITEM LISTS (Price Group)";
        break;
      case '1':
        $reporttitle = "ITEM LISTS/PROMO";
        break;
      default:
        $reporttitle = "ITEM LISTS";
        break;
    }

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(520, 0, $reporttitle, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $this->homework_table_header_PDF($params);

    // PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    switch ($reporttype) {
      case '0':
        PDF::MultiCell(55, 25, "ITEM CODE", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 25, "ITEM DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "SRP 1", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "SRP 2", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "COST", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "BRAND", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(45, 25, "CATEGORY", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "SUPPLIER CODE", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(130, 25, "SUPPLIER NAME", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 25, "CLASS", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 25, "SUBCLASS", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 25, "STATUS", 'TB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        break;
      case '1':
        PDF::MultiCell(45, 25, "ITEM CODE", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(90, 25, "ITEM DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "SRP 1", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "SRP 2", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "COST", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "BRAND", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(40, 25, "CATEGORY", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 25, "SUPPLIER CODE", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "SUPPLIER NAME", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 25, "CLASS", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(60, 25, "SUBCLASS", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 25, "STATUS", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(30, 25, "PROMO PRICE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(65, 25, "PROMO PERIOD DATE", 'TB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        break;
      case '2':

        PDF::MultiCell(55, 25, "ITEM CODE", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "ITEM DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "GROUP / CATEGORY", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "PRICE", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "STATUS", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 25, "Retail Price", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::MultiCell(65, 25, "Wholesale Price", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price A", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price B", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price C", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price D", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price E", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price F", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 25, "Price G", 'TB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        break;
    }
  }
  public function homework_PDF_layout_item($config, $result)
  {

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "5";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->header_PDF($config, $result);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    $part = "";
    $lbrand = "";
    $cat = "";
    $class = "";
    $group = "";

    for ($i = 0; $i < count($result); $i++) {
      $maxrow = 1;

      $barcode = $result[$i]->barcode;
      $itemname = $result[$i]->itemname;
      $price = $result[$i]->price != 0 ? number_format($result[$i]->price, 2) : '-';
      $price2 = $result[$i]->price2 != 0 ? number_format($result[$i]->price2, 2) : '-';
      $cost = $result[$i]->avecost != 0 ? number_format($result[$i]->avecost, 2) : '-';
      $brand = $result[$i]->brand;
      $catname = $result[$i]->catname;
      $sup_code = $result[$i]->sup_code;

      $sup_name = $result[$i]->sup_name;
      $cl_name = $result[$i]->cl_name;
      $stockgrp = $result[$i]->stockgrp;

      if ($result[$i]->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '38', 0);
      $arr_price = $this->reporter->fixcolumn([$price], '10', 0);
      $arr_price2 = $this->reporter->fixcolumn([$price2], '10', 0);
      $arr_cost = $this->reporter->fixcolumn([$cost], '10', 0);


      $arr_brand = $this->reporter->fixcolumn([$brand], '15', 0);
      $arr_catname = $this->reporter->fixcolumn([$catname], '15', 0);

      $arr_sup_code = $this->reporter->fixcolumn([$sup_code], '25', 0);
      $arr_sup_name = $this->reporter->fixcolumn([$sup_name], '25', 0);
      $arr_cl_name = $this->reporter->fixcolumn([$cl_name], '19', 0);
      $arr_stockgrp = $this->reporter->fixcolumn([$stockgrp], '18', 0);
      $arr_isinactive = $this->reporter->fixcolumn([$isinactive], '10', 0);;

      $maxrow = $this->othersClass->getmaxcolumn([
        $arr_barcode,
        $arr_itemname,
        $arr_price,
        $arr_price2,
        $arr_cost,
        $arr_brand,
        $arr_catname,
        $arr_sup_code,
        $arr_sup_name,
        $arr_cl_name,
        $arr_stockgrp,
        $arr_isinactive
      ]);

      if (strtoupper($cat) == strtoupper($result[$i]->catname)) {
        $cat = "";
      } else {
        $cat = strtoupper($result[$i]->catname);
      } //end if
      if (strtoupper($class) == strtoupper($result[$i]->cl_name)) {
        $class = "";
      } else {
        $class = strtoupper($result[$i]->cl_name);
      } //end if
      if (strtoupper($group) == strtoupper($result[$i]->stockgrp)) {
        $group = "";
      } else {
        $group = strtoupper($result[$i]->stockgrp);
      } //end if

      if ($cat != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, $cat, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      if ($class != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(10, 0, '', '', '', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(710, 0, $class, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }
      if ($group != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(20, 0, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(700, 0, $group, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }


      PDF::SetFont($font, '', $fontsize);
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::MultiCell(55, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price[$r]) ? $arr_price[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price2[$r]) ?  $arr_price2[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(45, 0, ' ' . (isset($arr_catname[$r]) ? $arr_catname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_sup_code[$r]) ? $arr_sup_code[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(130, 0, ' ' . (isset($arr_sup_name[$r]) ? $arr_sup_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 0, ' ' . (isset($arr_cl_name[$r]) ? $arr_cl_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(70, 0, ' ' . (isset($arr_stockgrp[$r]) ? $arr_stockgrp[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30, 0, ' ' . (isset($arr_isinactive[$r]) ? $arr_isinactive[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $class = strtoupper($result[$i]->cl_name);
      $group = strtoupper($result[$i]->stockgrp);
      $cat = strtoupper($result[$i]->catname);
      if (PDF::getY() > 900) {
        $this->header_PDF($config, $result);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function homework_PDF_layout_with_promo($config, $result)
  {

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "5";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->header_PDF($config, $result);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    $part = "";
    $lbrand = "";
    for ($i = 0; $i < count($result); $i++) {
      $maxrow = 1;

      $barcode = $result[$i]->barcode;
      $itemname = $result[$i]->itemname;
      $price = $result[$i]->price != 0 ? number_format($result[$i]->price, 2) : '-';
      $price2 = $result[$i]->price2 != 0 ? number_format($result[$i]->price2, 2) : '-';
      $cost = $result[$i]->cost != 0 ? number_format($result[$i]->cost, 2) : '-';
      $brand = $result[$i]->brand;
      $catname = $result[$i]->catname;
      $sup_code = $result[$i]->sup_code;

      $sup_name = $result[$i]->sup_name;
      $cl_name = $result[$i]->cl_name;
      $stockgrp = $result[$i]->stockgrp;
      $promoprice = $result[$i]->avecost != 0 ? number_format($result[$i]->avecost, 2) : '-';

      if ($result[$i]->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if
      $promo = '-';
      if ($result[$i]->promo != NULL || $result[$i]->promo != '') {
        $promo = $result[$i]->promo;
      }
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '29', 0);
      $arr_price = $this->reporter->fixcolumn([$price], '10', 0);
      $arr_price2 = $this->reporter->fixcolumn([$price2], '10', 0);
      $arr_cost = $this->reporter->fixcolumn([$cost], '10', 0);


      $arr_brand = $this->reporter->fixcolumn([$brand], '15', 0);
      $arr_catname = $this->reporter->fixcolumn([$catname], '15', 0);

      $arr_sup_code = $this->reporter->fixcolumn([$sup_code], '25', 0);
      $arr_sup_name = $this->reporter->fixcolumn([$sup_name], '25', 0);
      $arr_cl_name = $this->reporter->fixcolumn([$cl_name], '19', 0);
      $arr_stockgrp = $this->reporter->fixcolumn([$stockgrp], '18', 0);
      $arr_isinactive = $this->reporter->fixcolumn([$isinactive], '10', 0);
      $arr_promoprice = $this->reporter->fixcolumn([$promoprice], '10', 0);
      $arr_promo = $this->reporter->fixcolumn([$promo], '25', 0);

      $maxrow = $this->othersClass->getmaxcolumn([
        $arr_barcode,
        $arr_itemname,
        $arr_price,
        $arr_price2,
        $arr_cost,
        $arr_brand,
        $arr_catname,
        $arr_sup_code,
        $arr_sup_name,
        $arr_cl_name,
        $arr_stockgrp,
        $arr_isinactive,
        $arr_promoprice,
        $arr_promo
      ]);

      if (strtoupper($part) == strtoupper($result[$i]->part)) {
        $part = "";
      } else {
        $part = $result[$i]->part;
      } //end if

      if (strtoupper($lbrand) == strtoupper($result[$i]->brand)) {
        $lbrand = "";
      } else {
        $lbrand = strtoupper($result[$i]->brand);
      } //end if
      if ($part != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, $part, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }
      if ($lbrand != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, $lbrand, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      PDF::SetFont($font, '', $fontsize);
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::MultiCell(45, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price[$r]) ? $arr_price[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price2[$r]) ?  $arr_price2[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(40, 0, ' ' . (isset($arr_catname[$r]) ? $arr_catname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(60, 0, ' ' . (isset($arr_sup_code[$r]) ? $arr_sup_code[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 0, ' ' . (isset($arr_sup_name[$r]) ? $arr_sup_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(60, 0, ' ' . (isset($arr_cl_name[$r]) ? $arr_cl_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(60, 0, ' ' . (isset($arr_stockgrp[$r]) ? $arr_stockgrp[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30, 0, ' ' . (isset($arr_isinactive[$r]) ? $arr_isinactive[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30, 0, ' ' . (isset($arr_promoprice[$r]) ? $arr_promoprice[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(65, 0, ' ' . (isset($arr_promo[$r]) ? $arr_promo[$r] : ''), '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }
      $lbrand = strtoupper($result[$i]->brand);
      $part = $result[$i]->part;
      if (PDF::getY() > 900) {
        $this->header_PDF($config, $result);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function homework_PDF_layout_pricegroup($config, $result)
  {

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "5";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->header_PDF($config, $result);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    $part = "";
    $lbrand = "";
    for ($i = 0; $i < count($result); $i++) {
      $maxrow = 1;

      $barcode = $result[$i]->barcode;
      $itemname = $result[$i]->itemname;
      $groupid = $result[$i]->groupid;
      $price = $result[$i]->price != 0 ? number_format($result[$i]->price, 2) : '-';
      $retail = $result[$i]->retail != 0 ? number_format($result[$i]->retail, 2) : '-';
      $wholesale = $result[$i]->wholesale != 0 ? number_format($result[$i]->wholesale, 2) : '-';
      $priceA = $result[$i]->priceA != 0 ? number_format($result[$i]->priceA, 2) : '-';
      $priceB = $result[$i]->priceB != 0 ? number_format($result[$i]->priceB, 2) : '-';

      $priceC = $result[$i]->priceC != 0 ? number_format($result[$i]->priceC, 2) : '-';
      $priceD = $result[$i]->priceD != 0 ? number_format($result[$i]->priceD, 2) : '-';
      $priceE = $result[$i]->priceE != 0 ? number_format($result[$i]->priceE, 2) : '-';
      $priceF = $result[$i]->priceF != 0 ? number_format($result[$i]->priceF, 2) : '-';
      $priceG = $result[$i]->priceG != 0 ? number_format($result[$i]->priceG, 2) : '-';

      if ($result[$i]->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
      $arr_groupid = $this->reporter->fixcolumn([$groupid], '24', 0);
      $arr_price = $this->reporter->fixcolumn([$price], '10', 0);
      $arr_isinactive = $this->reporter->fixcolumn([$isinactive], '10', 0);
      $arr_retail = $this->reporter->fixcolumn([$retail], '10', 0);
      $arr_wholesale = $this->reporter->fixcolumn([$wholesale], '15', 0);

      $arr_priceA = $this->reporter->fixcolumn([$priceA], '10', 0);
      $arr_priceB = $this->reporter->fixcolumn([$priceB], '10', 0);
      $arr_priceC = $this->reporter->fixcolumn([$priceC], '10', 0);
      $arr_priceD = $this->reporter->fixcolumn([$priceD], '10', 0);
      $arr_priceE = $this->reporter->fixcolumn([$priceE], '10', 0);
      $arr_priceF = $this->reporter->fixcolumn([$priceF], '10', 0);
      $arr_priceG = $this->reporter->fixcolumn([$priceG], '10', 0);

      $maxrow = $this->othersClass->getmaxcolumn([
        $arr_barcode,
        $arr_itemname,
        $arr_groupid,
        $arr_isinactive,
        $arr_price,
        $arr_retail,
        $arr_wholesale,
        $arr_priceA,
        $arr_priceB,
        $arr_priceC,
        $arr_priceE,
        $arr_priceD,
        $arr_priceF,
        $arr_priceG
      ]);

      if (strtoupper($part) == strtoupper($result[$i]->part)) {
        $part = "";
      } else {
        $part = $result[$i]->part;
      } //end if

      if (strtoupper($lbrand) == strtoupper($result[$i]->brand)) {
        $lbrand = "";
      } else {
        $lbrand = strtoupper($result[$i]->brand);
      } //end if
      if ($part != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, $part, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }
      if ($lbrand != "") {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, $lbrand, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      PDF::SetFont($font, '', $fontsize);
      for ($r = 0; $r < $maxrow; $r++) {

        PDF::MultiCell(55, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 0, ' ' . (isset($arr_groupid[$r]) ? $arr_groupid[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price[$r]) ?  $arr_price[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_isinactive[$r]) ? $arr_isinactive[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 0, ' ' . (isset($arr_retail[$r]) ? $arr_retail[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(65, 0, ' ' . (isset($arr_wholesale[$r]) ? $arr_wholesale[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceA[$r]) ? $arr_priceA[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceB[$r]) ? $arr_priceB[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceC[$r]) ? $arr_priceC[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceD[$r]) ? $arr_priceD[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceE[$r]) ? $arr_priceE[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceF[$r]) ? $arr_priceF[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_priceG[$r]) ? $arr_priceG[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }
      $lbrand = strtoupper($result[$i]->brand);
      $part = $result[$i]->part;
      if (PDF::getY() > 900) {
        $this->header_PDF($config, $result);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function test_homework_PDF_layout_item($config, $result)
  {

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "5";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->header_PDF($config, $result);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    $part = "";
    $lbrand = "";
    $cat = "";
    $class = "";
    $group = "";

    for ($i = 0; $i < count($result); $i++) {
      $maxrow = 1;

      $barcode = $result[$i]->barcode;
      $itemname = $result[$i]->itemname;
      $price = $result[$i]->price != 0 ? number_format($result[$i]->price, 2) : '-';
      $price2 = $result[$i]->price2 != 0 ? number_format($result[$i]->price2, 2) : '-';
      $cost = $result[$i]->cost != 0 ? number_format($result[$i]->cost, 2) : '-';


      if ($result[$i]->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '38', 0);
      $arr_price = $this->reporter->fixcolumn([$price], '10', 0);
      $arr_price2 = $this->reporter->fixcolumn([$price2], '10', 0);
      $arr_cost = $this->reporter->fixcolumn([$cost], '10', 0);
      $arr_isinactive = $this->reporter->fixcolumn([$isinactive], '10', 0);;

      $query = "
      select cat.name as catname,cl.clientname as sup_name,cl.client as sup_code,ifnull(itclass.cl_name,'') as cl_name,
      ifnull(stockgrp.stockgrp_name,'') as stockgrp,frontend_ebrands.brand_desc as brand from item
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = '" . $result[$i]->groupid . "'
      left join part_masterfile as parts on parts.part_id = '" . $result[$i]->partid . "'
      left join item_class as itclass on itclass.cl_id = '" . $result[$i]->classid . "'
      left join frontend_ebrands on frontend_ebrands.brandid = '" . $result[$i]->brandid . "'
      left join itemcategory as cat on cat.line = '" . $result[$i]->categoryid . "'
      left join client as cl on cl.clientid = '" . $result[$i]->supplierid . "'
      where itemid ='" . $result[$i]->itemid . "'";
      $otherinfo = $this->coreFunctions->opentable($query);


      $brand = $otherinfo[0]->brand;
      $catname = $otherinfo[0]->catname;
      $sup_code = $otherinfo[0]->sup_code;
      $sup_name = $otherinfo[0]->sup_name;
      $cl_name = $otherinfo[0]->cl_name;
      $stockgrp = $otherinfo[0]->stockgrp;

      $arr_brand = $this->reporter->fixcolumn([$brand], '15', 0);
      $arr_catname = $this->reporter->fixcolumn([$catname], '15', 0);

      $arr_sup_code = $this->reporter->fixcolumn([$sup_code], '25', 0);
      $arr_sup_name = $this->reporter->fixcolumn([$sup_name], '25', 0);
      $arr_cl_name = $this->reporter->fixcolumn([$cl_name], '19', 0);
      $arr_stockgrp = $this->reporter->fixcolumn([$stockgrp], '18', 0);

      $maxrow = $this->othersClass->getmaxcolumn([
        $arr_barcode,
        $arr_itemname,
        $arr_price,
        $arr_price2,
        $arr_cost,

        $arr_brand,
        $arr_catname,
        $arr_sup_code,
        $arr_sup_name,
        $arr_cl_name,
        $arr_stockgrp,
        $arr_isinactive
      ]);

      PDF::SetFont($font, '', $fontsize);
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::MultiCell(55, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price[$r]) ? $arr_price[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_price2[$r]) ?  $arr_price2[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(40, 0, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(45, 0, ' ' . (isset($arr_catname[$r]) ? $arr_catname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 0, ' ' . (isset($arr_sup_code[$r]) ? $arr_sup_code[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(130, 0, ' ' . (isset($arr_sup_name[$r]) ? $arr_sup_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 0, ' ' . (isset($arr_cl_name[$r]) ? $arr_cl_name[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

        PDF::MultiCell(70, 0, ' ' . (isset($arr_stockgrp[$r]) ? $arr_stockgrp[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30, 0, ' ' . (isset($arr_isinactive[$r]) ? $arr_isinactive[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }
      if (PDF::getY() > 900) {
        $this->header_PDF($config, $result);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function homework_layout_item_with_promo_test($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '8';
    $fontsize11 = 10;
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';

    $layoutsize = '1400';
    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '65px;margin-top:5px;');
    $str .= $this->header_default_homeworks($config);
    $str .= $this->homework_table_cols($border, $font, $fontsize11, $config);

    $part = "";
    $brand = "";
    // $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if
      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if
      $price2 = number_format($data->price2, 2);
      if ($price2 == 0) {
        $price2 = '-';
      } //end if
      $cost = number_format($data->avecost, 2);
      if ($cost == 0) {
        $cost = '-';
      } //end if
      $promoprice = number_format($data->amount, 2);
      if ($promoprice == 0) {
        $promoprice = '-';
      } //end if

      if ($part != "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '120', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      if ($brand != "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '120', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      if ($data->isinactive) {
        $isinactive = 'INACTIVE';
      } else {
        $isinactive = 'ACTIVE';
      } //end if

      if ($data->promo == null || $data->promo == '') {
        $promo = '-';
      } else {
        $promo = $data->promo;
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($price2, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($cost, '70', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->brand, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->catname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sup_code, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->sup_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cl_name, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->stockgrp, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($isinactive, '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($promoprice, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($promo, '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $brand = strtoupper($data->brand);
      $part = $data->part;

      // if ($this->reporter->linecounter == $page) {
      //   $str .= $this->reporter->page_break();
      //   $str .= $this->header_default_homeworks($config);
      //   $str .= $this->homework_table_cols($layoutsize, $border, $font, $fontsize11, $config);
      //   $page = $page + $count;
      // } //end if

    }
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }


    private function transpower_displayHeader($config)
  {
    $border = '1px solid';
    // $font = $this->companysetup->getrptfont($config['params']);
     $font = 'calibri';
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];

    if ($barcode == '') {
      $ritem = ' All';
    } else {
      $ritem = $barcode;
    }
    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }
    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }
    if ($classname == '') {
      $rclass = ' All';
    } else {
      $rclass = $classname;
    }


    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }


    $str = '';
    $str .= $this->reporter->begintable('2000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('2000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM LISTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('2000');
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item :' . $ritem, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $rgroup, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $rbrand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $rclass, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      }

      $str .= $this->reporter->pagenumber('Page', '100');
      $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }


  private function transpower_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('2000');





    $str .= $this->reporter->startrow(); 

    $str .= $this->reporter->col('', '57', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');


    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('START', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('END', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('REVERSE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('INACTIVE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(); 
    $str .= $this->reporter->col('ITEM', '57', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MAIN', '55', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SUB', '55', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('INVOICE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('INVOICE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('NET', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->col('BASE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BASE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WHOLESALE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WHOLESALE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('NET', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('NET', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISTRIBUTOR', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');


    $str .= $this->reporter->col('LOWEST', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LOWEST', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('NET', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DR', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DR', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('NET', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WIRE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WIRE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WIRE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WIRE', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM', '58', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(); 

    $str .= $this->reporter->col('CODE', '57', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('FULL ITEM NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CATEGORY', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CATEGORY', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DESCRIPTION1', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DESCRIPTION2', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('INVOICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->col('PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('WHOLESALE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISTRIBUTOR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');


    $str .= $this->reporter->col('PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LOWEST', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MINIMUM', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MAXIMUM', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MTR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MTR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TAG', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TAG', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TAG', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();


  


    // $str .= $this->reporter->startrow(); 

    // $str .= $this->reporter->col('ITEM CODE', '57', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('FULL ITEM NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('MAIN CATEGORY', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('SUB CATEGORY', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('BRAND', '55', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('DESCRIPTION 1', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('DESCRIPTION 2', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('INVOICE PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('INVOICE DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('NET INVOICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    // $str .= $this->reporter->col('BASE PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('BASE DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('WHOLESALE PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('WHOLESALE DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('NET WHOLESALE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('COST', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('COST DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('NET COST', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('DISTRIBUTOR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('DISTRIBUTOR DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');


    // $str .= $this->reporter->col('LOWEST PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('LOWEST DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('NET LOWEST', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('DR PRICE', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('DR DISC', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('NET DR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('MINIMUM', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('MAXIMUM', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('START WIRE MTR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('END WIRE MTR', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('ITEM WIRE TAG', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('REVERSE WIRE TAG', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    // $str .= $this->reporter->col('INACTIVE ITEM TAG', '58', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');


    // $str .= $this->reporter->endrow();

    return $str;
  }


   public function transpower_layout($config, $result)
  {
    $border = '1px solid';
    // $font = $this->companysetup->getrptfont($config['params']);
     $font = 'calibri';
    $font_size = '8';
    $fontsize11 = 9;
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $layoutsize = '2000';
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];

    $str = '';
 
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->transpower_displayHeader($config);
    $str .= $this->transpower_table_cols($layoutsize, $border, $font, $fontsize11, $config);

        // $part = "";
        // $brand = "";
        foreach ($result as $key => $data) {
          // if (strtoupper($part) == strtoupper($data->part)) {
          //   $part = "";
          // } else {
          //   $part = $data->part;
          // } //end if

          // if (strtoupper($brand) == strtoupper($data->brand)) {
          //   $brand = "";
          // } else {
          //   $brand = strtoupper($data->brand);
          // } //end if

          // $price = number_format($data->price, 2);
          // if ($price == 0) {
          //   $price = '-';
          // } //end if

          // if ($companyid != 28) { //not xcomp
          //   if ($part != "") {
          //     $str .= $this->reporter->startrow();
          //     $str .= $this->reporter->col($part, '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
          //     $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
          //     $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          //     $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          //     $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          //     $str .= $this->reporter->endrow();
          //   }
          //   if ($brand != "") {
          //     $str .= $this->reporter->startrow();
          //     $str .= $this->reporter->col($brand, '150', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
          //     $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
          //     $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          //     $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          //     $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          //     $str .= $this->reporter->endrow();
          //   }
          // }


          // $invoiceprice = number_format($data->invoiceprice, 2);
          // if ($invoiceprice == 0) {
          //   $invoiceprice = '-';
          // } //end if




          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          // if ($data->isinactive) {
          //   $isinactive = 'INACTIVE';
          // } else {
          //   $isinactive = 'ACTIVE';
          // } //end if

          $str .= $this->reporter->col($data->barcode, '57', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'LT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->maincat, '55', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->subcatname, '55', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->brand, '55', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '60', null, false, $border, '', 'CT', $font, $font_size, '', '', '');//desc1
          $str .= $this->reporter->col('', '60', null, false, $border, '', 'CT', $font, $font_size, '', '', '');//desc2
          $str .= $this->reporter->col($data->unit, '50', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->invoiceprice !=0 ? number_format($data->invoiceprice,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->invoicedisc !=0 ?  number_format($data->invoicedisc,0):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->netinvoice !=0 ? number_format($data->netinvoice,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->baseprice !=0 ? number_format($data->baseprice,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->basedisc !=0 ? number_format($data->basedisc,0):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->wholesaleprice !=0 ? number_format($data->wholesaleprice,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->wholesaledisc !=0 ? number_format($data->wholesaledisc,0):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->netwholesale !=0 ? number_format($data->netwholesale,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->cost != 0 ? number_format($data->cost,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');

          $str .= $this->reporter->col($data->costdisc != 0 ? number_format($data->costdisc,0):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->netcost !=0 ? number_format($data->netcost,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->distr !=0 ? number_format($data->distr,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');


          $str .= $this->reporter->col($data->distrdisc !=0 ? $data->distrdisc:'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');




          $str .= $this->reporter->col($data->lowestp !=0 ? number_format($data->lowestp,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->lowestdisc !=0 ? number_format($data->lowestdisc,0):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->netlow !=0 ? number_format($data->netlow,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->drp !=0 ? number_format($data->drp,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->drdisc !=0 ? number_format($data->drdisc,0):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->netdr !=0 ? number_format($data->netdr,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->minimum !=0 ? number_format($data->minimum,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->maximum !=0 ? number_format($data->maximum,2):'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
               
          $str .= $this->reporter->col($data->startwire !=0 ? $data->startwire:'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->endwire !=0 ? $data->endwire:'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->itemwiretag !=0 ? $data->itemwiretag:'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->reversewiretag !=0 ? $data->reversewiretag:'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->inactiveitemtag !=0 ? $data->inactiveitemtag:'-', '58', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
                              

          // $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          // $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
          // $str .= $this->reporter->col($data->groupid, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          // $str .= $this->reporter->col($price, '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          // $str .= $this->reporter->col($isinactive, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

          // $brand = strtoupper($data->brand);
          // $part = $data->part;

          $str .= $this->reporter->endrow();

          // if ($companyid != 28) { //not xcomp
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->page_break();

              $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
              if (!$allowfirstpage) {
                $str .= $this->transpower_displayHeader($config);
              }
              $str .= $this->transpower_table_cols($layoutsize, $border, $font, $fontsize11, $config);
              $str .= $this->reporter->addline();
              $page = $page + $count;
            } //end if
        }
        // break;
    // }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }




  public function reportdatacsv($config)
  {
    ini_set('max_execution_time', 0);

    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $clientid = $config['params']['dataparams']['clientid'];
    $isassettag = $config['params']['dataparams']['isassettag'];

    $filter = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($clientid != 0) {
      $filter .= " and item.supplier='$clientid'";
    }

    $current = $this->othersClass->getCurrentDate();
    $add_fields = "";

    if ($isassettag == '1') {
      $filter .= " and item.isfa=1";
    } else {
      if ($isassettag == '2') {
        $filter .= " and item.isfa=0";
      }
    }
    $current = $this->othersClass->getCurrentDate();

    // $add_fields = ",
    // (select  concat(date_format(startdate,'%m-%d-%y'), ' / ', date_format(enddate,'%m-%d-%y')) as promo
    // from pricelist
    // where '" . $current . "' between date(startdate) and date(enddate)
    // and clientid = 0 and itemid = item.itemid limit 1) as `PROMO PERIOD DATE`,
    // (select  format(amount,2) as price
    // from pricelist
    // where '" . $current . "' between date(startdate) and date(enddate)
    // and clientid = 0 and itemid = item.itemid limit 1) as `PROMO PRICE`";
    if ($companyid == 60) { //transpower
      $order = " order by cat.name,itclass.cl_name,frontend_ebrands.brand_desc,itemname";
      $query = "select item.barcode as `ITEM_CODE`, item.itemname as `FULL_ITEM_NAME`, cat.name as `MAIN_CATEGORY`, subcat.name as `SUB_CATEGORY`, frontend_ebrands.brand_desc as `BRAND`,
        itclass.cl_name as `DESCRIPTION_1`, mm.model_name as `DESCRIPTION_2`, item.uom as `UNIT`, format(item.amt5,2) as `INVOICE_PRICE`, format(item.disc5,2) as `INVOICE_DISC`,
        format(item.namt5,2) as `NET_INVOICE`, format(item.amt,2) as `BASE_PRICE`, format(item.disc,2) as `BASE_DISC`, format(item.amt2,2) as `WHOLESALE_PRICE`,
        format(item.disc2,2) as `WHOLESALE_DISC`, format(item.namt2,2) as `NET_WHOLESALE`, format(item.amt4,2) as `COST`, format(item.disc4,2) as `COST_DISC`,
        format(item.namt4,2) as `NET_COST`, format(item.famt,2) as `DISTRIBUTOR`, format(item.disc3,2) as `DISTRIBUTOR_DISC`, format(item.nfamt,2) as `NET_DISTRIBUTOR`,
        format(item.amt6,2) as `LOWEST_PRICE`, format(item.disc6,2) as `LOWEST_DISC`, format(item.namt6,2) as `NET_LOWEST`, format(item.amt7,2) as `DR_PRICE`,
        format(item.disc7,2) as `DR_DISC`, format(item.namt7,2) as `NET_DR`, format(item.minimum,2) as `MINIMUM`, format(item.maximum,2) as `MAXIMUM`,
        format(item.startwire,2) as `START_WIRE_MTR`, format(item.endwire,2) as `END_WIRE_MTR`, item.iswireitem as `ITEM_WIRE_TAG`, item.isreversewireitem as `REVERSE_WIRE_TAG`,
        item.isinactive as `INACTIVE_ITEM_TAG`
        from item
          left join itemcategory as cat on cat.line=item.category
          left join itemsubcategory as subcat on subcat.line=item.subcat
          left join frontend_ebrands as frontend_ebrands on frontend_ebrands.brandid=item.brand
          left join item_class as itclass on itclass.cl_id=item.class
          left join model_masterfile as mm on mm.model_id=item.model
        where item.barcode<>'' and item.isinactive in $itemstatus
          and item.isimport in $itemtype $filter and item.isofficesupplies=0 $order";
    } else {
      $order = " order by cat.name,itclass.cl_name,stockgrp.stockgrp_name,ifnull(parts.part_name,''),frontend_ebrands.brand_desc,itemname";
      $add_fields = ",(select concat(date_format(head.dateid,'%m-%d-%y'), ' / ', date_format(head.due,'%m-%d-%y')) as promo from 
      hpahead as head
      left join hpastock as stock on stock.trno = head.trno
      where stock.itemid = item.itemid and '" . $current . "'  between date(head.dateid) and date(head.due) limit 1) as`PROMO PERIOD DATE`
      ,(select format(stock.ext,2) from 
      hpahead as head
      left join hpastock as stock on stock.trno = head.trno
      where stock.itemid = item.itemid and '" . $current . "'  between date(head.dateid) and date(head.due) limit 1) as `PROMO PRICE`";


      $query = "
      select item.barcode as `ITEM CODE`, item.itemname as `ITEM DESCRIPTION`,format(item.amt,2) as `SRP 1`,format(item.amt2,2) as `SRP 2`,format(item.avecost,2) as `COST`,
      frontend_ebrands.brand_desc as `BRAND`,cat.name as `CATEGORY`,cl.client as `SUPPLIER CODE`,cl.clientname as `SUPPLIER NAME`,
      ifnull(itclass.cl_name,'') as `CLASS`,ifnull(stockgrp.stockgrp_name,'') as `SUBCLASS`, if(item.isinactive = 1,'INACTIVE','ACTIVE') as `STATUS`
      $add_fields
      from item 
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join item_class as itclass on item.class = itclass.cl_id
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join itemcategory as cat on cat.line = item.category
      left join client as cl on cl.clientid = item.supplier
  	  where item.barcode <> '' and item.isinactive in $itemstatus 
  	  and item.isimport in $itemtype $filter  and item.isofficesupplies=0 $order";
    }

    $data = $this->coreFunctions->opentable($query);
    return ['status' => true, 'msg' => 'Generating CSV successfully', 'data' => $data, 'params' => $this->reportParams, 'name' => 'ItemList'];
  }
}//end class