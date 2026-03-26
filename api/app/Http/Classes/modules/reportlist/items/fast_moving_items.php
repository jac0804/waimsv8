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


class fast_moving_items
{
  public $modulename = 'Fast Moving Items';
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
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'divsion.label', 'Group');
    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['year', 'radioposttype', 'radioreportitemtype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'year.label', 'Top # of Items');
    data_set($col2, 'year.required', true);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'orange'],
      ['label' => 'All', 'value' => '2', 'color' => 'orange']
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
      left(adddate(now(),-30),10) as start,
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
      '10' as year,
      '' as divsion,
      '' as brand,
      '' as class,
      '' as category,
      '' as subcatname,
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
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
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid       = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid      = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category     = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat       = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat= '$subcat'";
    }
    if ($brandname != '') {
      $brandid      = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid      = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid         = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = "";
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

      from glhead as head 
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category

      where head.doc in ('sj','mj','sd','se','sf','ci') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'') <>'' $filter and item.isofficesupplies=0
      GROUP BY 
      item.itemid,
      item.barcode,item.partno,item.body,
      item.itemname,cat.name
    ) as FM 
    order by sold desc $top";;
    return $query;
  }

  public function CDO_QUERY_UNPOSTED($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid       = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid      = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category     = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat       = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat= '$subcat'";
    }
    if ($brandname != '') {
      $brandid      = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid      = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid         = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = "";
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

      from lahead as head 
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category

      where head.doc in ('sj','mj','sd','se','sf','ci') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' $filter and item.isofficesupplies=0

      GROUP BY 
      item.itemid,
      item.barcode,item.partno,item.body,
      item.itemname,cat.name
    ) as FM 
    order by sold desc $top";

    return $query;
  }

  private function CDO_QUERY_ALL($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $wh           = $config['params']['dataparams']['wh'];
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid       = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid      = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category     = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat       = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat= '$subcat'";
    }
    if ($brandname != '') {
      $brandid      = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid      = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid         = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = "";
    }

    $selectedfields = "item.itemid, item.barcode as stockno, item.partno as partno, item.body as superceeding, 
    item.itemname as partname, cat.name as category, SUM(stock.iss) AS sold, SUM(stock.ext) AS totalext";

    $query = "select itemid, stockno,partno,superceeding, partname,category,sum(sold) as sold,sum(totalext) as totalext
    FROM (
      SELECT " . $selectedfields . "
      FROM lahead as head 
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category
      WHERE head.doc in ('sj','mj','sd','se','sf','ci') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'')<>'' " . $filter . " and item.isofficesupplies=0
      GROUP BY item.itemid, item.barcode,item.partno,item.body, item.itemname,cat.name

      UNION ALL

      SELECT " . $selectedfields . "
      FROM glhead as head 
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category
      WHERE head.doc in ('sj','mj','sd','se','sf','ci') and date(head.dateid) between '$start' and '$end' 
      and ifnull(item.itemid,'') <>'' " . $filter . " and item.isofficesupplies=0
      GROUP BY item.itemid, item.barcode,item.partno,item.body, item.itemname,cat.name
    ) as FM
    
    GROUP BY itemid, stockno,partno,superceeding, partname,category
    ORDER BY sold desc $top ";

    return $query;
  }

  public function default_QUERY_POSTED($config)
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
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];

    $isqty = "stock.iss";
    $filter = " and item.isimport in $loc";
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
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    $filter1 .= "";
    $query = "select size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body,category,subcatname 
    from (
    select item.sizeid as size,'P' as tr, ifnull(item.groupid,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,item.uom,
    cat.name as category, subcat.name as subcatname
   
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
    
    where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
    and ifnull(item.itemid,'')<>'' $filter $filter1 and item.isofficesupplies=0
    
    group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
    item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,item.uom,cat.name , subcat.name) as FM 
    group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,uom,part,body,qty,category,subcatname
    order by qty desc $top";;
    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
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
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];

    $isqty = "stock.iss";
    $filter = " and item.isimport in $loc";
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
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    $query = "select size,model,tr,groupid,brand,class,whcode,
    whname,itemname,barcode,qty,uom,part,body,category,subcatname
    from (
    select item.sizeid as size, 'U' as tr, ifnull(item.groupid,'') as groupid, ifnull(item.brand,'') as brand,
    ifnull(item.part,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,item.uom,
    cat.name as category, subcat.name as subcatname
    
    from lahead as head left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid 
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
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
    item.barcode,item.uom,cat.name , subcat.name) as FM 
    
    group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body,category,subcatname
    order by qty desc $top";

    return $query;
  }

  private function default_QUERY_ALL($config)
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
    $loc          = $config['params']['dataparams']['itemtype'];
    $top          = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $isqty = "stock.iss";
    $filter = " and item.isimport in $loc";
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
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
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
        $filter1 .= " and stock.projectid=" . $projectid;
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
    from (
    select item.sizeid as size,'P' as tr, ifnull(item.groupid,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,item.uom,
    cat.name as category, subcat.name as subcatname
    from lahead as head left join 
    lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as mm on mm.model_id = item.model
    left join part_masterfile as parts on parts.part_id = item.part
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join item_class as cc on cc.cl_id = item.class
    left join cntnum on cntnum.trno=head.trno
    left join client as wh on wh.clientid=stock.whid
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
    and ifnull(item.itemid,'')<>'' $filter $filter1  and item.isofficesupplies=0
    group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
    item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,item.uom,cat.name , subcat.name

    UNION ALL

    select item.sizeid as size,'P' as tr, ifnull(item.groupid,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,item.uom,
    cat.name as category, subcat.name as subcatname
    from glhead as head 
    left join glstock as stock on stock.trno=head.trno
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
    where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
    and ifnull(item.itemid,'') <>'' $filter $filter1  and item.isofficesupplies=0
    group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
    item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,item.uom,cat.name , subcat.name
    ) as FM
    group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body,category,subcatname
    order by qty desc $top ";

    return $query;
  }

  private function CDO_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center    = $config['params']['center'];
    $username  = $config['params']['user'];

    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $whname       = $config['params']['dataparams']['whname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

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
    $str .= $this->reporter->col('FAST MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

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


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $whname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }


      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function CDO_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')

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
    $fontsize11 = 11;

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

    $str .= $this->CDO_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
    
    $count = 0;
    $grandtotal = 0;
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
      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->CDO_displayHeader($config);
        }
        $str .= $this->CDO_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $pagecount;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('', '290', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '105', null, false, $border, 'T', 'L', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '105', null, false, $border, 'T', 'R', 'Verdana', $font_size, 'B', '', '', '');
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
    $str .= $this->reporter->col('FAST MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

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
      $str .= $this->reporter->col('', '100', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $wh, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }


      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
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
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
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
    $fontsize11 = 11;

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
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

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
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
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

  public function getBal($config, $itemid)
  {
    $wh = $config['params']['dataparams']['wh'];
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $whid = '';
    if ($wh != '') $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $whfilter = "";
    if ($whid != '') {      
      $whfilter = " and stock.whid='" . $whid . "'";
    }
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