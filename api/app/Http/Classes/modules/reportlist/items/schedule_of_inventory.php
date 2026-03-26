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

class schedule_of_inventory
{
  public $modulename = 'Schedule of Inventory';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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

    $fields = ['radioprint', 'start', 'itemname', 'divsion', 'class', 'dwhname'];
    switch ($companyid) {
      case 39: //cbbsi
        array_push($fields, 'pricegroup');
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'itemname.label', 'Item Code: A first few letters of the item');
        data_set($col1, 'itemname.readonly', false);
        data_set($col1, 'dwhname.label', 'Warehouse 1');
        data_set($col1, 'pricegroup.lookupclass', 'lookuppricetype');
        data_set($col1, 'pricegroup.name', 'pricetype');
        data_set($col1, 'pricegroup.required', true);
        break;
    }

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 39: //cbbsi
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        '' as end,
        '' as shortcode,
        '' as barcode,
        0 as itemid,
        '' as itemname,

        0 as classid,
        '' as classic,
        '' as class,
        
        0 as whid,
        '' as wh,
        '' as whname,
        '' as dwhname,

        0 as groupid,
        '' as stockgrp,
        '' as divsion,

        0 clientid,
        '' as client,
        '' as clientname,
        '' as pricetype";
        break;
    }

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $company = $config['params']['companyid'];

    switch ($company) {
      case 39: //cbbsi
        $result = $this->CBBSI_Layout($config);
        break;

      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }
    return $result;
  }

  public function CBBSI_QRY($config)
  {
    // QUERY
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $itemname    = $config['params']['dataparams']['itemname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $classname  = $config['params']['dataparams']['classic'];
    $wh         = $config['params']['dataparams']['wh'];
    $pricetype    = $config['params']['dataparams']['pricetype'];
    $filter = " and item.isimport in (0,1)";

    if ($itemname != "") {
      $filter .= " and left(item.barcode," . strlen($itemname) . ") like '%" . $itemname . "%'";
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    $pricefieldamt = '';
    if ($pricetype != '') {
      $pricefield = $this->othersClass->getamtfieldbygrp($pricetype);
      $pricefieldamt .= 'item.' . $pricefield['amt'];
    }

    $query = "
    select a.whcode,a.whname,a.barcode,a.itemname,a.uom,sum(a.qty-a.iss) as qtybal,a.cost,sum(a.ext) as ext from(
      select
        item.barcode, item.itemname,
        item.uom,
        stock.qty, stock.iss,
        ifnull($pricefieldamt,0) as cost,
        stock.ext,wh.client as whcode,wh.clientname as whname
    
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=head.whid
        left join item on item.itemid=stock.itemid
        where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter
    
        UNION ALL
    
        select
        item.barcode, item.itemname,
        item.uom,
        stock.qty, stock.iss,
        ifnull($pricefieldamt,0) as cost,
        stock.ext,wh.client as whcode,wh.clientname as whname
    
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.client=head.wh
        left join item on item.itemid=stock.itemid
        where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter
    ) as a
    group by a.whcode,a.whname,a.barcode,a.itemname,a.uom,a.cost
    order by barcode";

    return $this->coreFunctions->opentable($query);
  }

  private function CBBSI_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $center    = $config['params']['center'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $itemname    = $config['params']['dataparams']['itemname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $classname  = $config['params']['dataparams']['classic'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $clientname  = $config['params']['dataparams']['clientname'];
    $pricetype    = $config['params']['dataparams']['pricetype'];

    if ($pricetype != '') {
      $pricefield = $this->othersClass->getamtfieldbygrp($pricetype);
      $pricefieldlabel = $pricefield['label'];
    }
    if ($clientname == "") {
      $clientname = 'ALL';
    }


    if ($itemname == "") {
      $itemname = 'ALL';
    }

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SCHEDULE OF INVENTORY', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $division = !empty($groupname) ? $groupname : "ALL";
    $class    = !empty($classname) ? $classname : "ALL";
    // $item     = !empty($barcode) ? $barcode : "ALL";
    $wh   = !empty($wh) ? $wh : "ALL";
    $price = !empty($pricefieldlabel) ? $pricefieldlabel : "ALL";
    $asof     = !empty($asof) ? $asof : "";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item: ' . $itemname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group : ' . $division, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class : ' . $class, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse : ' . $whname . ' ~ ' . $wh, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Price Type : ' . $price, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '300', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function CBBSI_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';

    $result = $this->CBBSI_QRY($config);
    $clientname  = $config['params']['dataparams']['clientname'];
    $companyid = $config['params']['companyid'];
    $pricetype    = $config['params']['dataparams']['pricetype'];

    if ($pricetype != '') {
      $pricefield = $this->othersClass->getamtfieldbygrp($pricetype);
      $pricefieldlabel = $pricefield['label'];
    }
    if ($clientname == "") {
      $clientname = 'ALL';
    }

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->CBBSI_displayHeader($config);
    $grandtotal = 0;
    foreach ($result as $key => $data) {

      $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qtybal, $this->companysetup->getdecimal('qty', $config['params'])), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, $this->companysetup->getdecimal('price', $config['params'])), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qtybal * $data->cost, $this->companysetup->getdecimal('price', $config['params'])), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $grandtotal += $data->ext;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->CBBSI_displayHeader($config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('TOTAL: ', '150', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, $this->companysetup->getdecimal('price', $config['params'])), '150', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefault($config)
  {
    // QUERY
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $companyid = $config['params']['companyid'];

    $filter = " and item.isimport in (0,1)";
    $filter1 = "";

    if ($barcode != "") {
      $filter .= " and item.barcode='$barcode'";
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat =  $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($modelname != "") {
      $modelid = $config['params']['dataparams']['modelid'];
      $filter .= " and item.model=" . $modelid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }

    if ($companyid == 10 || $companyid == 12) { // afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    }

    $filter3 = ($companyid == 14) ? 'minimum' : 'critical';

    $query = "select barcode, itemname,ib.uom,cost,sum(qty-iss) as balance,ib.critical,ib.minimum,ib.maximum
       from (
        select
            cntnum.center, item.critical,
            item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
            ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
            item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
            wh.clientname as whname, stock.qty, stock.iss,
            ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost, item.amt,
            cat.name as category1, subcat.name as subcatname
            FROM glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid          
            left join model_masterfile as modelgrp on modelgrp.model_id = item.model
            left join part_masterfile as partgrp on partgrp.part_id = item.part
            left join client as wh on wh.clientid=stock.whid
            left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
            left join cntnum on cntnum.trno=head.trno
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
            where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1  and item.isofficesupplies=0
          
            UNION ALL
          
            select cntnum.center, item.critical,
            item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
            ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
            item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
            wh.clientname as whname, stock.qty, stock.iss,
            ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,item.amt,
            cat.name as category1,subcat.name as subcatname

            FROM lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join model_masterfile as modelgrp on modelgrp.model_id = item.model
            left join part_masterfile as partgrp on partgrp.part_id = item.part
            left join client as wh on wh.clientid=stock.whid
            left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
            left join cntnum on cntnum.trno=head.trno
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat 
            where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
          ) as ib
          left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
          group by barcode, itemname,ib.uom,cost,ib.critical,ib.minimum,ib.maximum
          having (case when sum(qty-iss) <= " . $filter3 . " then 1 else 0 end) in (1)
          order by itemname";

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('REORDER REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $division = !empty($groupname) ? $groupname : "ALL";
    $brand    = !empty($brandname) ? $brandname : "ALL";
    $class    = !empty($classname) ? $classname : "ALL";
    $model    = !empty($modelname) ? $modelname : "ALL";
    $item     = !empty($barcode) ? $barcode : "ALL";
    $whcode   = !empty($wh) ? $wh : "ALL";
    $asof     = !empty($asof) ? $asof : "";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code: ' . $item, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Division : ' . $division, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand : ' . $brand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('Generic : ' . $model, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Model : ' . $model, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->col('Class : ' . $class, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse : ' . $whname . ' ~ ' . $whcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As Of : ' . $asof, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }

    if ($companyid == 14) { //majesty
      if ($subcatname == '') {
        $str .= $this->reporter->col('Principal: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Principal : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
    } else {
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEMNAME', '200', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '75', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LQTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    if ($companyid != 14) { //not majesty
      $str .= $this->reporter->col('CRITICAL', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    }
    $str .= $this->reporter->col('MIN', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PENDING ORDER', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LAST PO DATE', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];

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

    foreach ($result as $key => $data) {
      $barcode = $data->barcode;
      $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '$barcode'");
      $query = "select sum(qty - qa) as pending, sum(qa) as served from hpostock where itemid = $itemid";
      $result1 = $this->coreFunctions->opentable($query);;
      $data->pending = $result1[0]->pending;
      $data->served = $result1[0]->served;

      $query1 = "
      select head.trno, head.dateid as lpodate, stock.qty as lpoqty
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      where itemid = $itemid and head.doc = 'RR'
      order by head.dateid desc, docno
      limit 1
    ";
      $result2 = $this->coreFunctions->opentable($query1);

      if ($data->balance <= $data->minimum || $data->balance == 0) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($data->balance, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');

        foreach ($result2 as $key => $data1) {
          $data->trno = $data1->trno;

          if (isset($data1->lpodate)) {
            $lpodate = $data1->lpodate;
          } else {
            $lpodate = '';
          }

          if (isset($data1->lpoqty)) {
            $lpoqty = $data1->lpoqty;
          } else {
            $lpoqty = 0;
          }
        }

        if (empty($result2)) {
          $lpodate = '';
          $lpoqty = 0;
        }

        $str .= $this->reporter->col(number_format($lpoqty, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        if ($companyid != 14) { //not majesty
          $str .= $this->reporter->col(number_format(intval($data->critical), 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        }

        $str .= $this->reporter->col(number_format($data->minimum, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($data->maximum, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($data->pending, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        if ($lpodate != '') {
          $str .= $this->reporter->col(date('m/d/Y', strtotime($lpodate)), '75', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        } else {
          $str .= $this->reporter->col($lpodate, '75', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        }


        $str .= $this->reporter->endrow();
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class