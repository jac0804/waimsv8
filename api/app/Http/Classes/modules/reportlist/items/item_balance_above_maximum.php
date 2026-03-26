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

class item_balance_above_maximum
{
  public $modulename = 'Item Balance Above Maximum';
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

    $fields = ['radioprint', 'start', 'ditemname', 'divsion', 'brandname', 'brandid', 'part', 'dwhname', 'categoryname', 'subcatname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      case 14: //majesty
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'part.label', 'Principal');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'start.label', 'Balance as of');
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    unset($col1['divsion']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'part.name', 'partname');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      left(now(),10) as start,
      '' as end,
      0 as itemid,
      '' as ditemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as brandname,
      0 as partid,
      '' as partname,
      0 as whid,
      '' as wh,
      '' as whname,
      '' as divsion,
      '' as brand,
      '' as part,
      '' as dwhname,
      '' as category,
      '' as categoryname,
      '' as subcatname,
      '' as subcat, 
      '' as project, 
      0 as projectid, 
      '' as projectname,
      0 as deptid,
      '' as ddeptname, 
      '' as dept, 
      '' as deptname ";

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
    $result = $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid    = $config['params']['companyid'];
    $asof         = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $partname     = $config['params']['dataparams']['partname'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];

    $filter = "";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid = $itemid";
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid = $groupid";
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category = '$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat = '$subcat'";
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid = $whid";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand = $brandid";
    }
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part = $partid";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and head.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    } else {
      $filter1 .= "";
    }
    $minimum = 'il.max';
    if ($companyid == 60) { //transpower
      $minimum = 'item.maximum';
    }
    $query = "select item.barcode, item.itemname,modelgrp.model_name as model, partgrp.part_name as part,item.groupid,
    brand.brand_desc as brand, item.sizeid,item.body, item.class,
    item.uom, wh.client as swh, wh.clientname as whname, ifnull(sum(stock.qty-stock.iss),0) as balance,
    (select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1) as cost,
    (select sum(bal) from rrstatus where itemid=item.itemid ) as bal,
    item.amt, $minimum as maximum,
    cat.name as category, subcat.name as subcatname
    from (((lahead as head 
    left join lastock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid    
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join client as wh on wh.clientid=stock.whid) 
    left join itemlevel as il on il.itemid = stock.itemid and il.center = wh.client 
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
    group by barcode, itemname, groupid,modelgrp.model_name, partgrp.part_name,brand.brand_desc,sizeid,body, class, 
    uom, wh.client, wh.clientname,cat.name, subcat.name, cost,bal,amt, $minimum, maximum having ifnull(sum(stock.qty-stock.iss),0)>maximum
 
    
    UNION ALL

    select item.barcode, item.itemname,modelgrp.model_name as model, partgrp.part_name as part,item.groupid, 
    brand.brand_desc as brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh, wh.clientname as whname, 
    ifnull(sum(stock.qty-stock.iss),0) as balance,
    (select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1) as cost,
    (select sum(bal) from rrstatus where itemid=item.itemid) as bal, item.amt, $minimum as maximum,
    cat.name as category, subcat.name as subcatname
    from (((glhead as head 
    left join glstock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid    
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join client as wh on wh.clientid=stock.whid) left join itemlevel as il on il.itemid = stock.itemid and il.center = wh.client  
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
    group by barcode, itemname, groupid,modelgrp.model_name, partgrp.part_name,brand.brand_desc,sizeid,body,
    class, uom, wh.client, wh.clientname, cat.name, subcat.name, cost,bal, amt, $minimum, maximum  having ifnull(sum(stock.qty-stock.iss),0)>maximum
    order by part,brand,itemname,sizeid";

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center    = $config['params']['center'];
    $username  = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof         = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcat'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $partname     = $config['params']['dataparams']['partname'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];

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

    if ($companyid == 14) { //majesty
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item Balance Above Maximum', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('INVENTORY BALANCE - Above Maximum', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '750', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    if ($barcode == '') {
      $str .= $this->reporter->col('Item : ALL ITEM', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Item : ' . $barcode, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL GROUP', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($brandname == '') {
      $str .= $this->reporter->col('Brand : ALL BRAND', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Brand : ' . $brandname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    if ($companyid == 14) { //majesty
      if ($partname == '') {
        $str .= $this->reporter->col('Principal : ALL PART', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      } else {
        $str .= $this->reporter->col('Principal : ' . $partname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      }
    } else {
      if ($partname == '') {
        $str .= $this->reporter->col('Part : ALL PART', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      } else {
        $str .= $this->reporter->col('Part : ' . $partname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      }
    }

    if ($wh == '') {
      $str .= $this->reporter->col('WH : ALL WAREHOUSE', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('WH : ' . $wh, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col('Project : ' . $projname, '80', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '450', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAXIMUM', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 39;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $part = "";
    $brand = "";
    $totalbal = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();

      if ($part == $data->part) {
        $part = "";
        if ($brand == $data->brand) {
          $brand = "";
        } else {
          $brand = $data->brand;
        }
      } else {
        $part = $data->part;
        if ($brand == $data->brand) {
          $brand = "";
        } else {
          $brand = $data->brand;
        }
      }

      $balance = number_format($data->bal, 2);
      if ($balance == 0) {
        $balance = '-';
      }

      $maximum = number_format($data->maximum, 2);
      if ($maximum == 0) {
        $maximum = '-';
      }

      if ($companyid != 14) { //not majesty
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname . ' - ' . $data->brand . ' - ' . $data->part . ' - ' . $data->model . ' - ' . $data->sizeid . ' - ' . $data->body, '450', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($maximum, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $totalbal = $totalbal + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '450', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalbal, 2), '75', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class