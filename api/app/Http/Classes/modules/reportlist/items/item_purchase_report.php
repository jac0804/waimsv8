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

class item_purchase_report
{
  public $modulename = 'Item Purchase Report';
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

    $fields = [
      'radioprint',
      'start',
      'end',
      'ditemname',
      'divsion',
      'brandname',
      'brandid',
      'class',
      'dwhname',
      'categoryname',
      'subcatname'
    ];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      case 59: //roosevelt
        array_push($fields, 'dclientname', 'radioposttype');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioposttype.options', [
          ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Unposted', 'value' => '1', 'color' => 'orange'],
          ['label' => 'All', 'value' => '2', 'color' => 'orange']
        ]);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

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
    // $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      adddate(left(now(),10),1) as end,
      0 as itemid,
      '' as ditemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as brandname,
      0 as classid,
      '' as classic,
      '' as categoryid,
      '' as categoryname,
      0 as whid,
      '' as wh,
      '' as whname,
      '' as divsion,
      '' as brand,
      '' as class,
      '' as category,
      '' as subcat,
      '' as dwhname,
      '' as subcatname,
      '' as project, 
      0 as projectid, 
      '' as projectname, 
      '' as ddeptname, 
      0 as deptid,
      '' as dept, 
      '' as deptname,
      '0' as posttype,
      0 as clientid,
      '' as client,
      '' as clientname,
      '' as dclientname";

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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_Layout($config);
        break;
      case 59: //roosevelt
        $result = $this->roosevelt_layout($config);
        break;
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportRoosevelt($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];

    $filter = $filter2 = $filter1 = "";
    if ($barcode != "") $filter .= " and stock.itemid=" . $config['params']['dataparams']['itemid'];
    if ($groupname != "") $filter .= " and item.groupid=" . $config['params']['dataparams']['groupid'];
    if ($categoryname != "") $filter .= " and item.category='".$config['params']['dataparams']['category']."'";
    if ($subcatname != "") $filter .= " and item.subcat='".$config['params']['dataparams']['subcat']."'";
    if ($brandname != "") $filter .= " and item.brand=" . $config['params']['dataparams']['brandid'];
    if ($classname != "") $filter .= " and item.class=" . $config['params']['dataparams']['classid'];
    if ($wh != "") $filter .= " and stock.whid=" . $config['params']['dataparams']['whid'];
    if ($client != '') {
      $filter1 = " and head.client='".$client."'";
      $filter2 = " and head.clientid=".$clientid;
    }

    switch ($posttype) {
      case '0':
        $query = "select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.qty) as qty, 
          sum(stock.cost) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          where head.doc in ('rr','rp') and item.barcode is not null and head.dateid between '$start' and '$end' $filter $filter2 and item.isofficesupplies=0
          group by item.barcode, item.itemname, docno, head.dateid,item.uom, brand.brand_desc, 
          part.part_name,model.model_name,item.sizeid,item.body,stock.ref
          order by brand, part, itemname";
        break;
      case '1':
        $query = "select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.qty) as qty, 
          sum(stock.cost) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          where head.doc in ('rr','rp') and item.barcode is not null and date(head.dateid) between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
          group by item.barcode, item.itemname, head.dateid,item.uom, brand.brand_desc, part.part_name,
          model.model_name,item.sizeid,item.body, head.docno,stock.ref
          order by brand, part, itemname";
        break;
      default:
        $query = "select barcode, itemname, docno, dateid , qty, price, 
          uom, brand, part,model,sizeid,body,ponum
          from (
          select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.qty) as qty, 
          sum(stock.cost) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          where head.doc in ('rr','rp') and item.barcode is not null and date(head.dateid) between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
          group by item.barcode, item.itemname, head.dateid,item.uom, brand.brand_desc, part.part_name,
          model.model_name,item.sizeid,item.body, head.docno,stock.ref
          UNION ALL
          select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.qty) as qty, 
          sum(stock.cost) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          where head.doc in ('rr','rp') and item.barcode is not null and head.dateid between '$start' and '$end' $filter $filter2 and item.isofficesupplies=0
          group by item.barcode, item.itemname, docno, head.dateid,item.uom, brand.brand_desc, 
          part.part_name,model.model_name,item.sizeid,item.body,stock.ref) as ip
          order by brand, part, itemname";
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault($config)
  {
    // QUERY
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
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
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $deptname = $config['params']['dataparams']['ddeptname'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid=" . $projectid;
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    } else {
      $filter1 .= "";
    }

    $query = "select barcode, itemname, docno, dateid , qty, price, 
      uom, brand, part,model,sizeid,body,ponum
      from (
      select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.qty) as qty, 
      sum(stock.cost) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
      part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join item on item.itemid=stock.itemid
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join part_masterfile as part on part.part_id = item.part
      left join model_masterfile as model on model.model_id = item.model
      where head.doc in ('rr','rp') and item.barcode is not null and date(head.dateid) between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
      group by item.barcode, item.itemname, head.dateid,item.uom, brand.brand_desc, part.part_name,
      model.model_name,item.sizeid,item.body, head.docno,stock.ref

      UNION ALL

      select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.qty) as qty, 
      sum(stock.cost) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
      part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join part_masterfile as part on part.part_id = item.part
      left join model_masterfile as model on model.model_id = item.model
      where head.doc in ('rr','rp') and item.barcode is not null and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
      group by item.barcode, item.itemname, docno, head.dateid,item.uom, brand.brand_desc, 
      part.part_name,model.model_name,item.sizeid,item.body,stock.ref) as ip
      order by brand, part, itemname";

    return $this->coreFunctions->opentable($query);
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

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];

    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whname     = $config['params']['dataparams']['whname'];

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($barcode == "") {
      $barcode = "ALL";
    }
    if ($groupname == "") {
      $groupname = "ALL";
    }
    if ($brandname == "") {
      $brandname = "ALL";
    }
    if ($classname == "") {
      $classname = "ALL";
    }

    if ($subcatname == "") {
      $subcatname = "ALL";
    } else {
      $subcatname = $config['params']['dataparams']['subcatname'];
    }

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM PURCHASE REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, '', $border, '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Items : ' . $barcode, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group : ' . $groupname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('Brand : ' . $brandname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('Class : ' . $classname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
    }

    $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '350', null, false, $border, 'B', 'L', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('DOC #', '100', null, false, $border, 'B', 'L', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('DATE PURCHASE', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function MAJESTY_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);
    $item = null;
    $subtotal = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($item != $data->barcode) {

        if ($item != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
          $str .= $this->reporter->col('SUBTOTAL', '100', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');


          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode . '&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '8px');
        $str .= $this->reporter->col($data->itemname . ' - ' . $data->brand . ' - ' . $data->part . ' - ' . $data->model . ' - ' . $data->sizeid . ' - ' . $data->body, '350', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');

        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('&nbsp', '350', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');

      $item = $data->barcode;
      $subtotal = $subtotal + $data->price;
      $amt = $amt + $data->price;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('SUBTOTAL', '100', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function roosevelt_layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '8';
    $result = $this->reportRoosevelt($config);
    $count = 26;
    $page = 25;

    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->roosevelt_displayHeader($config);
    $item = null;
    $subtotal = 0;
    $amt = 0;
    $totalcount = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($item != $data->barcode) {
        if ($item != "") {
          $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->barcode, '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->sizeid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->docno, '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->qty,2), '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->price,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          // $str .= $this->reporter->col($data->barcode . '&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '8px');
          // $str .= $this->reporter->col($data->itemname . ' - ' . $data->brand . ' - ' . $data->part . ' - ' . $data->model . ' - ' . $data->sizeid . ' - ' . $data->body, '350', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '8px');
          // $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          // $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          // $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          // $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '8px');
          // $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->barcode, '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->sizeid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->docno, '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->qty,2), '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->price,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      }
      $item = $data->barcode;
      $totalcount += $data->qty;
      $subtotal = $subtotal + $data->price;
      $amt = $amt + $data->price;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->roosevelt_displayHeader($config);
        $page = $page + $count;
      }

    }

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col('', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col('', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col('', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col('SUBTOTAL', '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subtotal,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
    // $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($totalcount,2), '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($amt,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function roosevelt_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];

    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whname     = $config['params']['dataparams']['whname'];
    $clientname = $config['params']['dataparams']['clientname'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM PURCHASE REPORT', null, null, false, $border, '', 'C', $font, '15', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, '', $border, '', 'l', '', '10', '', '', '');
        $str .= $this->reporter->col('Items : ' . ($barcode == '' ? 'ALL' : $barcode), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('WH : ' . ($whname == '' ? 'ALL' : $whname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Group : ' . ($groupname == '' ? 'ALL' : $groupname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('Brand : ' . ($brandname == '' ? 'ALL' : $brandname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('Class : ' . ($classname == '' ? 'ALL' : $classname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('Category : '.($categoryname == '' ? 'ALL' : $categoryname), '200', null, false, '1px solid', '', 'L', $font, $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->col('Sub-Category : ' . ($subcatname == '' ? 'ALL' : $subcatname), '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier : '.($clientname == '' ? 'ALL' : $clientname), '500', null, false, '1px solid', '', 'L', $font, $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('SIZE', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('QTY', '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->endrow();

    return $str;
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

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];

    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
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

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($barcode == "") {
      $barcode = "ALL";
    }
    if ($groupname == "") {
      $groupname = "ALL";
    }
    if ($brandname == "") {
      $brandname = "ALL";
    }
    if ($classname == "") {
      $classname = "ALL";
    }

    if ($subcatname == "") {
      $subcatname = "ALL";
    } else {
      $subcatname = $config['params']['dataparams']['subcatname'];
    }

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM PURCHASE REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '350', null, '', $border, '', 'l', '', '10', '', '', '');
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Items : ' . $barcode, '350', null, '', $border, '', 'l', '', '10', '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, '', $border, '', 'l', '', '10', '', '', '');
      $str .= $this->reporter->col('Items : ' . $barcode, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('WH : ' . $whname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . $groupname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);

      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('ITEM DESCRIPTION', '250', null, false, $border, 'B', 'L', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('DOC #', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('RR DATE', '80', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('PO #', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('DATE PURCHASE', '90', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('QTY', '80', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'R', 'Verdana', $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '8px');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('ITEM DESCRIPTION', '350', null, false, $border, 'B', 'L', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('DOC #', '100', null, false, $border, 'B', 'L', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('DATE PURCHASE', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    }

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);

    $count = 26;
    $page = 25;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $item = null;
    $subtotal = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($item != $data->barcode) {

        if ($item != "") {
          $str .= $this->reporter->startrow();
          if ($companyid == 8) { //maxipro
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('SUBTOTAL', '80', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
            $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
          } else {
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
            $str .= $this->reporter->col('SUBTOTAL', '100', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
          }

          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }
        $str .= $this->reporter->startrow();
        if ($companyid == 8) { //maxipro
          $str .= $this->reporter->col($data->barcode . '&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '8px');
          $str .= $this->reporter->col($data->itemname . ' - ' . $data->brand . ' - ' . $data->part . ' - ' . $data->model . ' - ' . $data->sizeid . ' - ' . $data->body, '250', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
        } else {
          $str .= $this->reporter->col($data->barcode . '&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '8px');
          $str .= $this->reporter->col($data->itemname . ' - ' . $data->brand . ' - ' . $data->part . ' - ' . $data->model . ' - ' . $data->sizeid . ' - ' . $data->body, '350', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '8px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '8px');
        }
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col('&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '250', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->ponum, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('&nbsp', '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '350', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      }

      $item = $data->barcode;
      $subtotal = $subtotal + $data->price;
      $amt = $amt + $data->price;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('SUBTOTAL', '80', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('SUBTOTAL', '100', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('TOTAL', '80', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '8px');
      $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class