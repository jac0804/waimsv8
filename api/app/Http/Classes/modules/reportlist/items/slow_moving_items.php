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


class slow_moving_items
{
  public $modulename = 'Slow Moving Items';
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

    $fields = ['radioprint', 'start', 'end', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'dwhname', 'categoryname', 'subcatname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      case 39: //cbbsi
        $fields = ['radioprint', 'start', 'barcode', 'divsion', 'class', 'dwhname', 'numdays'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.label', 'Balance as of');
        data_set($col1, 'barcode.label', 'Item Code');
        data_set($col1, 'barcode.type', 'input');
        data_set($col1, 'barcode.readonly', false);
        data_set($col1, 'numdays.readonly', false);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');

    $fields = [];
    if ($companyid != 39) $fields = ['year', 'radioposttype', 'radioreportitemtype']; //not cbbsi
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'year.label', 'Top # of Items');
    data_set($col2, 'year.required', false);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);

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
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      0 as itemid,
      '' as ditemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as brandname,
      0 as classid,
      '' as classic,
      '' as categoryname,
      0 as whid,
      '' as wh,
      '' as whname,
      '0' as posttype,
      '(0,1)' as itemtype,
      '' as year,
      '' as divsion,
      '' as brand,
      '' as class,
      '' as category,
      '' as subcat,
      '' as subcatname,
      '' as dwhname,
      '' as numdays,
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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];

    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_layout($config);
        break;
      case 39: //cbbsi
        $result = $this->CBBSI_layout($config);
        break;
      case 40: // CDO
        $this->reportParams['orientation'] = 'p';
        $result = $this->CDO_Layout($config);
        break;
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefaultCBBSI($config)
  {
    $start     = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $barcode   = $config['params']['dataparams']['barcode'];
    $stockgrp  = $config['params']['dataparams']['stockgrp'];
    $class = $config['params']['dataparams']['classic'];
    $wh        = $config['params']['dataparams']['wh'];
    $numdays   = $config['params']['dataparams']['numdays'];

    $filter = '';
    if ($barcode != '') {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($stockgrp != '') {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($class != '') {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != '') {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($numdays != '') {
      $filter .= " and datediff('" . $start . "', head.dateid) >= '" . $numdays . "'";
    }

    $query = "select size, model, tr, groupid, brand, class, whcode, whname, itemname, barcode,
      qty, uom, part, body, category, subcatname, itemid from (
      select item.sizeid as size, 'U' as tr, ifnull(item.groupid,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
      ifnull(parts.part_name,'') as part, ifnull(mm.model_name,'') as model, item.body, ifnull(cc.cl_name,'') as class,
      wh.client as whcode, ifnull(wh.clientname,'') as whname, ifnull(item.itemname,'') as itemname,
      item.barcode, sum(stock.iss) as qty, stock.uom, cat.name as category, subcat.name as subcatname, item.itemid
      from lahead as head left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join part_masterfile as parts on parts.part_id = item.part
      where head.doc in ('sj','sd','se','sf') and date(head.dateid) <= '" . $start . "' and ifnull(item.itemid,'')<>'' " . $filter . " and item.isofficesupplies=0
      group by item.sizeid, item.groupid, frontend_ebrands.brand_desc, parts.part_name, cc.cl_name, wh.client,
      item.model, item.body, wh.clientname, item.barcode, item.itemname, mm.model_name, item.barcode, stock.uom, cat.name, subcat.name, item.itemid

      UNION ALL

      select item.sizeid as size, 'P' as tr, ifnull(item.groupid,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
      ifnull(parts.part_name,'') as part, ifnull(mm.model_name,'') as model, item.body, ifnull(cc.cl_name,'') as class,
      wh.client as whcode, ifnull(wh.clientname,'') as whname, ifnull(item.itemname,'') as itemname,
      item.barcode, sum(stock.iss) as qty, stock.uom, cat.name as category, subcat.name as subcatname, item.itemid
      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.doc in ('sj','sd','se','sf') and date(head.dateid) <= '" . $start . "' and ifnull(item.itemid,'')<>'' " . $filter . " and item.isofficesupplies=0
      group by item.sizeid, item.groupid, frontend_ebrands.brand_desc, parts.part_name, cc.cl_name, wh.client,
      item.model, item.body, wh.clientname, item.barcode, item.itemname, mm.model_name, item.barcode, stock.uom, cat.name , subcat.name, item.itemid) as FM 
      group by size, model, tr, groupid, brand, class, whcode, whname, itemname, barcode, uom, part, body, qty, category, subcatname, itemid
      order by itemname";

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 40: //CDO
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->CDO_QUERY_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->CDO_QUERY_UNPOSTED($config);
            break;
          default:
            $query = $this->CDO_QUERY_ALL($config);
            break;
        }
        break;

      default:
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->default_QUERY_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->default_QUERY_UNPOSTED($config);
            break;
          default: // UNPOSTED
            $query = $this->default_QUERY_ALL($config);
            break;
        }
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function CDO_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    // $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $itemid     = $config['params']['dataparams']['itemid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whid       = $config['params']['dataparams']['whid'];
    $wh         = $config['params']['dataparams']['wh'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $filter .= " and stock.itemid = $itemid";
    }
    if ($groupname != "") {
      $filter .= " and item.groupid = $groupid";
    }
    if ($categoryname != "") {
      $filter .= " and item.category = '$category'";
    }
    if ($subcatname != "") {
      $filter .= " and item.subcat = '$subcat'";
    }
    if ($brandname != "") {
      $filter .= " and item.brand = $brandid";
    }
    if ($classname != "") {
      $filter .= " and item.class = $classid";
    }
    if ($wh != "") {
      $filter .= " and stock.whid = $whid";
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and item.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select *
    from (
      
      SELECT 
      item.itemid,
      item.barcode as stockno,
      item.partno as partno,
      item.body as superceeding,
      item.itemname as partname,
      cat.name as category,
      SUM(stock.iss) AS sold,
      SUM(stock.ext) AS totalext
    
      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.doc in ('sj','mj','sd','se','sf','ci') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
      
      GROUP BY 
      item.itemid,
      item.barcode,item.partno,item.body,
      item.itemname,cat.name
    ) as FM 
    
    order by sold asc $top";

    return $query;
  }

  public function CDO_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $itemid     = $config['params']['dataparams']['itemid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whid       = $config['params']['dataparams']['whid'];
    $wh         = $config['params']['dataparams']['wh'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter = " and item.isimport in $loc";
    $filter1 = "";

    if ($barcode != "") {
      $filter .= " and stock.itemid = $itemid";
    }
    if ($groupname != "") {
      $filter .= " and item.groupid = $groupid";
    }
    if ($categoryname != "") {
      $filter .= " and item.category = '$category'";
    }
    if ($subcatname != "") {
      $filter .= " and item.subcat = '$subcat'";
    }
    if ($brandname != "") {
      $filter .= " and item.brand = $brandid";
    }
    if ($classname != "") {
      $filter .= " and item.class = $classid";
    }
    if ($wh != "") {
      $filter .= " and stock.whid = $whid";
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and item.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select *
    from (
      
      SELECT 
      item.itemid,
      item.barcode as stockno,
      item.partno as partno,
      item.body as superceeding,
      item.itemname as partname,
      cat.name as category,
      SUM(stock.iss) AS sold,
      SUM(stock.ext) AS totalext

    
      from lahead as head left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
    
      GROUP BY 
      item.itemid,
      item.barcode,item.partno,item.body,
      item.itemname,cat.name
      ) as FM 
    order by sold asc $top";

    return $query;
  }

  public function CDO_QUERY_ALL($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $itemid     = $config['params']['dataparams']['itemid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whid       = $config['params']['dataparams']['whid'];
    $wh         = $config['params']['dataparams']['wh'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $filter .= " and stock.itemid = $itemid";
    }
    if ($groupname != "") {
      $filter .= " and item.groupid = $groupid";
    }
    if ($categoryname != "") {
      $filter .= " and item.category = '$category'";
    }
    if ($subcatname != "") {
      $filter .= " and item.subcat = '$subcat'";
    }
    if ($brandname != "") {
      $filter .= " and item.brand = $brandid";
    }
    if ($classname != "") {
      $filter .= " and item.class = $classid";
    }
    if ($wh != "") {
      $filter .= " and stock.whid = $whid";
    }
    if ($top != "") {
      $top = " limit " . $top . "";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and item.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }
    $query = "
    select itemid,
    stockno,partno,superceeding,
    partname,category,sum(sold) as sold,sum(totalext) as totalext
    from (
      SELECT 
      item.itemid,
      item.barcode as stockno,
      item.partno as partno,
      item.body as superceeding,
      item.itemname as partname,
      cat.name as category,
      SUM(stock.iss) AS sold,
      SUM(stock.ext) AS totalext

      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0

      GROUP BY 
      item.itemid,
      item.barcode,item.partno,item.body,
      item.itemname,cat.name


      union all

      SELECT 
      item.itemid,
      item.barcode as stockno,
      item.partno as partno,
      item.body as superceeding,
      item.itemname as partname,
      cat.name as category,
      SUM(stock.iss) AS sold,
      SUM(stock.ext) AS totalext

      from lahead as head left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0

      GROUP BY 
      item.itemid,
      item.barcode,item.partno,item.body,
      item.itemname,cat.name

    ) as FM
    
    GROUP BY 
    itemid,
    stockno,partno,superceeding,
    partname,category

    order by sold asc  $top;";
    return $query;
  }

  public function default_QUERY_POSTED($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];
    $companyid    = $config['params']['companyid'];

    $isqty        = "stock.iss";
    $filter = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter = $filter . " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat =  $config['params']['dataparams']['subcat'];
      $filter = $filter . " and item.subcat='$subcat'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
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

    $query = "select size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body,category,subcatname
      from (select item.sizeid as size,'P' as tr, ifnull(item.groupid,'') as groupid, 
      ifnull(frontend_ebrands.brand_desc,'') as brand,
      ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
      ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
      ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom,
      cat.name as category, subcat.name as subcatname
      from glhead as head 
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on frontend_ebrands.brandid = item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
      group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
      item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
      item.barcode,stock.uom,cat.name , subcat.name) as FM 
      group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,uom,part,body,qty,category,subcatname
      order by qty asc" . $top;

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];
    $companyid    = $config['params']['companyid'];

    $isqty   = "stock.iss";
    $filter  = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter = $filter . " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat =  $config['params']['dataparams']['subcat'];
      $filter = $filter . " and item.subcat='$subcat'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
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

    $query = "select size,model,tr,groupid,brand,class,whcode,
      whname,itemname,barcode,qty,uom,part,body,category,subcatname
      from (select item.sizeid as size, 'U' as tr, ifnull(item.groupid,'') as groupid, ifnull(item.brand,'') as brand,
      ifnull(item.part,'') as part,ifnull(mm.model_name,'') as model,item.body,
      ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
      ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom,
      cat.name as category, subcat.name as subcatname
      from lahead as head 
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join model_masterfile as mm on mm.model_id = item.model
      left join item_class as cc on cc.cl_id = item.class
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
      group by item.sizeid,item.groupid, item.brand,cc.cl_name,wh.client,
      item.part,item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
      item.barcode,stock.uom,cat.name , subcat.name) as FM 
      group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body,category,subcatname
      order by qty asc" . $top;

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];
    $companyid    = $config['params']['companyid'];

    $isqty   = "stock.iss";
    $filter  = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter = $filter . " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat =  $config['params']['dataparams']['subcat'];
      $filter = $filter . " and item.subcat='$subcat'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
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

    $query = "
    select size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body,category,subcatname
    from (
    select item.sizeid as size,'P' as tr, ifnull(item.groupid,'') as groupid,
    ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom,
    cat.name as category, subcat.name as subcatname
    from glhead as head 
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join item_class as cc on cc.cl_id = item.class
    left join part_masterfile as parts on parts.part_id = item.part
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join cntnum on cntnum.trno=head.trno
    left join client as wh on wh.clientid=stock.whid
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
    and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
    group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
    item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,stock.uom,cat.name , subcat.name

    UNION ALL

    select item.sizeid as size, 'U' as tr, ifnull(item.groupid,'') as groupid,   ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom,
    cat.name as category, subcat.name as subcatname
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join item_class as cc on cc.cl_id = item.class
    left join part_masterfile as parts on parts.part_id = item.part
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join cntnum on cntnum.trno=head.trno
    left join client as wh on wh.clientid=stock.whid
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
    and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
    group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
    item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,stock.uom,cat.name , subcat.name ) as FM
    group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,uom,part,body,qty,category,subcatname
    order by qty asc" . $top;
    
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
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
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
    } else {
      $posttype = 'Unposted';
    }

    switch ($itemtype) {
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
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

    $str .= $this->reporter->col('SLOW MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . $barcode, '300', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $wh, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function MAJESTY_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRODUCT CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRODUCT DESCRIPTION', '500', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');

    return $str;
  }

  public function MAJESTY_layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);
    $str .= $this->MAJESTY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $item_desc = $data->itemname;

      if ($data->model != "") {
        $item_desc = $item_desc . " " . $data->model;
      } //end if

      if ($data->size != "") {
        $item_desc = $item_desc . " " . $data->size;
      } //end if        

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($item_desc, '500', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->endrow();

    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function cbbsi_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $classname = $config['params']['dataparams']['classic'];
    $groupname = $config['params']['dataparams']['stockgrp'];
    $whname = $config['params']['dataparams']['whname'];
    $numdays = $config['params']['dataparams']['numdays'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br /><br />';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SLOW MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of: ' . $start, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group: ' . ($groupname == '' ? 'ALL' : $groupname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WH: ' . ($whname == '' ? 'ALL' : $whname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class: ' . ($classname == '' ? 'ALL' : $classname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('No of days: ' . $numdays, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function cbbsi_tablecols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $font_size = '10';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LAST TRANS DATE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LAST DOCNO', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COUNT', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    return $str;
  }

  public function CBBSI_layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;

    $result = $this->reportDefaultCBBSI($config);

    $count = 39;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) return $this->othersClass->emptydata($config);

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->cbbsi_displayHeader($config);
    $str .= $this->cbbsi_tablecols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    foreach ($result as $key => $data) {
      $lasttrans = $this->getlasttrans($data, $config);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(isset($lasttrans[0]) ? $lasttrans[0]->dateid : '', '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(isset($lasttrans[0]) ? $lasttrans[0]->docno : '', '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->cbbsi_tablecols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $str .= $this->reporter->begintable($layoutsize);
        $page += $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function CDO_displayHeader($config)
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
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
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

    switch ($itemtype) {
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
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

    $str .= $this->reporter->col('SLOW MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . $barcode, '300', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $wh, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function CDO_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('#', '30', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STOCK #', '100', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PART NUMBER', '130', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SUPERCEEDING', '100', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');

    $str .= $this->reporter->col('PART NAME', '290', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CATEGORY', '70', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '70', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SOLD', '105', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '105', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');

    return $str;
  }

  public function CDO_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    // $fontsize11 = 11;

    $result = $this->reportDefault($config);

    $pagecount = 39;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->CDO_displayHeader($config);
    $str .= $this->CDO_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);

    $grandtotal = 0;
    $count = 0;
    foreach ($result as $key => $data) {
      $count++;
      $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($count, '30', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->stockno, '100', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->partno, '130', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->superceeding, '100', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->partname, '290', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->category, '70', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($this->getBal($config, $data->itemid), 2), '70', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->sold, 2), '105', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->totalext, 2), '105', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');

      $grandtotal += $data->totalext;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->CDO_displayHeader($config);
        }
        $str .= $this->CDO_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);
        $str .= $this->reporter->begintable($layoutsize);

        $page = $page + $pagecount;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

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
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
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

    switch ($itemtype) {
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
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

    $str .= $this->reporter->col('SLOW MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . $barcode, '300', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $wh, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRODUCT CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRODUCT DESCRIPTION', '500', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', 'Verdana', $fontsize, 'B', '', '', '');

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    // $fontsize11 = 11;

    $result = $this->reportDefault($config);

    $count = 39;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $item_desc = $data->itemname;

      if ($data->brand != "") {
        $item_desc = $data->brand . " " . $item_desc;
      } //end if

      if ($data->model != "") {
        $item_desc = $item_desc . " " . $data->model;
      } //end if

      if ($data->size != "") {
        $item_desc = $item_desc . " " . $data->size;
      } //end if        

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($item_desc, '500', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->default_displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function getlasttrans($data, $config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $numdays = $config['params']['dataparams']['numdays'];

    $filter = '';
    if ($numdays != '') $filter .= " and datediff('" . $start . "', head.dateid) >= '" . $numdays . "'";

    $qry = "select docno, dateid from (
      select head.docno, head.dateid from lahead as head left join lastock as stock on stock.trno=head.trno
        where head.doc in ('sj', 'sd', 'se', 'sf') and date(head.dateid) <= '" . $start . "' and stock.itemid=" . $data->itemid . "
      group by docno, dateid
      union all
      select head.docno, head.dateid from glhead as head left join glstock as stock on stock.trno=head.trno
        where head.doc in ('sj', 'sd', 'se', 'sf') and date(head.dateid) <= '" . $start . "' and stock.itemid=" . $data->itemid . "
      group by docno, dateid
      union all
      select head.docno, head.dateid from lahead as head left join lastock as stock on stock.trno=head.trno
        where head.doc = 'dr' and date(head.dateid) <= '" . $start . "' and stock.itemid=" . $data->itemid . " " . $filter . "
      group by docno, dateid
      union all
      select head.docno, head.dateid from glhead as head left join glstock as stock on stock.trno=head.trno
        where head.doc = 'dr' and date(head.dateid) <= '" . $start . "' and stock.itemid=" . $data->itemid . " " . $filter . "
      group by docno, dateid
    ) as t group by docno, dateid order by dateid desc limit 1";
    return $this->coreFunctions->opentable($qry);
  }

  public function getBal($config, $itemid)
  {
    $wh = $config['params']['dataparams']['wh'];
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    if ($wh != '') $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $whfilter = "";
    $asof = " and head.dateid<='$end'";
    $qry = "select sum(qty-iss) as value from (
      select stock.qty, stock.iss from lahead as head left join lastock as stock on stock.trno=head.trno where stock.itemid=" . $itemid . " " . $whfilter . " $asof
      union all
      select stock.qty, stock.iss from glhead as head left join glstock as stock on stock.trno=head.trno where stock.itemid=" . $itemid . " " . $whfilter . " $asof
    ) as t";
    $bal = $this->coreFunctions->datareader($qry);
    if (empty($bal)) $bal = 0;
    return $bal;
  }
}//end class