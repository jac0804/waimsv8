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
use App\Http\Classes\modules\masterfile\supplier;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class monthly_analyze_item_purchase
{
  public $modulename = 'Monthly Analyze Item Purchase';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1200'];

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

    $fields = ['radioprint', 'dclientname', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'categoryname', 'subcatname',  'dwhname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
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

    $fields = ['year', 'radioposttype', 'radioreportanalyzedbypurhcase', 'radioreportitemtype'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'year.type', 'input');
    data_set($col2, 'year.readonly', false);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'orange'],
      ['label' => 'All', 'value' => '2', 'color' => 'orange']
    ]);

    data_set($col2, 'radioreportanalyzedbypurhcase.options', [
      ['label' => 'Value Purchased', 'value' => 'value', 'color' => 'orange'],
      ['label' => 'Unit Purchased', 'value' => 'unit', 'color' => 'orange'],
      ['label' => 'Price Per Unit', 'value' => 'unitprice', 'color' => 'orange'],
    ]);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      0 as clientid,
      '' as client,
      '' as clientname,
      0 as itemid,
      '' as itemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as brandname,
      0 as classid,
      '' as classic,
      '' as categoryname,
      '' as subcatname,
      0 as whid,
      '' as wh,
      '' as whname,
      left(now(),4) as year,
      '0' as posttype,
      'value' as analyzedby,
      '(0,1)' as itemtype,
      '' as dclientname,
      '' as ditemname,
      '' as divsion,
      '' as brand,
      '' as class,
      '' as category,
      '' as subcat,
      '' as dwhname,
      '' as project, 
      0 as projectid, 
      '' as projectname,
      0 as deptid,
      '' as ddeptname, 
      '' as dept, 
      '' as deptname";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $result = $this->reportDefault($config);
    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    if ($companyid == 59) { //roosevelt
      $data = $this->reportRooseveltLayout($config, $result);
    } else {
      $data = $this->reportDefaultLayout($config, $result);
    }
    return $data;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0': // POSTED
        $query = $this->DEFAULT_QUERY_POSTED($config);
        break;
      case '1':
        $query = $this->DEFAULT_QUERY_UNPOSTED($config);
        break;
      default:
        $query = $this->DEFAULT_QUERY_ALL($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY_POSTED($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $classname    = $config['params']['dataparams']['classic'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $wh           = $config['params']['dataparams']['wh'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
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
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($analyzedby == 'unit') {
      $war = "stock.qty";
    } else if ($analyzedby == 'unitprice') {
      $war = "(case when stock.qty = 0 then 0 else stock.ext/stock.qty end)";
    } else {
      $war = " stock.ext ";
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

    $query = "select barcode, size, uom, tr, groupid, part, brand, model, body, itemname, yr, sum(mojan) as mojan, 
    sum(mofeb) as mofeb, sum(momar) as momar, sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
    sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
      select item.barcode, head.clientname, item.sizeid as size, 'p' as tr, ifnull(stockgrp.stockgrp_name, 'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand,
      ifnull(pp.part_name, 'NO PART') as part, ifnull(mm.model_name, 'NO MODEL') as model, item.body,
      ifnull(item.itemname, '') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid) = 1 then " . $war . " else 0 end) as mojan,
      sum(case when month(head.dateid) = 2 then " . $war . " else 0 end) as mofeb,
      sum(case when month(head.dateid) = 3 then " . $war . " else 0 end) as momar,
      sum(case when month(head.dateid) = 4 then " . $war . " else 0 end) as moapr,
      sum(case when month(head.dateid) = 5 then " . $war . " else 0 end) as momay,
      sum(case when month(head.dateid) = 6 then " . $war . " else 0 end) as mojun,
      sum(case when month(head.dateid) = 7 then " . $war . " else 0 end) as mojul,
      sum(case when month(head.dateid) = 8 then " . $war . " else 0 end) as moaug,
      sum(case when month(head.dateid) = 9 then " . $war . " else 0 end) as mosep,
      sum(case when month(head.dateid) = 10 then " . $war . " else 0 end) as mooct,
      sum(case when month(head.dateid) = 11 then " . $war . " else 0 end) as monov,
      sum(case when month(head.dateid) = 12 then " . $war . " else 0 end) as modec, item.uom
      from (((glhead as head left join glstock as stock on stock.trno = head.trno)
      left join client on client.clientid = head.clientid)
      left join cntnum on cntnum.trno = head.trno)
      left join item on item.itemid = stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join frontend_ebrands on item.brand = frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      where head.doc in ('RR', 'RP') and year(head.dateid) = " . $year . " " . $filter . " " . $filter1 . " and item.isofficesupplies = 0
      group by item.barcode, head.clientname, item.sizeid, item.itemname, ifnull(stockgrp.stockgrp_name, 'NO GROUP'), 
      frontend_ebrands.brand_desc, ifnull(pp.part_name, 'NO PART'), ifnull(mm.model_name, 'NO MODEL'), item.body, year(head.dateid), item.uom
    ) as maip group by part, brand, barcode, size, tr, groupid, model, body, itemname, yr, uom
    order by part, brand, barcode, itemname";

    return $query;
  }

  public function DEFAULT_QUERY_UNPOSTED($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $classname    = $config['params']['dataparams']['classic'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $wh           = $config['params']['dataparams']['wh'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
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
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($analyzedby == 'unit') {
      $war = "stock.qty";
    } else if ($analyzedby == 'unitprice') {
      $war = "(case when stock.qty = 0 then 0 else stock.ext/stock.qty end)";
    } else {
      $war = " stock.ext ";
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

    $query = "select barcode, size, uom, tr, groupid, part, brand, model, body, itemname, yr, 
    sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar, sum(moapr) as moapr, sum(momay) as momay, 
    sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug, sum(mosep) as mosep, sum(mooct) as mooct, 
    sum(monov) as monov, sum(modec) as modec from (
      select item.barcode, head.clientname, item.sizeid as size, 'u' as tr, ifnull(stockgrp.stockgrp_name, 'NO GROUP') as groupid,
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand,
      ifnull(pp.part_name, 'NO PART') as part, ifnull(mm.model_name, 'NO MODEL') as model, item.body,
      ifnull(item.itemname, '') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid) = 1 then " . $war . " else 0 end) as mojan,
      sum(case when month(head.dateid) = 2 then " . $war . " else 0 end) as mofeb,
      sum(case when month(head.dateid) = 3 then " . $war . " else 0 end) as momar,
      sum(case when month(head.dateid) = 4 then " . $war . " else 0 end) as moapr,
      sum(case when month(head.dateid) = 5 then " . $war . " else 0 end) as momay,
      sum(case when month(head.dateid) = 6 then " . $war . " else 0 end) as mojun,
      sum(case when month(head.dateid) = 7 then " . $war . " else 0 end) as mojul,
      sum(case when month(head.dateid) = 8 then " . $war . " else 0 end) as moaug,
      sum(case when month(head.dateid) = 9 then " . $war . " else 0 end) as mosep,
      sum(case when month(head.dateid) = 10 then " . $war . " else 0 end) as mooct,
      sum(case when month(head.dateid) = 11 then " . $war . " else 0 end) as monov,
      sum(case when month(head.dateid) = 12 then " . $war . " else 0 end) as modec, item.uom
      from (((lahead as head 
      left join lastock as stock on stock.trno = head.trno)
      left join client on client.client = head.client)
      left join item on item.itemid = stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join frontend_ebrands on item.brand = frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join cntnum on cntnum.trno = head.trno
      left join center on center.code = cntnum.center
      where head.doc in ('RR', 'RP') and year(head.dateid) = " . $year . " " . $filter . " " . $filter1 . " and item.isofficesupplies = 0
      group by item.barcode, head.clientname, item.sizeid, item.itemname, ifnull(stockgrp.stockgrp_name, 'NO GROUP'), 
      frontend_ebrands.brand_desc, ifnull(pp.part_name, 'NO PART'), ifnull(mm.model_name, 'NO MODEL'),
      item.body, year(head.dateid), item.uom
    ) as maip
    group by part, brand, barcode, size, tr, groupid, model, body, itemname, yr, uom
    order by part, brand, barcode, itemname";

    return $query;
  }

  private function DEFAULT_QUERY_ALL($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $classname    = $config['params']['dataparams']['classic'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $wh           = $config['params']['dataparams']['wh'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
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
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($analyzedby == 'unit') {
      $war = "stock.qty";
    } else if ($analyzedby == 'unitprice') {
      $war = "(case when stock.qty = 0 then 0 else stock.ext/stock.qty end)";
    } else {
      $war = " stock.ext ";
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

    $query = "select barcode, size, uom, tr, groupid, part, brand, model, body, itemname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
    sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
    sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
      select item.barcode, client.clientname, item.sizeid as size, 'p' as tr, ifnull(stockgrp.stockgrp_name, 'NO GROUP') as groupid,
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand,
      ifnull(pp.part_name, 'NO PART') as part, ifnull(mm.model_name, 'NO MODEL') as model, item.body,
      ifnull(item.itemname, '') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid) = 1 then " . $war . " else 0 end) as mojan,
      sum(case when month(head.dateid) = 2 then " . $war . " else 0 end) as mofeb,
      sum(case when month(head.dateid) = 3 then " . $war . " else 0 end) as momar,
      sum(case when month(head.dateid) = 4 then " . $war . " else 0 end) as moapr,
      sum(case when month(head.dateid) = 5 then " . $war . " else 0 end) as momay,
      sum(case when month(head.dateid) = 6 then " . $war . " else 0 end) as mojun,
      sum(case when month(head.dateid) = 7 then " . $war . " else 0 end) as mojul,
      sum(case when month(head.dateid) = 8 then " . $war . " else 0 end) as moaug,
      sum(case when month(head.dateid) = 9 then " . $war . " else 0 end) as mosep,
      sum(case when month(head.dateid) = 10 then " . $war . " else 0 end) as mooct,
      sum(case when month(head.dateid) = 11 then " . $war . " else 0 end) as monov,
      sum(case when month(head.dateid) = 12 then " . $war . " else 0 end) as modec, item.uom
      from (((glhead as head left join glstock as stock on stock.trno = head.trno)
      left join client on client.clientid = head.clientid)
      left join cntnum on cntnum.trno = head.trno)
      left join item on item.itemid = stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join frontend_ebrands on item.brand = frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      where head.doc in ('RR', 'RP') and year(head.dateid) = " . $year . " " . $filter . " " . $filter1 . " and item.isofficesupplies = 0
      group by item.barcode, client.clientname, item.sizeid, item.itemname, ifnull(stockgrp.stockgrp_name, 'NO GROUP'),
      frontend_ebrands.brand_desc,
      ifnull(pp.part_name, 'NO PART'), ifnull(mm.model_name, 'NO MODEL'), item.body, year(head.dateid), item.uom
      
      UNION ALL

      select item.barcode, client.clientname, item.sizeid as size, 'u' as tr, ifnull(stockgrp.stockgrp_name, 'NO GROUP') as groupid,
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand,
      ifnull(pp.part_name, 'NO PART') as part, ifnull(mm.model_name, 'NO MODEL') as model, item.body,
      ifnull(item.itemname, '') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid) = 1 then " . $war . " else 0 end) as mojan,
      sum(case when month(head.dateid) = 2 then " . $war . " else 0 end) as mofeb,
      sum(case when month(head.dateid) = 3 then " . $war . " else 0 end) as momar,
      sum(case when month(head.dateid) = 4 then " . $war . " else 0 end) as moapr,
      sum(case when month(head.dateid) = 5 then " . $war . " else 0 end) as momay,
      sum(case when month(head.dateid) = 6 then " . $war . " else 0 end) as mojun,
      sum(case when month(head.dateid) = 7 then " . $war . " else 0 end) as mojul,
      sum(case when month(head.dateid) = 8 then " . $war . " else 0 end) as moaug,
      sum(case when month(head.dateid) = 9 then " . $war . " else 0 end) as mosep,
      sum(case when month(head.dateid) = 10 then " . $war . " else 0 end) as mooct,
      sum(case when month(head.dateid) = 11 then " . $war . " else 0 end) as monov,
      sum(case when month(head.dateid) = 12 then " . $war . " else 0 end) as modec, item.uom
      from (((lahead as head
      left join lastock as stock on stock.trno = head.trno)
      left join client on client.client = head.client)
      left join item on item.itemid = stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join frontend_ebrands on item.brand = frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join cntnum on cntnum.trno = head.trno
      left join center on center.code = cntnum.center
      where head.doc in ('RR', 'RP') and year(head.dateid) = " . $year . " " . $filter . " " . $filter1 . " and item.isofficesupplies = 0
      group by item.barcode, client.clientname, item.sizeid, item.itemname, ifnull(stockgrp.stockgrp_name, 'NO GROUP'),
      frontend_ebrands.brand_desc, ifnull(pp.part_name, 'NO PART'), ifnull(mm.model_name, 'NO MODEL'),
      item.body, year(head.dateid), item.uom) as x
    group by part, brand, barcode, size, tr, groupid, model, body, itemname, yr, uom
    order by part, brand, barcode, itemname";

    return $query;
  }

  public function reportRooseveltLayout($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $fontsize11 = 11;
    $count = $page = 40;
    $this->reporter->linecounter = 0;
    if (empty($result)) return $this->othersClass->emptydata($config);

    $str = '';
    $layoutsize = '1200';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->roosevelt_displayHeader($config);
    $str .= $this->roosevelt_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $totalmojan = $totalmofeb = $totalmomar = $totalmoapr = $totalmomay = $totalmojun = $totalmojul = $totalmoaug = $totalmosep = $totalmooct = $totalmonov = $totalmodec = $amt = $totalamt = 0;
    $part = $brand = "";
    $subjan = $subfeb = $submar = $subapr = $submay = $subjun = $subjul = $subaug = $subsep = $suboct = $subnov = $subdec = $subamt = 0;
    $gsubjan = $gsubfeb = $gsubmar = $gsubapr = $gsubmay = $gsubjun = $gsubjul = $gsubaug = $gsubsep = $gsuboct = $gsubnov = $gsubdec = $gsubamt = 0;
    $partName = 'Part';
    $i = 0;
    foreach ($result as $key => $data) {
      // if ($data->part == '') {
      //   $data->part = 'No ' . $partName;
      //   if (isset($data->brandname)) {
      //     if ($data->brandname == '') $data->brand = 'No Brand';
      //   } else {
      //     $data->brand = 'No Brand';
      //   }
      // }

      // if ($brand != '' && $brand != strtoupper($data->brand)) {
      //   BrandSubTotalHere:
      //   $str .= $this->reporter->begintable($layoutsize);
      //     $str .= $this->reporter->startrow();
      //       $str .= $this->reporter->col($brand . ' - SUB TOTAL:', '140', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
      //       $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subjan, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subfeb, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($submar, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subapr, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($submay, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subjun, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subjul, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subaug, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subsep, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($suboct, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subnov, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subdec, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subamt, 2), '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->endrow();
      //   $str .= $this->reporter->endtable();
      //   $brand = '';
      //   $subjan = $subfeb = $submar = $subapr = $submay = $subjun = $subjul = $subaug = $subsep = $suboct = $subnov = $subdec = $subamt = 0;
      //   if ($i == (count((array)$result) - 1)) goto PartSubtotalHere;
      // }
      // if ($part == '' || $part != strtoupper($data->part)) {
      //   if ($part != '' && $part != strtoupper($data->part)) {
      //     PartSubtotalHere:
      //     $str .= $this->reporter->begintable($layoutsize);
      //       $str .= $this->reporter->startrow();
      //         $str .= $this->reporter->col($part . ' - SUB TOTAL:', '140', null, false, $border, '', 'R', $font, $font_size9, 'B', '', '', '');
      //         $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubjan, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubfeb, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubmar, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubapr, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubmay, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubjun, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubjul, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubaug, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubsep, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsuboct, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubnov, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubdec, 2), '75', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //         $str .= $this->reporter->col(number_format($gsubamt, 2), '80', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->endrow();
      //     $str .= $this->reporter->endtable();
      //     $brand = '';
      //     $str .= '<br/>';
      //     if ($i == (count((array)$result) - 1)) break;
      //   }
      //   $str .= $this->reporter->begintable($layoutsize);
      //     $str .= $this->reporter->startrow();
      //       $str .= $this->reporter->col(strtoupper($data->part), '1000', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      //     $str .= $this->reporter->endrow();
      //   $str .= $this->reporter->endtable();
      // }

      // if ($brand == '' || $brand != strtoupper($data->brand)) {
      //   if ($brand != '' && $brand != strtoupper($data->brand)) goto BrandSubTotalHere;
      //   $str .= $this->reporter->begintable($layoutsize);
      //     $str .= $this->reporter->startrow();
      //       $str .= $this->reporter->col(strtoupper($data->brand), '1000', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      //     $str .= $this->reporter->endrow();
      //   $str .= $this->reporter->endtable();
      // }

      $mojan = ($data->mojan <> 0 ? number_format($data->mojan, 2) : '-');
      $mofeb = ($data->mofeb <> 0 ? number_format($data->mofeb, 2) : '-');
      $momar = ($data->momar <> 0 ? number_format($data->momar, 2) : '-');
      $moapr = ($data->moapr <> 0 ? number_format($data->moapr, 2) : '-');
      $momay = ($data->momay <> 0 ? number_format($data->momay, 2) : '-');
      $mojun = ($data->mojun <> 0 ? number_format($data->mojun, 2) : '-');
      $mojul = ($data->mojul <> 0 ? number_format($data->mojul, 2) : '-');
      $moaug = ($data->moaug <> 0 ? number_format($data->moaug, 2) : '-');
      $mosep = ($data->mosep <> 0 ? number_format($data->mosep, 2) : '-');
      $mooct = ($data->mooct <> 0 ? number_format($data->mooct, 2) : '-');
      $monov = ($data->monov <> 0 ? number_format($data->monov, 2) : '-');
      $modec = ($data->modec <> 0 ? number_format($data->modec, 2) : '-');
      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data->itemname, '140', null, false, $border, 'TLRB', 'L', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col($data->size. ' ', '80', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col($mojan, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($mofeb, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($momar, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($moapr, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($momay, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($mojun, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($mojul, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($moaug, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($mosep, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($mooct, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($monov, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col($modec, '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
          $str .= $this->reporter->col(number_format($amt, 2).' ', '80', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //brand
      $subjan += $data->mojan;
      $subfeb += $data->mofeb;
      $submar += $data->momar;
      $subapr += $data->moapr;
      $submay += $data->momay;
      $subjun += $data->mojun;
      $subjul += $data->mojul;
      $subaug += $data->moaug;
      $subsep += $data->mosep;
      $suboct += $data->mooct;
      $subnov += $data->monov;
      $subdec += $data->modec;
      $subamt = $subamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      //part
      $gsubjan += $data->mojan;
      $gsubfeb += $data->mofeb;
      $gsubmar += $data->momar;
      $gsubapr += $data->moapr;
      $gsubmay += $data->momay;
      $gsubjun += $data->mojun;
      $gsubjul += $data->mojul;
      $gsubaug += $data->moaug;
      $gsubsep += $data->mosep;
      $gsuboct += $data->mooct;
      $gsubnov += $data->monov;
      $gsubdec += $data->modec;
      $gsubamt = $gsubamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $totalmojan += $data->mojan;
      $totalmofeb += $data->mofeb;
      $totalmomar += $data->momar;
      $totalmoapr += $data->moapr;
      $totalmomay += $data->momay;
      $totalmojun += $data->mojun;
      $totalmojul += $data->mojul;
      $totalmoaug += $data->moaug;
      $totalmosep += $data->mosep;
      $totalmooct += $data->mooct;
      $totalmonov += $data->monov;
      $totalmodec += $data->modec;
      $totalamt += $amt;

      $brand = strtoupper($data->brand);
      $part = strtoupper($data->part);

      // if ($i == (count((array)$result) - 1)) goto BrandSubTotalHere;
      $i++;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) $str .= $this->roosevelt_displayHeader($config);
        $str .= $this->roosevelt_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '140', null, false, $border, 'TLRB', 'L', $font, $font_size9, 'b', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '', '');
        $str .= $this->reporter->col($totalmojan == 0 ? '-' : number_format($totalmojan, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmofeb == 0 ? '-' : number_format($totalmofeb, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmomar == 0 ? '-' : number_format($totalmomar, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmoapr == 0 ? '-' : number_format($totalmoapr, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmomay == 0 ? '-' : number_format($totalmomay, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmojun == 0 ? '-' : number_format($totalmojun, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmojul == 0 ? '-' : number_format($totalmojul, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmoaug == 0 ? '-' : number_format($totalmoaug, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmosep == 0 ? '-' : number_format($totalmosep, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmooct == 0 ? '-' : number_format($totalmooct, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmonov == 0 ? '-' : number_format($totalmonov, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalmodec == 0 ? '-' : number_format($totalmodec, 2), '75', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
        $str .= $this->reporter->col($totalamt == 0 ? '-' : number_format($totalamt, 2), '80', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'b', '0 2px 0 0', '');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function roosevelt_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $font_size9 = '9';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $classname  = $config['params']['dataparams']['classic'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $year       = $config['params']['dataparams']['year'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    $str = '';
    $layoutsize = '1200';

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ANALYZE ITEM PURCHASE (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier : ' . strtoupper(($client == '' ? 'ALL' : $client)), NULL, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Item :' . strtoupper(($barcode == '' ? 'ALL' : $barcode)), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Group :' . strtoupper(($groupname == '' ? 'ALL' : $groupname)), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Brand : ' . strtoupper(($brandname == '' ? 'ALL' : $brandname)), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Class :' . strtoupper(($classname == '' ? 'ALL' : $classname)), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Category : ' . ($categoryname == '' ? 'ALL' : $categoryname), '200', null, false, '1px solid ', '', 'L', $font_size, '', '', $padding, $margin);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font_size, '', '', '');
        $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->col('Sub-Category : ' . ($subcatname == '' ? 'ALL' : $subcatname), '200', null, false, '1px solid ', '', 'L', $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font_size, '', '', '');
        $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  private function roosevelt_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $font_size9 = '9';
    $str = '';
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ITEM DESCRIPTION', '140', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('SIZE/UOM', '80', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('JAN', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('FEB', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('MAR', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('APR', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('MAY', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('JUN', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('JUL', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('AUG', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('SEP', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('OCT', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('NOV', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('DEC', '75', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');
      $str .= $this->reporter->col('TOTAL', '80', '', '', $border, 'TLRB', 'C', $font, $font_size9, 'B', '', '');

    $str .= $this->reporter->endrow();

    return $str;
  }
  
  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $font_size9 = '9';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $classname  = $config['params']['dataparams']['classic'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $year       = $config['params']['dataparams']['year'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

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
    if ($client == "") {
      $client = "ALL";
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

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
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
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ANALYZE ITEM PURCHASE (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Supplier : ' . strtoupper($client), '200', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname), '150', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . strtoupper($classname), '150', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Year : ' . $year, '130', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), '120', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . strtoupper($barcode), '200', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), '150', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . strtoupper($whname), '150', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '130', null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '120', null, false, $border, '', 'L', $font_size, '', '', '');

      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Supplier : ' . strtoupper($client), NULL, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . strtoupper($groupname), null, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . strtoupper($classname), null, null, false, $border, '', 'L', $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), null, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, false, $border, '', 'L', $font_size, '', '', '');
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $font_size9 = '9';
    $str = '';
    // $companyid = $config['params']['companyid'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM DESCRIPTION', '140', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('APR', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    if (strtoupper($analyzedby) == "UNIT") {
      $str .= $this->reporter->col('QUANTITY', '80', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    } else {
      $str .= $this->reporter->col('AMOUNT', '80', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    }

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {
    $border = '1px solid';
    // $border_line = '';
    // $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $fontsize11 = 11;

    $count = 40;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $amt = 0;
    $totalamt = 0;

    $part = "";
    $brand = "";
    //brand
    $subjan = 0;
    $subfeb = 0;
    $submar = 0;
    $subapr = 0;
    $submay = 0;
    $subjun = 0;
    $subjul = 0;
    $subaug = 0;
    $subsep = 0;
    $suboct = 0;
    $subnov = 0;
    $subdec = 0;
    $subamt = 0;
    //part
    $gsubjan = 0;
    $gsubfeb = 0;
    $gsubmar = 0;
    $gsubapr = 0;
    $gsubmay = 0;
    $gsubjun = 0;
    $gsubjul = 0;
    $gsubaug = 0;
    $gsubsep = 0;
    $gsuboct = 0;
    $gsubnov = 0;
    $gsubdec = 0;
    $gsubamt = 0;

    $partName = 'Part';
    $i = 0;
    foreach ($result as $key => $data) {

      if ($data->part == '') {
        $data->part = 'No ' . $partName;

        if (isset($data->brandname)) {
          if ($data->brandname == '') {
            $data->brand = 'No Brand';
          }
        } else {
          $data->brand = 'No Brand';
        }
      }

      if ($brand != '' && $brand != strtoupper($data->brand)) {
        BrandSubTotalHere:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand . ' - SUB TOTAL:', '140', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
        $str .= $this->reporter->col(number_format($subjan, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subfeb, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($submar, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subapr, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($submay, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subjun, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subjul, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subaug, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subsep, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($suboct, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subnov, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subdec, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($subamt, 2), '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $brand = '';
        $subjan = 0;
        $subfeb = 0;
        $submar = 0;
        $subapr = 0;
        $submay = 0;
        $subjun = 0;
        $subjul = 0;
        $subaug = 0;
        $subsep = 0;
        $suboct = 0;
        $subnov = 0;
        $subdec = 0;
        $subamt = 0;
        if ($i == (count((array)$result) - 1)) {
          goto PartSubtotalHere;
        }
      }

      if ($part == '' || $part != strtoupper($data->part)) {

        if ($part != '' && $part != strtoupper($data->part)) {
          PartSubtotalHere:
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($part . ' - SUB TOTAL:', '140', null, false, $border, '', 'R', $font, $font_size9, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjan, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubfeb, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmar, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubapr, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmay, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjun, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjul, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubaug, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubsep, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsuboct, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubnov, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubdec, 2), '65', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubamt, 2), '80', null, false, $border, 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $brand = '';

          $str .= '<br/>';

          if ($i == (count((array)$result) - 1)) {
            break;
          }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($data->part), '1000', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      if ($brand == '' || $brand != strtoupper($data->brand)) {

        if ($brand != '' && $brand != strtoupper($data->brand)) {
          goto BrandSubTotalHere;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($data->brand), '1000', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $mojan = ($data->mojan <> 0 ? number_format($data->mojan, 2) : '-');
      $mofeb = ($data->mofeb <> 0 ? number_format($data->mofeb, 2) : '-');
      $momar = ($data->momar <> 0 ? number_format($data->momar, 2) : '-');
      $moapr = ($data->moapr <> 0 ? number_format($data->moapr, 2) : '-');
      $momay = ($data->momay <> 0 ? number_format($data->momay, 2) : '-');
      $mojun = ($data->mojun <> 0 ? number_format($data->mojun, 2) : '-');
      $mojul = ($data->mojul <> 0 ? number_format($data->mojul, 2) : '-');
      $moaug = ($data->moaug <> 0 ? number_format($data->moaug, 2) : '-');
      $mosep = ($data->mosep <> 0 ? number_format($data->mosep, 2) : '-');
      $mooct = ($data->mooct <> 0 ? number_format($data->mooct, 2) : '-');
      $monov = ($data->monov <> 0 ? number_format($data->monov, 2) : '-');
      $modec = ($data->modec <> 0 ? number_format($data->modec, 2) : '-');

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode . ' - ' . $data->itemname, '140', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '80', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //brand
      $subjan = $subjan + $data->mojan;
      $subfeb = $subfeb + $data->mofeb;
      $submar = $submar + $data->momar;
      $subapr = $subapr + $data->moapr;
      $submay = $submay + $data->momay;
      $subjun = $subjun + $data->mojun;
      $subjul = $subjul + $data->mojul;
      $subaug = $subaug + $data->moaug;
      $subsep = $subsep + $data->mosep;
      $suboct = $suboct + $data->mooct;
      $subnov = $subnov + $data->monov;
      $subdec = $subdec + $data->modec;
      $subamt = $subamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      //part
      $gsubjan = $gsubjan + $data->mojan;
      $gsubfeb = $gsubfeb + $data->mofeb;
      $gsubmar = $gsubmar + $data->momar;
      $gsubapr = $gsubapr + $data->moapr;
      $gsubmay = $gsubmay + $data->momay;
      $gsubjun = $gsubjun + $data->mojun;
      $gsubjul = $gsubjul + $data->mojul;
      $gsubaug = $gsubaug + $data->moaug;
      $gsubsep = $gsubsep + $data->mosep;
      $gsuboct = $gsuboct + $data->mooct;
      $gsubnov = $gsubnov + $data->monov;
      $gsubdec = $gsubdec + $data->modec;
      $gsubamt = $gsubamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $totalmojan = $totalmojan + $data->mojan;
      $totalmofeb = $totalmofeb + $data->mofeb;
      $totalmomar = $totalmomar + $data->momar;
      $totalmoapr = $totalmoapr + $data->moapr;
      $totalmomay = $totalmomay + $data->momay;
      $totalmojun = $totalmojun + $data->mojun;
      $totalmojul = $totalmojul + $data->mojul;
      $totalmoaug = $totalmoaug + $data->moaug;
      $totalmosep = $totalmosep + $data->mosep;
      $totalmooct = $totalmooct + $data->mooct;
      $totalmonov = $totalmonov + $data->monov;
      $totalmodec = $totalmodec + $data->modec;
      $totalamt = $totalamt + $amt;

      $brand = strtoupper($data->brand);
      $part = strtoupper($data->part);

      if ($i == (count((array)$result) - 1)) {
        goto BrandSubTotalHere;
      }
      $i++;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        // 

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->default_displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '140', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '80', null, false, $border, 'TB', 'R', $font, $font_size9, 'b', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class