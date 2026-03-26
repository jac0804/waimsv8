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

class item_balance_below_minimum
{
  public $modulename = 'Item Balance Below Minimum';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
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

    $fields = ['radioreportitemtype', 'radiorepitemstock'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
      '(0)' as itemtype,
      '(0,1)' as itemstock,
      '' as divsion,
      '' as brand,
      '' as part,
      '' as dwhname,
      '' as category,
      '' as subcat,
      '' as categoryname,
      '' as subcatname,
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
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $result = $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $brandname  = $config['params']['dataparams']['brandname'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $partname   = $config['params']['dataparams']['partname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    $stocks = "";
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

    switch ($itemstock) {
      case '(0)':
        $stocks = " and sum(qty-iss)=0 ";
        break;
      case '(1)':
        $stocks = " and sum(qty-iss)>0 ";
        break;
      default:
        break;
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
    $minimum = 'il.min';
    if ($companyid == 60) { //transpower
      $minimum = 'item.minimum';
    }

    $query = "select minimum,barcode, itemname, groupid,model, part,brand,sizeid,body, class, uom, swh, whname, sum(qty-iss) as balance,cost,amt,category,subcatname
    from (select $minimum as minimum,item.barcode, item.itemname,item.model, item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh, wh.clientname as whname, stock.qty, stock.iss,
    (select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1) as cost, item.amt,cat.name as category, subcat.name as subcatname
    from (((lahead as head 
    left join lastock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join client as wh on wh.clientid=stock.whid) left join itemlevel as il on il.itemid = stock.itemid and il.center = wh.client 
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0

    UNION ALL

    select $minimum as minimum,item.barcode, item.itemname,item.model, item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh, wh.clientname as whname, stock.qty, stock.iss,
    (select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1) as cost, item.amt,cat.name as category,subcat.name as subcatname
    from (((glhead as head 
    left join glstock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join client as wh on wh.clientid=stock.whid) left join itemlevel as il on il.itemid = stock.itemid and il.center = wh.client 
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
    )as ib 
    group by minimum,barcode, itemname, groupid,model, part,brand,sizeid, body, class, uom, swh, whname,category,subcatname,cost,amt having sum(qty-iss)<=minimum $stocks
    order by part, brand,itemname,sizeid";

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $partname   = $config['params']['dataparams']['partname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstock  = $config['params']['dataparams']['itemstock'];

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
    $str .= $this->reporter->col('Item Balance - Below Minimum', '400', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($brandname == '') {
      $str .= $this->reporter->col('Brand : ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Brand : ' . $brandname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    if ($companyid == 14) { //majesty
      if ($partname == '') {
        $str .= $this->reporter->col('Principal : ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      } else {
        $str .= $this->reporter->col('Principal :' . $partname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      }
    } else {
      if ($partname == '') {
        $str .= $this->reporter->col('Part : ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      } else {
        $str .= $this->reporter->col('Part :' . $partname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      }
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $wh, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    switch ($itemtype) {
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    switch ($itemstock) {
      case '(1)':
        $itemstock = 'With Balance';
        break;
      case '(0)':
        $itemstock = 'Without Balance';
        break;
      case '(0,1)':
        $itemstock = 'None';
        break;
    }
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname,  null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->printline();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('BALANCE', '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('MINIMUM STOCK', '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    return $str;
  }

  public function reportDefaultLayout($config)
  {

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $totalbal = 0;
    $totalmin = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->col($data->barcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->minimum, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $totalbal = $totalbal + $data->balance;
      $totalmin = $totalmin + $data->minimum;
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Total', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalbal, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalmin, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class