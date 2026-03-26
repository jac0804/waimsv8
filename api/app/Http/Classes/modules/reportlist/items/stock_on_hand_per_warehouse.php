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

class stock_on_hand_per_warehouse
{
  public $modulename = 'Stock On Hand Per Warehouse';
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
    $fields = ['radioprint', 'start', 'itemname', 'divsion', 'class', 'whfilter1', 'whfilter2', 'whfilter3', 'whfilter4', 'radioreportitemtype'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'itemname.label', 'Item Code: A first few letters of the item');
    data_set($col1, 'itemname.readonly', false);
    data_set($col1, 'whfilter1.required', true);

    unset($col1['class']['labeldata']);
    unset($col1['divsion']['labeldata']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['divsion']);
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'divsion.name', 'stockgrp');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      '' as end,

      '' as ditemname,
      '' as shortcode,
      '' as barcode,
      0 as itemid,
      '' as itemname,

      0 as classid,
      '' as classic,
      '' as class,
      
      '' as whfilter1,
      '' as whcode1,
      '' as whname1,

      '' as whfilter2,
      '' as whcode2,
      '' as whname2,

      '' as whfilter3,
      '' as whcode3,
      '' as whname3,

      '' as whfilter4,
      '' as whcode4,
      '' as whname4,
      
      0 as groupid,
      '' as stockgrp,
      '' as divsion,

      '(0)' as itemtype";

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
    $result = $this->CBBSI_Layout($config);
    return $result;
  }

  public function CBBSI_MAINQRY($asof, $filter, $whcode, $whfilter)
  {
    $fields = '';
    switch ($whfilter) {
      case '1':
        $fields = "
        stock.qty-stock.iss as bal1,
        0 as bal2,
        0 as bal3,
        0 as bal4,
        ";
        break;
      case '2':
        $fields = "
        0 as bal1,
        stock.qty-stock.iss as bal2,
        0 as bal3,
        0 as bal4,
        ";
        break;
      case '3':
        $fields = "
        0 as bal1,
        0 as bal2,
        stock.qty-stock.iss as bal3,
        0 as bal4,
        ";
        break;
      case '4':
        $fields = "
        0 as bal1,
        0 as bal2,
        0 as bal3,
        stock.qty-stock.iss as bal4,
        ";
        break;
    }

    $qry = "
    select item.barcode, item.itemname, item.uom, $fields ifnull(item.amt9,0) as cost,
    stock.ext,wh.client as whcode,wh.clientname as whname
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as wh on wh.clientid=head.whid
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join item_class as classgrp on classgrp.cl_id = item.class
    where head.dateid<='$asof' and wh.client = '$whcode'
    and ifnull(item.barcode,'')<>'' 
    $filter

    UNION ALL

    select item.barcode, item.itemname, item.uom, $fields ifnull(item.amt9,0) as cost,
    stock.ext,wh.client as whcode,wh.clientname as whname
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as wh on wh.client=head.wh
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join item_class as classgrp on classgrp.cl_id = item.class
    where head.dateid<='$asof' and wh.client = '$whcode' and ifnull(item.barcode,'')<>'' 
    $filter";

    return $qry;
  }

  public function CBBSI_QRY($config)
  {
    // QUERY
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $itemname    = $config['params']['dataparams']['itemname'];
    $stockgrp    = $config['params']['dataparams']['stockgrp'];
    $classname    = $config['params']['dataparams']['classic'];

    $whcode1     = $config['params']['dataparams']['whcode1'];
    $whcode2     = $config['params']['dataparams']['whcode2'];
    $whcode3     = $config['params']['dataparams']['whcode3'];
    $whcode4     = $config['params']['dataparams']['whcode4'];

    $itemtype   = $config['params']['dataparams']['itemtype'];
    $filter = " and item.isimport in $itemtype";

    if ($itemname != "") {
      $filter = $filter . " and left(item.barcode," . strlen($itemname) . ") like '%" . $itemname . "%'";
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter = $filter . " and item.groupid=" . $groupid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }

    $addQry = '';
    $whfilter = "";
    $test = '';
    if ($whcode2 != '') {
      $whfilter = "2";
      $addQry = $addQry . " union all " . $this->CBBSI_MAINQRY($asof, $filter, $whcode2, $whfilter);
      $test .= "wh 2";
    }
    if ($whcode3 != '') {
      $whfilter = "3";
      $addQry = $addQry . " union all " . $this->CBBSI_MAINQRY($asof, $filter, $whcode3, $whfilter);
      $test .= "wh 3";
    }
    if ($whcode4 != '') {
      $whfilter = "4";
      $addQry = $addQry . " union all " . $this->CBBSI_MAINQRY($asof, $filter, $whcode4, $whfilter);
      $test .= "wh 4";
    }

    $whfilter = "1";
    $query = "
      select a.barcode,a.itemname,a.uom,
      sum(a.bal1) as bal1,
      sum(a.bal2) as bal2,
      sum(a.bal3) as bal3,
      sum(a.bal4) as bal4,
      a.cost from(
        " . $this->CBBSI_MAINQRY($asof, $filter, $whcode1, $whfilter) . "

        " . $addQry . "
      ) as a
      group by a.barcode,a.itemname,a.uom,a.cost
    
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

    $whname1     = $config['params']['dataparams']['whname1'];
    $whname2     = $config['params']['dataparams']['whname2'];
    $whname3     = $config['params']['dataparams']['whname3'];
    $whname4     = $config['params']['dataparams']['whname4'];

    $wh1label = '';
    $wh2label = '';
    $wh3label = '';
    $wh4label = '';

    if ($whname1 == '') {
      $wh1label = 'WHSE1';
    } else {
      $wh1label = $whname1;
    }
    if ($whname2 == '') {
      $wh2label = 'WHSE2';
    } else {
      $wh2label = $whname2;
    }
    if ($whname3 == '') {
      $wh3label = 'WHSE3';
    } else {
      $wh3label = $whname3;
    }
    if ($whname4 == '') {
      $wh4label = 'WHSE4';
    } else {
      $wh4label = $whname4;
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
    $str .= $this->reporter->col('STOCK ON HAND PER WAREHOUSE', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $division = !empty($groupname) ? $groupname : "ALL";
    $class    = !empty($classname) ? $classname : "ALL";
    $item     = !empty($barcode) ? $barcode : "ALL";
    $asof     = !empty($asof) ? $asof : "";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code: ' . $itemname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group : ' . $division, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class : ' . $class, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '250', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col($wh1label, '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col($wh2label, '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col($wh3label, '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col($wh4label, '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');


    $str .= $this->reporter->endrow();

    return $str;
  }

  public function CBBSI_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';

    $result = $this->CBBSI_QRY($config);
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
    $str .= $this->CBBSI_displayHeader($config);
    // $grandtotal = 0;

    foreach ($result as $key => $data) {

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cost, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal1, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal2, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal3, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal4, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->CBBSI_displayHeader($config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  // public function reportDefault($config)
  // {
  //   // QUERY
  //   $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
  //   $classname    = $config['params']['dataparams']['classic'];
  //   $brandname    = $config['params']['dataparams']['brandname'];
  //   $stockgrp    = $config['params']['dataparams']['stockgrp'];
  //   $categoryname  = $config['params']['dataparams']['categoryname'];
  //   $subcatname =  $config['params']['dataparams']['subcatname'];
  //   $modelname    = $config['params']['dataparams']['modelname'];
  //   $barcode    = $config['params']['dataparams']['barcode'];
  //   $wh         = $config['params']['dataparams']['wh'];
  //   $companyid = $config['params']['companyid'];

  //   $filter = " and item.isimport in (0,1)";
  //   $filter1 = "";
  //   if ($barcode != "") {
  //     $itemid = $config['params']['dataparams']['itemid'];
  //     $filter .= " and stock.itemid=" . $itemid;
  //   }
  //   if ($stockgrp != "") {
  //     $groupid = $config['params']['dataparams']['groupid'];
  //     $filter .= " and item.groupid=" . $groupid;
  //   }
  //   if ($categoryname != "") {
  //     $category = $config['params']['dataparams']['category'];
  //     $filter .= " and item.category='$category'";
  //   }
  //   if ($subcatname != "") {
  //     $subcat = $config['params']['dataparams']['subcat'];
  //     $filter .= " and item.subcat='$subcat'";
  //   }
  //   if ($wh != "") {
  //     $whid = $config['params']['dataparams']['whid'];
  //     $filter .= " and stock.whid=" . $whid;
  //   }
  //   if ($brandname != "") {
  //     $brandid = $config['params']['dataparams']['brandid'];
  //     $filter .= " and item.brand=" . $brandid;
  //   }
  //   if ($modelname != "") {
  //     $modelid = $config['params']['dataparams']['modelid'];
  //     $filter .= " and item.model=" . $modelid;
  //   }
  //   if ($classname != "") {
  //     $classid = $config['params']['dataparams']['classid'];
  //     $filter .= " and item.class=" . $classid;
  //   }
  //   if ($categoryname != "") {
  //     $filter .= " and item.category='$categoryname'";
  //   }

  //   if ($companyid == 10 || $companyid == 12) { //afti, afti usd
  //     $project = $config['params']['dataparams']['project'];
  //     $deptname = $config['params']['dataparams']['ddeptname'];

  //     if ($project != "") {
  //       $projectid = $config['params']['dataparams']['projectid'];
  //       $filter1 .= " and item.projectid=" . $projectid;
  //     }
  //     if ($deptname != "") {
  //       $deptid = $config['params']['dataparams']['deptid'];
  //       $filter1 .= " and head.deptid=" . $deptid;
  //     }
  //   } else {
  //     $filter1 .= "";
  //   }

  //   $filter3 = 'critical';
  //   if ($companyid == 14) { //majesty
  //     $filter3 = 'minimum';
  //   }

  //   $query = "select barcode, itemname,ib.uom,cost,sum(qty-iss) as balance,ib.critical,ib.minimum,ib.maximum
  //      from (
  //       select
  //         cntnum.center, item.critical,
  //         item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
  //         ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
  //         item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
  //         wh.clientname as whname, stock.qty, stock.iss,
  //         ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost, item.amt,
  //         cat.name as category1, subcat.name as subcatname

  //         from glhead as head
  //         left join glstock as stock on stock.trno=head.trno
  //         left join item on item.itemid=stock.itemid          
  //         left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
  //         left join model_masterfile as modelgrp on modelgrp.model_id = item.model
  //         left join part_masterfile as partgrp on partgrp.part_id = item.part
  //         left join client as wh on wh.clientid=stock.whid
  //         left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
  //         left join cntnum on cntnum.trno=head.trno
  //         left join itemcategory as cat on cat.line = item.category
  //         left join itemsubcategory as subcat on subcat.line = item.subcat



  //         where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1  and item.isofficesupplies=0

  //         union all

  //         select cntnum.center, item.critical,
  //           item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
  //           ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
  //           item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
  //           wh.clientname as whname, stock.qty, stock.iss,
  //           ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,item.amt,
  //           cat.name as category1,subcat.name as subcatname

  //           from lahead as head
  //           left join lastock as stock on stock.trno=head.trno
  //           left join item on item.itemid=stock.itemid
  //           left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
  //           left join model_masterfile as modelgrp on modelgrp.model_id = item.model
  //           left join part_masterfile as partgrp on partgrp.part_id = item.part
  //           left join client as wh on wh.clientid=stock.whid
  //           left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
  //           left join cntnum on cntnum.trno=head.trno
  //           left join itemcategory as cat on cat.line = item.category
  //           left join itemsubcategory as subcat on subcat.line = item.subcat


  //           where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
  //         ) as ib
  //         left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
  //         group by barcode, itemname,ib.uom,cost,ib.critical,ib.minimum,ib.maximum
  //         having (case when sum(qty-iss) <= " . $filter3 . " then 1 else 0 end) in (1)
  //         order by itemname";

  //   return $this->coreFunctions->opentable($query);
  // }

  // private function default_displayHeader($config)
  // {
  //   $border = '1px solid';
  //   $font = $this->companysetup->getrptfont($config['params']);
  //   $font_size = '10';
  //   $padding = '';
  //   $margin = '';

  //   $center     = $config['params']['center'];
  //   $username   = $config['params']['user'];
  //   $companyid = $config['params']['companyid'];

  //   $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
  //   $classname  = $config['params']['dataparams']['classic'];
  //   $brandname  = $config['params']['dataparams']['brandname'];
  //   $categoryname  = $config['params']['dataparams']['categoryname'];
  //   $subcatname =  $config['params']['dataparams']['subcat'];
  //   $groupname  = $config['params']['dataparams']['stockgrp'];
  //   $modelname  = $config['params']['dataparams']['modelname'];
  //   $categoryname  = $config['params']['dataparams']['categoryname'];
  //   $barcode    = $config['params']['dataparams']['barcode'];
  //   $wh         = $config['params']['dataparams']['wh'];
  //   $whname     = $config['params']['dataparams']['whname'];

  //   if ($companyid == 10 || $companyid == 12) { //afti, afti usd
  //     $dept   = $config['params']['dataparams']['ddeptname'];
  //     $proj   = $config['params']['dataparams']['project'];
  //     if ($dept != "") {
  //       $deptname = $config['params']['dataparams']['deptname'];
  //     } else {
  //       $deptname = "ALL";
  //     }
  //     if ($proj != "") {
  //       $projname = $config['params']['dataparams']['projectname'];
  //     } else {
  //       $projname = "ALL";
  //     }
  //   }

  //   $str = '';
  //   $layoutsize = '1000';

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->letterhead($center, $username, $config);
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();


  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('REORDER REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br>';

  //   $division = !empty($groupname) ? $groupname : "ALL";
  //   $brand    = !empty($brandname) ? $brandname : "ALL";
  //   $class    = !empty($classname) ? $classname : "ALL";
  //   $model    = !empty($modelname) ? $modelname : "ALL";
  //   $item     = !empty($barcode) ? $barcode : "ALL";
  //   $whcode   = !empty($wh) ? $wh : "ALL";
  //   $asof     = !empty($asof) ? $asof : "";

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Item Code: ' . $item, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //   $str .= $this->reporter->col('Division : ' . $division, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //   $str .= $this->reporter->col('Brand : ' . $brand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //   if ($companyid == 14) { //majesty
  //     $str .= $this->reporter->col('Generic : ' . $model, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
  //   } else {
  //     $str .= $this->reporter->col('Model : ' . $model, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
  //   }
  //   $str .= $this->reporter->col('Class : ' . $class, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();

  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Warehouse : ' . $whname . ' ~ ' . $whcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //   if ($companyid == 10 || $companyid == 12) { //afti, afti usd
  //     $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //     $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //   }
  //   $str .= $this->reporter->endrow();

  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('As Of : ' . $asof, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
  //   if ($categoryname == '') {
  //     $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
  //   } else {
  //     $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
  //   }

  //   if ($companyid == 14) { //majesty
  //     if ($subcatname == '') {
  //       $str .= $this->reporter->col('Principal: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
  //     } else {
  //       $subcatname =  $config['params']['dataparams']['subcatname'];
  //       $str .= $this->reporter->col('Principal : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
  //     }
  //   } else {
  //     if ($subcatname == '') {
  //       $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
  //     } else {
  //       $subcatname =  $config['params']['dataparams']['subcatname'];
  //       $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
  //     }
  //   }

  //   $str .= $this->reporter->endrow();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();

  //   $str .= $this->reporter->begintable($layoutsize);
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('ITEMNAME', '200', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('COST', '75', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('LQTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   if ($companyid != 14) { //not majesty
  //     $str .= $this->reporter->col('CRITICAL', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   }
  //   $str .= $this->reporter->col('MIN', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('MAX', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('PENDING ORDER', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->col('LAST PO DATE', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
  //   $str .= $this->reporter->endrow();

  //   return $str;
  // }

  // public function reportDefaultLayout($config)
  // {
  //   $border = '1px solid';
  //   $font = $this->companysetup->getrptfont($config['params']);
  //   $font_size = '10';

  //   $result = $this->reportDefault($config);
  //   $companyid  = $config['params']['companyid'];
  //   $barcode    = $config['params']['dataparams']['barcode'];

  //   $count = 61;
  //   $page = 60;
  //   $this->reporter->linecounter = 0;

  //   if (empty($result)) {
  //     return $this->othersClass->emptydata($config);
  //   }

  //   $str = '';
  //   $layoutsize = '1000';
  //   $str .= $this->reporter->beginreport($layoutsize);
  //   $str .= $this->default_displayHeader($config);

  //   foreach ($result as $key => $data) {
  //     $barcode = $data->barcode;
  //     $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '$barcode'");
  //     $query = "select sum(qty - qa) as pending, sum(qa) as served from hpostock where itemid = $itemid";
  //     $result1 = $this->coreFunctions->opentable($query);;
  //     $data->pending = $result1[0]->pending;
  //     $data->served = $result1[0]->served;

  //     $query1 = "
  //     select head.trno, head.dateid as lpodate, stock.qty as lpoqty
  //     from glhead as head
  //     left join glstock as stock on stock.trno = head.trno
  //     where itemid = $itemid and head.doc = 'RR'
  //     order by head.dateid desc, docno
  //     limit 1
  //   ";
  //     $result2 = $this->coreFunctions->opentable($query1);

  //     if ($data->balance <= $data->minimum || $data->balance == 0) {
  //       $str .= $this->reporter->addline();
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
  //       $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
  //       $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
  //       $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
  //       $str .= $this->reporter->col(number_format($data->balance, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');

  //       foreach ($result2 as $key => $data1) {
  //         $data->trno = $data1->trno;


  //         if (isset($data1->lpodate)) {
  //           $lpodate = $data1->lpodate;
  //         } else {
  //           $lpodate = '';
  //         }

  //         if (isset($data1->lpoqty)) {
  //           $lpoqty = $data1->lpoqty;
  //         } else {
  //           $lpoqty = 0;
  //         }
  //       }

  //       if (empty($result2)) {
  //         $lpodate = '';
  //         $lpoqty = 0;
  //       }

  //       $str .= $this->reporter->col(number_format($lpoqty, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
  //       if ($companyid != 14) { //not majesty
  //         $str .= $this->reporter->col(number_format(intval($data->critical), 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
  //       }

  //       $str .= $this->reporter->col(number_format($data->minimum, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
  //       $str .= $this->reporter->col(number_format($data->maximum, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
  //       $str .= $this->reporter->col(number_format($data->pending, 2), '50', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
  //       if ($lpodate != '') {
  //         $str .= $this->reporter->col(date('m/d/Y', strtotime($lpodate)), '75', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
  //       } else {
  //         $str .= $this->reporter->col($lpodate, '75', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
  //       }


  //       $str .= $this->reporter->endrow();
  //     }

  //     if ($this->reporter->linecounter == $page) {
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->page_break();
  //       $str .= $this->default_displayHeader($config);
  //       $str .= $this->reporter->addline();
  //       $page = $page + $count;
  //     } //end if
  //   }

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->endreport();

  //   return $str;
  // }
}//end class