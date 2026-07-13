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

class monthly_analyze_item_sales
{
  public $modulename = 'Monthly Analyze Item Sales';
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

    $fields = ['radioprint', 'dclientname', 'ditemname', 'divsion', 'brandname', 'brandid', 'categoryname', 'subcatname', 'part', 'dwhname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname', 'industry');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        break;
      case 23: //labsol cebu
      case 52: //technolab
        array_push($fields, 'luom', 'agentname');
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'luom.action', 'replookupuom');
        data_set($col1, 'agentname.type', 'lookup');
        data_set($col1, 'agentname.action', 'repagentmulti');
        break;
      case 41: //labsol mla
        array_push($fields, 'luom');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'luom.action', 'replookupuom');
        break;
      case 59: //roosevelt
        array_push($fields, 'area');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'area.readonly', true);
        break;
      case 60: //transpower
        $fields = ['radioprint', 'dclientname', 'ditemname', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'part', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    unset($col1['divsion']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'part.name', 'partname');

    $fields = ['year', 'radioposttype', 'radioreportanalyzedby', 'radioreportitemtype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);
    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function getclientmulti($config)
  {
    return 'A';
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $analyzedby = "value";
    if ($config['params']['companyid'] == 60) { //transpower
      $analyzedby = "unit";
    }
    $center = $config['params']['center'];
    $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
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
          '' as categoryname,
          '' as brandname,
          0 as partid,
          '' as partname,
          0 as whid,
          '' as wh,
          '' as whname,
          '' as uom,
          left(now(),4) as year,
          '0' as posttype,
          '$analyzedby' as analyzedby,
          '(0,1)' as itemtype,
          '' as dclientname,
          '' as ditemname,
          '' as divsion,
          '' as brand,
          '' as category,
          '' as subcatname,
          '' as subcat,
          '' as part,
          '' as agent,
          0 as agentid,
          '' as agentname,
          '' as dwhname,
          '' as project, 
          0 as projectid, 
          '' as projectname,
          0 as deptid,
          '' as ddeptname, 
          '' as dept, 
          '' as deptname,
          '' as industry,
          '' as area,
          '' as modelid, '' as modelname, 
          '' as classic, '' as classid";

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
    ini_set('memory_limit', '-1');
    switch ($companyid) {
      case 15: //nathina
        $result = $this->nathina_Layout($config);
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
        $result = $this->reportDefaultLayoutLabsol($config);
        break;
      case 52: //technolab
        $result = $this->reportDefaultLayoutTechnolab($config);
        break;
      case 59: //roosevelt
        $result = $this->reportDefaultLayoutRoosevelt($config);
        break;
      case 60: //transpower
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];
        $result = $this->reportDefaultLayoutTranspower($config);
        break;
      case 29: // sbc
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];
        $result = $this->reportDefaultLayoutSbc($config);
        break;
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }
    return $result;
  }

  public function reportDefault($config)
  {

    $companyid = $config['params']['companyid'];
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($companyid) {
      case 60:
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->TRANSPOWER_QUERY_POSTED($config);
            break;
          case '1':
            $query = $this->TRANSPOWER_QUERY_UNPOSTED($config);
            break;
          default:
            $query = $this->TRANSPOWER_QUERY_ALL($config);
            break;
        }
        break;
      default:
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
        break;
    }



    return $this->coreFunctions->opentable($query);
  }

  public function TRANSPOWER_ALL($config, $itemid)
  {
    $companyid = $config['params']['companyid'];
    $wh = $config['params']['dataparams']['wh'];
    $whid = $config['params']['dataparams']['whid'];
    $year = $config['params']['dataparams']['year'];

    $filter = '';

    if ($wh != "") {
      $filter .= " and stock.whid= '$whid'";
    }

    $posted = "select sum(x.qty - x.iss)  as balance
        from (select sum(stock.qty) as qty,
        sum(stock.iss) as iss
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        where year(head.dateid)=$year and stock.itemid = $itemid $filter
        union all
        select sum(stock.qty) as qty,
        sum(stock.iss) as iss
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        where year(head.dateid)=$year and stock.itemid = $itemid $filter
        ) as x";
    return $posted;
  }

  public function TRANSPOWER_QUERY_POSTED($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $partname     = $config['params']['dataparams']['partname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $wh = $config['params']['dataparams']['wh'];
    $whid = $config['params']['dataparams']['whid'];
    $classic = $config['params']['dataparams']['classic'];
    $classid = $config['params']['dataparams']['classid'];
    $modelname = $config['params']['dataparams']['modelname'];
    $modelid = $config['params']['dataparams']['modelid'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

    $filter = '';
    $filter1 = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh == '') {
      $center = $config['params']['center'];
      $minmax = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
    }
    if ($wh != "") {
      $filter .= " and stock.whid= '$whid'";
      $minmax = $wh;
    }

    if ($analyzedby == "unit") {
      $war = "stock.iss";
    } else {
      $war = "stock.ext";
    }
    $filter1 = "";

    if ($classic != "") {
      $filter1 .= " and ic.cl_id=" . $classid;
    }
    if ($modelname != "") {
      $filter1 .= " and mm.model_id=" . $modelid;
    }


    $query = "select 
      ifnull(max(x.classname),'') as classname, ifnull(max(x.itemmin),0) as itemmin, ifnull(max(x.itemmax),0) as itemmax, x.itemid,
      max(barcode) as barcode, max(size) as size, max(uom) as uom, max(category) as category, max(groupid) as groupid,
      max(category1) as category1, max(subcatname) as subcatname, max(part) as part, max(brand) as brand,
      max(model) as model, max(body) as body, upper(max(itemname)) as itemname, max(yr) as yr,
      sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
      select item.barcode, client.clientname, item.sizeid as size,'p' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc,'NO BRAND') as brand, 
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr, item.category,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec, 
      item.uom,ifnull(ic.cl_name,'') as classname, ifnull(itlevel.min,0) as itemmin, ifnull(itlevel.max,0) as itemmax, sum(stock.qty) as qty,  sum(stock.iss) as iss, 
      item.itemid , stock.whid
      from ((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join client on client.clientid=head.clientid)
      join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.clientid = head.agentid
      left join item_class as ic on ic.cl_id = item.class  
      left join itemlevel as itlevel on itlevel.itemid = item.itemid and itlevel.center='$minmax'
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by 
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname, 
      item.uom ,ic.cl_name, itlevel.min, itlevel.max, item.itemid , stock.whid) as x
      group by x.itemid, yr
      order by part, brand, itemname, barcode";
    return $query;
  }

  public function TRANSPOWER_QUERY_UNPOSTED($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $partname     = $config['params']['dataparams']['partname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $wh = $config['params']['dataparams']['wh'];
    $whid = $config['params']['dataparams']['whid'];
    $classic = $config['params']['dataparams']['classic'];
    $classid = $config['params']['dataparams']['classid'];
    $modelname = $config['params']['dataparams']['modelname'];
    $modelid = $config['params']['dataparams']['modelid'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

    $filter = '';
    $filter1 = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh == '') {
      $center = $config['params']['center'];
      $minmax = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
    }
    if ($wh != "") {
      $filter .= " and stock.whid= '$whid'";
      $minmax = $wh;
    }

    if ($analyzedby == "unit") {
      $war = "stock.iss";
    } else {
      $war = "stock.ext";
    }
    $filter1 = "";

    if ($classic != "") {
      $filter1 .= " and ic.cl_id=" . $classid;
    }
    if ($modelname != "") {
      $filter1 .= " and mm.model_id=" . $modelid;
    }


    $query = "select 
      ifnull(max(x.classname),'') as classname, ifnull(max(x.itemmin),0) as itemmin, ifnull(max(x.itemmax),0) as itemmax, x.itemid,
      max(barcode) as barcode, max(size) as size, max(uom) as uom, max(category) as category, max(groupid) as groupid,
      max(category1) as category1, max(subcatname) as subcatname, max(part) as part, max(brand) as brand,
      max(model) as model, max(body) as body, upper(max(itemname)) as itemname, max(yr) as yr,
      sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
      select item.barcode, client.clientname, item.sizeid as size,'u' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand, 
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, 
      item.uom ,ifnull(ic.cl_name,'') as classname, ifnull(itlevel.min,0) as itemmin, ifnull(itlevel.max,0) as itemmax,sum(stock.qty) as qty, sum(stock.iss) as iss, 
      item.itemid,  stock.whid
      from ((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client)
       join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.client = head.agent
      left join item_class as ic on ic.cl_id = item.class  
      left join itemlevel as itlevel on itlevel.itemid = item.itemid and itlevel.center='$minmax'
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by 
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname,
      item.uom  ,ic.cl_name, itlevel.min, itlevel.max, item.itemid, stock.whid) as x
      group by x.itemid, yr
      order by part, brand, itemname, barcode";
    return $query;
  }

  private function TRANSPOWER_QUERY_ALL($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $partname     = $config['params']['dataparams']['partname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $wh = $config['params']['dataparams']['wh'];
    $whid = $config['params']['dataparams']['whid'];
    $classic = $config['params']['dataparams']['classic'];
    $classid = $config['params']['dataparams']['classid'];
    $modelname = $config['params']['dataparams']['modelname'];
    $modelid = $config['params']['dataparams']['modelid'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];

    $filter = '';
    $filter1 = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh == '') {
      $center = $config['params']['center'];
      $minmax = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
    }
    if ($wh != "") {
      $filter .= " and stock.whid= '$whid'";
      $minmax = $wh;
    }

    if ($analyzedby == "unit") {
      $war = "stock.iss";
    } else {
      $war = "stock.ext";
    }
    $filter1 = "";

    if ($classic != "") {
      $filter1 .= " and ic.cl_id=" . $classid;
    }
    if ($modelname != "") {
      $filter1 .= " and mm.model_id=" . $modelid;
    }

    $query = "select 
      ifnull(max(x.classname),'') as classname, ifnull(max(x.itemmin),0) as itemmin, ifnull(max(x.itemmax),0) as itemmax, x.itemid,
      max(barcode) as barcode, max(size) as size, max(uom) as uom, max(category) as category, max(groupid) as groupid,
      max(category1) as category1, max(subcatname) as subcatname, max(part) as part, max(brand) as brand,
      max(model) as model, max(body) as body, upper(max(itemname)) as itemname, max(yr) as yr,
      sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
      select item.barcode, client.clientname, item.sizeid as size,'p' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc,'NO BRAND') as brand, 
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr, item.category,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec, 
      item.uom,ifnull(ic.cl_name,'') as classname, ifnull(itlevel.min,0) as itemmin, ifnull(itlevel.max,0) as itemmax, sum(stock.qty) as qty,  sum(stock.iss) as iss, 
      item.itemid , stock.whid
      from ((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join client on client.clientid=head.clientid)
      join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.clientid = head.agentid
      left join item_class as ic on ic.cl_id = item.class  
      left join itemlevel as itlevel on itlevel.itemid = item.itemid and itlevel.center='$minmax'
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by 
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname, 
      item.uom ,ic.cl_name, itlevel.min, itlevel.max, item.itemid , stock.whid
      UNION ALL

      select item.barcode, client.clientname, item.sizeid as size,'u' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand, 
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, 
      item.uom ,ifnull(ic.cl_name,'') as classname, ifnull(itlevel.min,0) as itemmin, ifnull(itlevel.max,0) as itemmax,sum(stock.qty) as qty, sum(stock.iss) as iss, 
      item.itemid,  stock.whid
      from ((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client)
       join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.client = head.agent
      left join item_class as ic on ic.cl_id = item.class  
      left join itemlevel as itlevel on itlevel.itemid = item.itemid and itlevel.center='$minmax'
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by 
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname,
      item.uom  ,ic.cl_name, itlevel.min, itlevel.max, item.itemid, stock.whid) as x
      group by x.itemid, yr
      order by part, brand, itemname, barcode";
    return $query;
  }

  public function DEFAULT_QUERY_POSTED($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $partname     = $config['params']['dataparams']['partname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $wh           = $config['params']['dataparams']['wh'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];
    $agent        = $config['params']['dataparams']['agent'];
    $agentid        = $config['params']['dataparams']['agentid'];

    $filter = '';
    $filter1 = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($analyzedby == "unit") {
      $war = "stock.iss";
    } else {
      $war = "stock.ext";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];
      $indus = $config['params']['dataparams']['industry'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and head.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
      if ($indus != "") {
        $filter1 .= " and client.industry='$indus'";
      }
    } else if ($companyid == 59) { //roosevelt
      $area = $config['params']['dataparams']['area'];
      $filter1 .= " and client.area='" . $area . "'";
    } else {
      $filter1 = "";
    }


    $classid = '';
    $modelid = '';
    $addfields = '';
    $leftjoin_class = '';
    $transpowerfield = '';
    $gtranspowerfield = '';
    $transpowerfield2 = '';

    if ($companyid == 60) {
      $classid = $config['params']['dataparams']['classid'];
      $modelid = $config['params']['dataparams']['modelid'];
      $whid      = $config['params']['dataparams']['wh'];

      $leftjoin_class = 'left join item_class as ic on ic.cl_id = item.class  
    left join itemlevel as itlevel on itlevel.itemid = item.itemid';

      if ($whid != '') {
        $transpowerfield  = ',ic.cl_name as classname, itlevel.min as itemmin, itlevel.max as itemmax, sum(stock.iss) as iss, item.itemid, stock.whid';
        $transpowerfield2 = 'x.classname, x.itemmin, x.itemmax, x.itemid, x.whid,';
        $addfields        = '(select sum(bal) from rrstatus where itemid = x.itemid and whid = x.whid) - sum(x.iss) as balance,';
        $gtranspowerfield = ',ic.cl_name, itlevel.min, itlevel.max, item.itemid, stock.whid';
      } else {
        $transpowerfield  = ',ic.cl_name as classname, itlevel.min as itemmin, itlevel.max as itemmax, sum(stock.iss) as iss, item.itemid';
        $transpowerfield2 = 'x.classname, x.itemmin, x.itemmax, x.itemid,';
        $addfields        = '(select sum(bal) from rrstatus where itemid = x.itemid) - sum(x.iss) as balance,';
        $gtranspowerfield = ',ic.cl_name, itlevel.min, itlevel.max, item.itemid';
      }

      if ($classid != "") {
        $filter1 .= " and ic.cl_id=" . $classid;
      }
      if ($modelid != "") {
        $filter1 .= " and mm.model_id=" . $modelid;
      }
    }

    $agfield = '';
    $agfield2 = '';
    $grpagent = '';
    if ($companyid == 23) {
      $agfield = "agentname, ";
      $agfield2 = "ifnull(agent.clientname, '') as agentname, ";
      $grpagent = "agent.clientname, ";

      if ($agentid != "") {
        $agentid = str_replace("~", ",", $config['params']['dataparams']['agentid']);
        $filter1 .= " and agent.clientid in (" . $agentid . ")";
      }
    }
    $sort = "order by $agfield part, brand, barcode, itemname";
    if ($companyid == 60) {
      $sort = "order by part, brand, itemname, barcode";
    }

    $query = "select $agfield $transpowerfield2  $addfields  barcode,size,uom,category,groupid,category1, subcatname, part, brand, model,body, itemname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
      select $agfield2 item.barcode, client.clientname, item.sizeid as size,'p' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc,'NO BRAND') as brand, 
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr, item.category,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec, item.uom $transpowerfield
      from ((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join client on client.clientid=head.clientid)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.clientid = head.agentid
      $leftjoin_class
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname, item.uom $gtranspowerfield) as x
      group by $agfield $transpowerfield2 part, brand, barcode, size, category, groupid,  model,body, itemname, yr,category1,subcatname, uom
      $sort";
    // var_dump($query);
    return $query;
  }

  public function DEFAULT_QUERY_UNPOSTED($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $partname     = $config['params']['dataparams']['partname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $wh           = $config['params']['dataparams']['wh'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];
    $agent        = $config['params']['dataparams']['agent'];
    $agentid        = $config['params']['dataparams']['agentid'];

    $filter = '';
    $filter1 = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($analyzedby == "unit") {
      $war = "stock.iss";
    } else {
      $war = "stock.ext";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];
      $indus = $config['params']['dataparams']['industry'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and head.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
      if ($indus != "") {
        $filter1 .= " and client.industry='$indus'";
      }
    } else if ($companyid == 59) { //roosevelt
      $area = $config['params']['dataparams']['area'];
      $filter1 .= " and client.area='" . $area . "'";
    } else {
      $filter1 = "";
    }

    $agfield = '';
    $agfield2 = '';
    $grpagent = '';
    if ($companyid == 23) {
      $agfield = "agentname, ";
      $agfield2 = "ifnull(agent.clientname, '') as agentname, ";
      $grpagent = "agent.clientname, ";

      if ($agentid != "") {
        $agentid = str_replace("~", ",", $config['params']['dataparams']['agentid']);
        $filter1 .= " and agent.clientid in (" . $agentid . ")";
      }
    }

    $sort = "order by $agfield part, brand, barcode, itemname";
    if ($companyid == 60) { //transpower
      $sort = "order by itemname,barcode,part, brand";
    }


    $classid = '';
    $modelid = '';
    $addfields = '';
    $leftjoin_class = '';
    $transpowerfield = '';
    $gtranspowerfield = '';
    $transpowerfield2 = '';

    if ($companyid == 60) {
      $classid = $config['params']['dataparams']['classid'];
      $modelid = $config['params']['dataparams']['modelid'];
      $whid      = $config['params']['dataparams']['wh'];

      $leftjoin_class = 'left join item_class as ic on ic.cl_id = item.class  
    left join itemlevel as itlevel on itlevel.itemid = item.itemid';

      if ($whid != '') {
        $transpowerfield  = ',ic.cl_name as classname, itlevel.min as itemmin, itlevel.max as itemmax, sum(stock.iss) as iss, item.itemid, stock.whid';
        $transpowerfield2 = 'x.classname, x.itemmin, x.itemmax, x.itemid, x.whid,';
        $addfields        = '(select sum(bal) from rrstatus where itemid = x.itemid and whid = x.whid) - sum(x.iss) as balance,';
        $gtranspowerfield = ',ic.cl_name, itlevel.min, itlevel.max, item.itemid, stock.whid';
      } else {
        $transpowerfield  = ',ic.cl_name as classname, itlevel.min as itemmin, itlevel.max as itemmax, sum(stock.iss) as iss, item.itemid';
        $transpowerfield2 = 'x.classname, x.itemmin, x.itemmax, x.itemid,';
        $addfields        = '(select sum(bal) from rrstatus where itemid = x.itemid) - sum(x.iss) as balance,';
        $gtranspowerfield = ',ic.cl_name, itlevel.min, itlevel.max, item.itemid';
      }

      if ($classid != "") {
        $filter1 .= " and ic.cl_id=" . $classid;
      }
      if ($modelid != "") {
        $filter1 .= " and mm.model_id=" . $modelid;
      }
    }

    $query = "select $agfield $transpowerfield2 $addfields barcode, size, uom,category, groupid, part, brand,category1, subcatname, model,body, 
    itemname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
      select $agfield2 item.barcode, client.clientname, item.sizeid as size,'u' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid, 
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand, 
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, item.uom $transpowerfield
      from ((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid 
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join client as agent on agent.client = head.agent
      left join itemsubcategory as subcat on subcat.line = item.subcat
      $leftjoin_class
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname,item.uom $gtranspowerfield) as x 
      group by $agfield $transpowerfield2 part, brand, barcode, size, category, groupid,  model,body, itemname, yr,category1,subcatname,uom
      $sort";

    return $query;
  }

  private function DEFAULT_QUERY_ALL($config)
  {
    $companyid    = $config['params']['companyid'];
    $client       = $config['params']['dataparams']['client'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $partname     = $config['params']['dataparams']['partname'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   =  $config['params']['dataparams']['subcatname'];
    $wh           = $config['params']['dataparams']['wh'];
    $year         = $config['params']['dataparams']['year'];
    $analyzedby   = $config['params']['dataparams']['analyzedby'];
    $itemtype     = $config['params']['dataparams']['itemtype'];
    $agent        = $config['params']['dataparams']['agent'];
    $agentid        = $config['params']['dataparams']['agentid'];

    $filter = '';
    $filter1 = '';
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    if ($analyzedby == "unit") {
      $war = "stock.iss";
    } else {
      $war = "stock.ext";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];
      $indus = $config['params']['dataparams']['industry'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and head.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
      if ($indus != "") {
        $filter1 .= " and client.industry='$indus'";
      }
    } else if ($companyid == 59) { //roosevelt
      $area = $config['params']['dataparams']['area'];
      $filter1 .= "and client.area='" . $area . "'";
    } else {
      $filter1 = "";
    }

    $agfield = '';
    $agfield2 = '';
    $grpagent = '';
    if ($companyid == 23) {
      $agfield = "agentname, ";
      $agfield2 = "ifnull(agent.clientname, '') as agentname, ";
      $grpagent = "agent.clientname, ";

      if ($agentid != "") {
        $agentid = str_replace("~", ",", $config['params']['dataparams']['agentid']);
        $filter1 .= " and agent.clientid in (" . $agentid . ")";
      }
    }



    $sort = "order by $agfield part, brand, barcode, itemname";
    if ($companyid == 60) { //transpower
      $sort = "order by itemname,barcode,part, brand";
    }


    $classid = '';
    $modelid = '';
    $addfields = '';
    $leftjoin_class = '';
    $transpowerfield = '';
    $gtranspowerfield = '';
    $transpowerfield2 = '';

    if ($companyid == 60) {
      $classid = $config['params']['dataparams']['classid'];
      $modelid = $config['params']['dataparams']['modelid'];
      $whid      = $config['params']['dataparams']['wh'];

      $leftjoin_class = 'left join item_class as ic on ic.cl_id = item.class  
    left join itemlevel as itlevel on itlevel.itemid = item.itemid';

      if ($whid != '') {
        $transpowerfield  = ',ic.cl_name as classname, itlevel.min as itemmin, itlevel.max as itemmax, sum(stock.iss) as iss, item.itemid, stock.whid';
        $transpowerfield2 = 'x.classname, x.itemmin, x.itemmax, x.itemid, x.whid,';
        $addfields        = '(select sum(bal) from rrstatus where itemid = x.itemid and whid = x.whid) - sum(x.iss) as balance,';
        $gtranspowerfield = ',ic.cl_name, itlevel.min, itlevel.max, item.itemid, stock.whid';
      } else {
        $transpowerfield  = ',ic.cl_name as classname, itlevel.min as itemmin, itlevel.max as itemmax, sum(stock.iss) as iss, item.itemid';
        $transpowerfield2 = 'x.classname, x.itemmin, x.itemmax, x.itemid,';
        $addfields        = '(select sum(bal) from rrstatus where itemid = x.itemid) - sum(x.iss) as balance,';
        $gtranspowerfield = ',ic.cl_name, itlevel.min, itlevel.max, item.itemid';
      }

      if ($classid != "") {
        $filter1 .= " and ic.cl_id=" . $classid;
      }
      if ($modelid != "") {
        $filter1 .= " and mm.model_id=" . $modelid;
      }
    }

    $query = "select $agfield $transpowerfield2 $addfields barcode,size,uom,category, groupid,category1, subcatname, part, brand, model,body, itemname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
      sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
      select $agfield2 item.barcode, client.clientname, item.sizeid as size,'u' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid,
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND') as brand,
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, item.uom $transpowerfield
      from ((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.client = head.agent
      $leftjoin_class
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)= $year and item.isimport in $itemtype $filter $filter1  and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname, item.uom $gtranspowerfield

      UNION ALL

      select $agfield2 item.barcode, client.clientname, item.sizeid as size,'p' as tr, ifnull(stockgrp.stockgrp_name,'NO GROUP') as groupid,
      ifnull(frontend_ebrands.brand_desc,'NO BRAND') as brand,
      cat.name as category1, subcat.name as subcatname,
      ifnull(parts.part_name,'NO PART') as part, ifnull(mm.model_name,'NO MODEL') as model,item.body,
      ifnull(item.itemname,'') as itemname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then $war else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then $war  else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then $war  else 0 end) as momar,
      sum(case when month(head.dateid)=4 then $war  else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then $war  else 0 end) as momay,
      sum(case when month(head.dateid)=6 then $war  else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then $war  else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then $war  else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then $war  else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then $war else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then $war else 0 end) as monov,
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, item.uom $transpowerfield
      from ((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join client on client.clientid=head.clientid)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join part_masterfile as parts on parts.part_id = item.part
      left join frontend_ebrands on item.brand=frontend_ebrands.brandid
      left join model_masterfile as mm on mm.model_id = item.model
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join client as agent on agent.clientid = head.agentid
      $leftjoin_class
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)= $year and item.isimport in $itemtype $filter $filter1  and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname,item.uom $gtranspowerfield) as x
      group by $agfield $transpowerfield2 part, brand, barcode, size, category, groupid,  model,body, itemname, yr,category1,subcatname, uom
      $sort";
    return $query;
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
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $partname   = $config['params']['dataparams']['partname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $year       = $config['params']['dataparams']['year'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $agent   = $config['params']['dataparams']['agent'];

    if ($whname == "") $whname = "ALL";
    if ($client == "") $client = "ALL";
    if ($clientname == "") $clientname = "ALL";
    if ($barcode == "") $barcode = "ALL";
    if ($groupname == "") $groupname = "ALL";
    if ($brandname == "") $brandname = "ALL";
    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = "ALL";
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . strtoupper($client), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . strtoupper($groupname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Category : ' . ($categoryname == '' ? 'ALL' : $categoryname), '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Sub-Category : ' . ($subcatname == '' ? 'ALL' : $subcatname), '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    $str .= $this->reporter->col('Agent : ' . ($agent == '' ? 'ALL' : $agent), null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
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
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $partname   = $config['params']['dataparams']['partname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $year       = $config['params']['dataparams']['year'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $agent   = $config['params']['dataparams']['agent'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($client == "") {
      $client = "ALL";
    }
    if ($clientname == "") {
      $clientname = "ALL";
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

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = "ALL";
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Supplier : ' . strtoupper($client), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Year : ' . $year, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . strtoupper($barcode), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . strtoupper($whname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Industry : ' . $indus, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
        $str .= $this->reporter->col('Customer : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('Customer : ' . strtoupper($client), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      }
      $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . strtoupper($groupname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
      if ($agent == '') {
        $str .= $this->reporter->col('Agent : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Agent : ' . $agent, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function roosevelt_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM DESCRIPTION', '90', '', '', $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SIZE/UOM', '60', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '70', '', '', $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');

    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')

    switch ($companyid) {
      case 23: //labsol cebu
        $str .= $this->reporter->col('AGENT', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARCODE', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '100', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JAN', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FEB', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAR', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APR', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAY', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUN', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUL', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AUG', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SEP', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OCT', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NOV', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEC', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        if (strtoupper($analyzedby) == "UNIT") {
          $str .= $this->reporter->col('QUANTITY', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col('AMOUNT', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }
        break;

      default:
        $str .= $this->reporter->col('BARCODE', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '90', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APR', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        if (strtoupper($analyzedby) == "UNIT") {
          $str .= $this->reporter->col('QUANTITY', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col('AMOUNT', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }
        break;
    }

    return $str;
  }

  public function reportDefaultLayoutRoosevelt($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $count = 36;
    $page = 37;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->roosevelt_displayHeader($config);

    $str .= $this->roosevelt_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
    $ab = 2;

    $part = $brand = "";
    $totalmojan = $totalmofeb = $totalmomar = $totalmoapr = $totalmomay = $totalmojun = $totalmojul = $totalmoaug = $totalmosep = $totalmooct = $totalmonov = $totalmodec = $amt = $totalamt = 0;

    //brand
    $subjan = $subfeb = $submar = $subapr = $submay = $subjun = $subjul = $subaug = $subsep = $suboct = $subnov = $subdec = $subamt = 0;
    //part
    $gsubjan = $gsubfeb = $gsubmar = $gsubapr = $gsubmay = $gsubjun = $gsubjul = $gsubaug = $gsubsep = $gsuboct = $gsubnov = $gsubdec = $gsubamt = 0;

    foreach ($result as $key => $data) {
      $mojan = number_format($data->mojan, $ab);
      $mofeb = number_format($data->mofeb, $ab);
      $momar = number_format($data->momar, $ab);
      $moapr = number_format($data->moapr, $ab);
      $momay = number_format($data->momay, $ab);
      $mojun = number_format($data->mojun, $ab);
      $mojul = number_format($data->mojul, $ab);
      $moaug = number_format($data->moaug, $ab);
      $mosep = number_format($data->mosep, $ab);
      $mooct = number_format($data->mooct, $ab);
      $monov = number_format($data->monov, $ab);
      $modec = number_format($data->modec, $ab);
      if ($mojan == 0) $mojan = '-';
      if ($mofeb == 0) $mofeb = '-';
      if ($momar == 0) $momar = '-';
      if ($moapr == 0) $moapr = '-';
      if ($momay == 0) $momay = '-';
      if ($mojun == 0) $mojun = '-';
      if ($mojul == 0) $mojul = '-';
      if ($moaug == 0) $moaug = '-';
      if ($mosep == 0) $mosep = '-';
      if ($mooct == 0) $mooct = '-';
      if ($monov == 0) $monov = '-';
      if ($modec == 0) $modec = '-';

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      // if ($part == strtoupper($data->part)) {
      //   $part = "";
      //   if (strtoupper($brand) == strtoupper($data->brand)) {
      //     $brand = "";
      //   } else {
      //     if ($brand != '') {
      //       $str .= $this->reporter->startrow();
      //       $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      //       $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
      //       $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //       $str .= $this->reporter->endrow();
      //     }
      //     //brand
      //     $subjan = 0;
      //     $subfeb = 0;
      //     $submar = 0;
      //     $subapr = 0;
      //     $submay = 0;
      //     $subjun = 0;
      //     $subjul = 0;
      //     $subaug = 0;
      //     $subsep = 0;
      //     $suboct = 0;
      //     $subnov = 0;
      //     $subdec = 0;
      //     $subamt = 0;
      //     //part
      //     $gsubjan = 0;
      //     $gsubfeb = 0;
      //     $gsubmar = 0;
      //     $gsubapr = 0;
      //     $gsubmay = 0;
      //     $gsubjun = 0;
      //     $gsubjul = 0;
      //     $gsubaug = 0;
      //     $gsubsep = 0;
      //     $gsuboct = 0;
      //     $gsubnov = 0;
      //     $gsubdec = 0;
      //     $gsubamt = 0;
      //     $brand = strtoupper($data->brand);
      //   }
      // } else {
      //   if ($brand != '') {
      //     $str .= $this->reporter->startrow();
      //     $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      //     $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
      //     $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->endrow();
      //   }
      //   if ($part != '') {
      //     $str .= $this->reporter->startrow();
      //     $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      //     $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubmar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubmay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsuboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->col(number_format($gsubamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
      //     $str .= $this->reporter->endrow();
      //   }
      //   $part = $data->part;
      //   if (strtoupper($brand) == strtoupper($data->brand)) {
      //     $brand = "";
      //   } else {
      //     //brand
      //     $subjan = 0;
      //     $subfeb = 0;
      //     $submar = 0;
      //     $subapr = 0;
      //     $submay = 0;
      //     $subjun = 0;
      //     $subjul = 0;
      //     $subaug = 0;
      //     $subsep = 0;
      //     $suboct = 0;
      //     $subnov = 0;
      //     $subdec = 0;
      //     $subamt = 0;
      //     //part
      //     $gsubjan = 0;
      //     $gsubfeb = 0;
      //     $gsubmar = 0;
      //     $gsubapr = 0;
      //     $gsubmay = 0;
      //     $gsubjun = 0;
      //     $gsubjul = 0;
      //     $gsubaug = 0;
      //     $gsubsep = 0;
      //     $gsuboct = 0;
      //     $gsubnov = 0;
      //     $gsubdec = 0;
      //     $gsubamt = 0;
      //     $brand = strtoupper($data->brand);
      //   }
      // }
      // $str .= $this->reporter->startrow();
      // //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      // $str .= $this->reporter->col($part, '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      // $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

      // $str .= $this->reporter->startrow();
      // //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      // $str .= $this->reporter->col($brand, '60', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      // $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      // $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($data->itemname, '90', null, false, $border, 'TLRB', 'L', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($data->size . '/' . $data->uom, '60', null, false, $border, 'TLRB', 'C', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');
      $str .= $this->reporter->col(number_format($amt, $ab), '70', null, false, $border, 'TLRB', 'R', $font, $font_size9, '', '', '0 2px 0 0', '');

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
      $part = $data->part;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->roosevelt_displayHeader($config);
        }
        $str .= $this->roosevelt_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $page = $page + $count;
      }
    }

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    //   $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    //   $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    // $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    //   $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubmar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubmay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsuboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    //   $str .= $this->reporter->col(number_format($gsubamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    // $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '90', null, false, $border, 'TLRB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TLRB', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($totalmojan == 0 ? '-' : number_format($totalmojan, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmofeb == 0 ? '-' : number_format($totalmofeb, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmomar == 0 ? '-' : number_format($totalmomar, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmoapr == 0 ? '-' : number_format($totalmoapr, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmomay == 0 ? '-' : number_format($totalmomay, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmojun == 0 ? '-' : number_format($totalmojun, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmojul == 0 ? '-' : number_format($totalmojul, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmoaug == 0 ? '-' : number_format($totalmoaug, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmosep == 0 ? '-' : number_format($totalmosep, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmooct == 0 ? '-' : number_format($totalmooct, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmonov == 0 ? '-' : number_format($totalmonov, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalmodec == 0 ? '-' : number_format($totalmodec, $ab), '65', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->col($totalamt == 0 ? '-' : number_format($totalamt, $ab), '70', null, false, $border, 'TLRB', 'R', $font, $font_size9, 'B', '0 2px 0 0', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $count = 36;
    $page = 37;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
    if (strtoupper($analyzedby) == "UNIT") {
      $ab = 2;
    } else {
      $ab = 2;
    }

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
    foreach ($result as $key => $data) {
      $mojan = number_format($data->mojan, $ab);
      if ($mojan == 0) {
        $mojan = '-';
      }
      $mofeb = number_format($data->mofeb, $ab);
      if ($mofeb == 0) {
        $mofeb = '-';
      }
      $momar = number_format($data->momar, $ab);
      if ($momar == 0) {
        $momar = '-';
      }
      $moapr = number_format($data->moapr, $ab);
      if ($moapr == 0) {
        $moapr = '-';
      }
      $momay = number_format($data->momay, $ab);
      if ($momay == 0) {
        $momay = '-';
      }
      $mojun = number_format($data->mojun, $ab);
      if ($mojun == 0) {
        $mojun = '-';
      }
      $mojul = number_format($data->mojul, $ab);
      if ($mojul == 0) {
        $mojul = '-';
      }
      $moaug = number_format($data->moaug, $ab);
      if ($moaug == 0) {
        $moaug = '-';
      }
      $mosep = number_format($data->mosep, $ab);
      if ($mosep == 0) {
        $mosep = '-';
      }
      $mooct = number_format($data->mooct, $ab);
      if ($mooct == 0) {
        $mooct = '-';
      }
      $monov = number_format($data->monov, $ab);
      if ($monov == 0) {
        $monov = '-';
      }
      $modec = number_format($data->modec, $ab);
      if ($modec == 0) {
        $modec = '-';
      }

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      if ($part == strtoupper($data->part)) {
        $part = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->endrow();
          }
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
          $brand = strtoupper($data->brand);
        }
      } else {
        if ($brand != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
          $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        if ($part != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
          $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsuboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $part = $data->part;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
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
          $brand = strtoupper($data->brand);
        }
      }
      $str .= $this->reporter->startrow();
      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($part, '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

      $str .= $this->reporter->startrow();
      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($brand, '60', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($data->barcode, '60', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($data->itemname, '90', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
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
      $str .= $this->reporter->col(number_format($amt, $ab), '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

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
      $part = $data->part;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->default_displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsuboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '90', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayoutLabsol($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $uom        = $config['params']['dataparams']['uom'];

    $count = 36;
    $page = 37;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $layoutsize = '1200';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($layoutsize, $border, $font, $fontsize11, $config);

    if (strtoupper($analyzedby) == "UNIT") {
      $ab = 2;
    } else {
      $ab = 2;
    }

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
    $agent = "";
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

    //AGENT
    $gagentjan = 0;
    $gagentfeb = 0;
    $gagentmar = 0;
    $gagentapr = 0;
    $gagentmay = 0;
    $gagentjun = 0;
    $gagentjul = 0;
    $gagentaug = 0;
    $gagentsep = 0;
    $gagentoct = 0;
    $gagentnov = 0;
    $gagentdec = 0;
    $gagentamt = 0;

    foreach ($result as $key => $data) {
      $uombal = 0;
      if ($uom != "") {
        $qry = "select ifnull(factor,1) as value from uom 
          left join item on item.itemid = uom.itemid
          where item.barcode = ? and uom.uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->barcode, $uom]);
      }
      if ($uombal != 0 && $analyzedby == 'unit') {
        $mojan = number_format($data->mojan / $uombal, $ab);
        $mofeb = number_format($data->mofeb / $uombal, $ab);
        $momar = number_format($data->momar / $uombal, $ab);
        $moapr = number_format($data->moapr / $uombal, $ab);
        $momay = number_format($data->momay / $uombal, $ab);
        $mojun = number_format($data->mojun / $uombal, $ab);
        $mojul = number_format($data->mojul / $uombal, $ab);
        $moaug = number_format($data->moaug / $uombal, $ab);
        $mosep = number_format($data->mosep / $uombal, $ab);
        $mooct = number_format($data->mooct / $uombal, $ab);
        $monov = number_format($data->monov / $uombal, $ab);
        $modec = number_format($data->modec / $uombal, $ab);
      } else {
        $mojan = number_format($data->mojan, $ab);
        $mofeb = number_format($data->mofeb, $ab);
        $momar = number_format($data->momar, $ab);
        $moapr = number_format($data->moapr, $ab);
        $momay = number_format($data->momay, $ab);
        $mojun = number_format($data->mojun, $ab);
        $mojul = number_format($data->mojul, $ab);
        $moaug = number_format($data->moaug, $ab);
        $mosep = number_format($data->mosep, $ab);
        $mooct = number_format($data->mooct, $ab);
        $monov = number_format($data->monov, $ab);
        $modec = number_format($data->modec, $ab);
      }
      if ($mojan == 0) $mojan = '-';
      if ($mofeb == 0) $mofeb = '-';
      if ($momar == 0) $momar = '-';
      if ($moapr == 0) $moapr = '-';
      if ($momay == 0) $momay = '-';
      if ($mojun == 0) $mojun = '-';
      if ($mojul == 0) $mojul = '-';
      if ($moaug == 0) $moaug = '-';
      if ($mosep == 0) $mosep = '-';
      if ($mooct == 0) $mooct = '-';
      if ($monov == 0) $monov = '-';
      if ($modec == 0) $modec = '-';

      if ($uombal != 0 && $analyzedby == 'unit') {
        $amt = ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);
      } else {
        $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      }

      if ($amt != 0) {
        // SUB TOTAL SALES FOR PART AND BRAND
        if ($part == strtoupper($data->part)) {
          $part = "";
          if (strtoupper($brand) == strtoupper($data->brand)) {
            $brand = "";
          } else {
            if ($brand != '') {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
              $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
              $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
              $str .= $this->reporter->col(number_format($subjan, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subfeb, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($submar, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subapr, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($submay, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subjun, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subjul, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subaug, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subsep, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($suboct, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subnov, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subdec, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->col(number_format($subamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
              $str .= $this->reporter->endrow();
            }
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
            $brand = strtoupper($data->brand);
          }
        } else {
          if ($brand != '') {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subjan, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subfeb, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submar, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subapr, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submay, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjun, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjul, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subaug, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subsep, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($suboct, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subnov, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subdec, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->endrow();
          }
          if ($part != '') {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($gsubjan, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubfeb, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubmar, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubapr, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubmay, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubjun, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubjul, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubaug, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubsep, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsuboct, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubnov, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubdec, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gsubamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->endrow();
          }
          $part = $data->part;
          if (strtoupper($brand) == strtoupper($data->brand)) {
            $brand = "";
          } else {
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
            $brand = strtoupper($data->brand);
          }
        }

        // SUB TOTAL SALES PER GROUP OF AGENT
        if ($key == 0 || $result[$key - 1]->agentname != $data->agentname) {
          if ($key != 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col(($agent != '' ? $agent : 'NO AGENT') . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($gagentjan, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentfeb, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentmar, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentapr, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentmay, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentjun, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentjul, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentaug, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentsep, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentoct, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentnov, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentdec, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($gagentamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->endrow();
          }

          $gagentjan = 0;
          $gagentfeb = 0;
          $gagentmar = 0;
          $gagentapr = 0;
          $gagentmay = 0;
          $gagentjun = 0;
          $gagentjul = 0;
          $gagentaug = 0;
          $gagentsep = 0;
          $gagentoct = 0;
          $gagentnov = 0;
          $gagentdec = 0;
          $gagentamt = 0;
          if ($data->agentname != '') {
            $agent = strtoupper($data->agentname);
          }
        }

        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
        $str .= $this->reporter->col($part, '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
        $str .= $this->reporter->col($brand, '60', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        if ($key == 0 || $result[$key - 1]->agentname != $data->agentname) {
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
        } else {
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
        }

        $str .= $this->reporter->col($data->barcode, '60', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($mojan, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($mofeb, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($momar, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($moapr, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($momay, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($mojun, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($mojul, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($moaug, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($mosep, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($mooct, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($monov, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col($modec, '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
        $str .= $this->reporter->col(number_format($amt, $ab), '100', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

        if ($uombal != 0 && $analyzedby == 'unit') {
          $subjan = $subjan + ($data->mojan / $uombal);
          $subfeb = $subfeb + ($data->mofeb / $uombal);
          $submar = $submar + ($data->momar / $uombal);
          $subapr = $subapr + ($data->moapr / $uombal);
          $submay = $submay + ($data->momay / $uombal);
          $subjun = $subjun + ($data->mojun / $uombal);
          $subjul = $subjul + ($data->mojul / $uombal);
          $subaug = $subaug + ($data->moaug / $uombal);
          $subsep = $subsep + ($data->mosep / $uombal);
          $suboct = $suboct + ($data->mooct / $uombal);
          $subnov = $subnov + ($data->monov / $uombal);
          $subdec = $subdec + ($data->modec / $uombal);
          $subamt = $subamt + ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);

          $gsubjan = $gsubjan + ($data->mojan / $uombal);
          $gsubfeb = $gsubfeb + ($data->mofeb / $uombal);
          $gsubmar = $gsubmar + ($data->momar / $uombal);
          $gsubapr = $gsubapr + ($data->moapr / $uombal);
          $gsubmay = $gsubmay + ($data->momay / $uombal);
          $gsubjun = $gsubjun + ($data->mojun / $uombal);
          $gsubjul = $gsubjul + ($data->mojul / $uombal);
          $gsubaug = $gsubaug + ($data->moaug / $uombal);
          $gsubsep = $gsubsep + ($data->mosep / $uombal);
          $gsuboct = $gsuboct + ($data->mooct / $uombal);
          $gsubnov = $gsubnov + ($data->monov / $uombal);
          $gsubdec = $gsubdec + ($data->modec / $uombal);
          $gsubamt = $gsubamt + ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);

          $gagentjan = $gagentjan + ($data->mojan / $uombal);
          $gagentfeb = $gagentfeb + ($data->mofeb / $uombal);
          $gagentmar = $gagentmar + ($data->momar / $uombal);
          $gagentapr = $gagentapr + ($data->moapr / $uombal);
          $gagentmay = $gagentmay + ($data->momay / $uombal);
          $gagentjun = $gagentjun + ($data->mojun / $uombal);
          $gagentjul = $gagentjul + ($data->mojul / $uombal);
          $gagentaug = $gagentaug + ($data->moaug / $uombal);
          $gagentsep = $gagentsep + ($data->mosep / $uombal);
          $gagentoct = $gagentoct + ($data->mooct / $uombal);
          $gagentnov = $gagentnov + ($data->monov / $uombal);
          $gagentdec = $gagentdec + ($data->modec / $uombal);
          $gagentamt = $gagentamt + ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);

          $totalmojan = $totalmojan + ($data->mojan / $uombal);
          $totalmofeb = $totalmofeb + ($data->mofeb / $uombal);
          $totalmomar = $totalmomar + ($data->momar / $uombal);
          $totalmoapr = $totalmoapr + ($data->moapr / $uombal);
          $totalmomay = $totalmomay + ($data->momay / $uombal);
          $totalmojun = $totalmojun + ($data->mojun / $uombal);
          $totalmojul = $totalmojul + ($data->mojul / $uombal);
          $totalmoaug = $totalmoaug + ($data->moaug / $uombal);
          $totalmosep = $totalmosep + ($data->mosep / $uombal);
          $totalmooct = $totalmooct + ($data->mooct / $uombal);
          $totalmonov = $totalmonov + ($data->monov / $uombal);
          $totalmodec = $totalmodec + ($data->modec / $uombal);
          $totalamt = $totalamt + $amt;
        } else {
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

          $gagentjan = $gagentjan + $data->mojan;
          $gagentfeb = $gagentfeb + $data->mofeb;
          $gagentmar = $gagentmar + $data->momar;
          $gagentapr = $gagentapr + $data->moapr;
          $gagentmay = $gagentmay + $data->momay;
          $gagentjun = $gagentjun + $data->mojun;
          $gagentjul = $gagentjul + $data->mojul;
          $gagentaug = $gagentaug + $data->moaug;
          $gagentsep = $gagentsep + $data->mosep;
          $gagentoct = $gagentoct + $data->mooct;
          $gagentnov = $gagentnov + $data->monov;
          $gagentdec = $gagentdec + $data->modec;
          $gagentamt = $gagentamt + $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

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
        }

        $brand = strtoupper($data->brand);
        $part = $data->part;

        $str .= $this->reporter->endrow();
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->default_displayHeader($config);
        }
        $str .= $this->default_table_cols($layoutsize, $border, $font, $fontsize11, $config);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subjan, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subfeb, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submar, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subapr, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submay, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjun, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjul, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subaug, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subsep, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($suboct, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subnov, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subdec, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjan, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubfeb, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmar, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubapr, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmay, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjun, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjul, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubaug, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubsep, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsuboct, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnov, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubdec, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $ab), '100', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayoutTechnolab($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $uom        = $config['params']['dataparams']['uom'];

    $count = 36;
    $page = 37;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    if (strtoupper($analyzedby) == "UNIT") {
      $ab = 2;
    } else {
      $ab = 2;
    }

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
    foreach ($result as $key => $data) {
      $uombal = 0;
      if ($uom != "") {
        $qry = "select ifnull(factor,1) as value from uom 
          left join item on item.itemid = uom.itemid
          where item.barcode = ? and uom.uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->barcode, $uom]);
      }
      if ($uombal != 0 && $analyzedby == 'unit') {
        $mojan = number_format($data->mojan / $uombal, $ab);
        $mofeb = number_format($data->mofeb / $uombal, $ab);
        $momar = number_format($data->momar / $uombal, $ab);
        $moapr = number_format($data->moapr / $uombal, $ab);
        $momay = number_format($data->momay / $uombal, $ab);
        $mojun = number_format($data->mojun / $uombal, $ab);
        $mojul = number_format($data->mojul / $uombal, $ab);
        $moaug = number_format($data->moaug / $uombal, $ab);
        $mosep = number_format($data->mosep / $uombal, $ab);
        $mooct = number_format($data->mooct / $uombal, $ab);
        $monov = number_format($data->monov / $uombal, $ab);
        $modec = number_format($data->modec / $uombal, $ab);
      } else {
        $mojan = number_format($data->mojan, $ab);
        $mofeb = number_format($data->mofeb, $ab);
        $momar = number_format($data->momar, $ab);
        $moapr = number_format($data->moapr, $ab);
        $momay = number_format($data->momay, $ab);
        $mojun = number_format($data->mojun, $ab);
        $mojul = number_format($data->mojul, $ab);
        $moaug = number_format($data->moaug, $ab);
        $mosep = number_format($data->mosep, $ab);
        $mooct = number_format($data->mooct, $ab);
        $monov = number_format($data->monov, $ab);
        $modec = number_format($data->modec, $ab);
      }
      if ($mojan == 0) $mojan = '-';
      if ($mofeb == 0) $mofeb = '-';
      if ($momar == 0) $momar = '-';
      if ($moapr == 0) $moapr = '-';
      if ($momay == 0) $momay = '-';
      if ($mojun == 0) $mojun = '-';
      if ($mojul == 0) $mojul = '-';
      if ($moaug == 0) $moaug = '-';
      if ($mosep == 0) $mosep = '-';
      if ($mooct == 0) $mooct = '-';
      if ($monov == 0) $monov = '-';
      if ($modec == 0) $modec = '-';

      if ($uombal != 0 && $analyzedby == 'unit') {
        $amt = ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);
      } else {
        $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      }
      if ($part == strtoupper($data->part)) {
        $part = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->endrow();
          }
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
          $brand = strtoupper($data->brand);
        }
      } else {
        if ($brand != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
          $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        if ($part != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
          $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsuboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $part = $data->part;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
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
          $brand = strtoupper($data->brand);
        }
      }
      $str .= $this->reporter->startrow();
      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($part, '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

      $str .= $this->reporter->startrow();
      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($brand, '60', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($data->barcode, '60', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($data->itemname, '90', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
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
      $str .= $this->reporter->col(number_format($amt, $ab), '70', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

      if ($uombal != 0 && $analyzedby == 'unit') {
        $subjan = $subjan + ($data->mojan / $uombal);
        $subfeb = $subfeb + ($data->mofeb / $uombal);
        $submar = $submar + ($data->momar / $uombal);
        $subapr = $subapr + ($data->moapr / $uombal);
        $submay = $submay + ($data->momay / $uombal);
        $subjun = $subjun + ($data->mojun / $uombal);
        $subjul = $subjul + ($data->mojul / $uombal);
        $subaug = $subaug + ($data->moaug / $uombal);
        $subsep = $subsep + ($data->mosep / $uombal);
        $suboct = $suboct + ($data->mooct / $uombal);
        $subnov = $subnov + ($data->monov / $uombal);
        $subdec = $subdec + ($data->modec / $uombal);
        $subamt = $subamt + ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);

        $gsubjan = $gsubjan + ($data->mojan / $uombal);
        $gsubfeb = $gsubfeb + ($data->mofeb / $uombal);
        $gsubmar = $gsubmar + ($data->momar / $uombal);
        $gsubapr = $gsubapr + ($data->moapr / $uombal);
        $gsubmay = $gsubmay + ($data->momay / $uombal);
        $gsubjun = $gsubjun + ($data->mojun / $uombal);
        $gsubjul = $gsubjul + ($data->mojul / $uombal);
        $gsubaug = $gsubaug + ($data->moaug / $uombal);
        $gsubsep = $gsubsep + ($data->mosep / $uombal);
        $gsuboct = $gsuboct + ($data->mooct / $uombal);
        $gsubnov = $gsubnov + ($data->monov / $uombal);
        $gsubdec = $gsubdec + ($data->modec / $uombal);
        $gsubamt = $gsubamt + ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);

        $totalmojan = $totalmojan + ($data->mojan / $uombal);
        $totalmofeb = $totalmofeb + ($data->mofeb / $uombal);
        $totalmomar = $totalmomar + ($data->momar / $uombal);
        $totalmoapr = $totalmoapr + ($data->moapr / $uombal);
        $totalmomay = $totalmomay + ($data->momay / $uombal);
        $totalmojun = $totalmojun + ($data->mojun / $uombal);
        $totalmojul = $totalmojul + ($data->mojul / $uombal);
        $totalmoaug = $totalmoaug + ($data->moaug / $uombal);
        $totalmosep = $totalmosep + ($data->mosep / $uombal);
        $totalmooct = $totalmooct + ($data->mooct / $uombal);
        $totalmonov = $totalmonov + ($data->monov / $uombal);
        $totalmodec = $totalmodec + ($data->modec / $uombal);
        $totalamt = $totalamt + $amt;
      } else {
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
      }

      $brand = strtoupper($data->brand);
      $part = $data->part;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->default_displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($suboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '90', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjan, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubfeb, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmar, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubapr, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmay, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjun, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjul, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubaug, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubsep, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsuboct, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnov, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubdec, $ab), '65', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamt, $ab), '70', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '90', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, $ab), '65', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $ab), '70', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function Nathina_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $client     = $config['params']['dataparams']['client'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $partname   = $config['params']['dataparams']['partname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

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

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else {
      $posttype = 'Unposted';
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . strtoupper($client), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . strtoupper($groupname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    return $str;
  }

  private function nathina_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM DESCRIPTION', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUL', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '60', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    if (strtoupper($analyzedby) == "UNIT") {
      $str .= $this->reporter->col('QUANTITY', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('AMOUNT', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    }

    return $str;
  }

  public function nathina_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '10';
    $fontsize11 = 11;
    $companyid = $config['params']['companyid'];

    $result = $this->reportDefault($config);
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $count = 36;
    $page = 37;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->Nathina_displayHeader($config);
    $str .= $this->nathina_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $str .= $this->reporter->begintable($layoutsize);

    if (strtoupper($analyzedby) == "UNIT") {
      $ab = 2;
    } else {
      $ab = 2;
    }

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
    foreach ($result as $key => $data) {
      $mojan = number_format($data->mojan, $ab);
      if ($mojan == 0) {
        $mojan = '-';
      }
      $mofeb = number_format($data->mofeb, $ab);
      if ($mofeb == 0) {
        $mofeb = '-';
      }
      $momar = number_format($data->momar, $ab);
      if ($momar == 0) {
        $momar = '-';
      }
      $moapr = number_format($data->moapr, $ab);
      if ($moapr == 0) {
        $moapr = '-';
      }
      $momay = number_format($data->momay, $ab);
      if ($momay == 0) {
        $momay = '-';
      }
      $mojun = number_format($data->mojun, $ab);
      if ($mojun == 0) {
        $mojun = '-';
      }
      $mojul = number_format($data->mojul, $ab);
      if ($mojul == 0) {
        $mojul = '-';
      }
      $moaug = number_format($data->moaug, $ab);
      if ($moaug == 0) {
        $moaug = '-';
      }
      $mosep = number_format($data->mosep, $ab);
      if ($mosep == 0) {
        $mosep = '-';
      }
      $mooct = number_format($data->mooct, $ab);
      if ($mooct == 0) {
        $mooct = '-';
      }
      $monov = number_format($data->monov, $ab);
      if ($monov == 0) {
        $monov = '-';
      }
      $modec = number_format($data->modec, $ab);
      if ($modec == 0) {
        $modec = '-';
      }

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      if ($part == strtoupper($data->part)) {
        $part = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {
            $str .= $this->reporter->startrow();
            if ($companyid == 15 && $brand == 'NO BRAND') { //nathina
              $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            } else {
              $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
            }
            $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subjan, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subfeb, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submar, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subapr, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($submay, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjun, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subjul, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subaug, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subsep, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($suboct, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subnov, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subdec, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->col(number_format($subamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
            $str .= $this->reporter->endrow();
          }
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
          $brand = strtoupper($data->brand);
        }
      } else {
        if ($brand != '') {
          $str .= $this->reporter->startrow();
          if ($companyid == 15 && $brand == 'NO BRAND') { //nathina
            $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          } else {
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          }
          $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($subjan, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subfeb, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($submar, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subapr, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($submay, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subjun, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subjul, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subaug, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subsep, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($suboct, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subnov, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subdec, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($subamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        if ($part != '') {
          $str .= $this->reporter->startrow();
          if ($companyid == 15 && $part == 'NO PART') { //nathina
            $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          } else {
            $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
          }
          $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjan, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubfeb, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmar, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubapr, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubmay, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjun, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubjul, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubaug, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubsep, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsuboct, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubnov, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubdec, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->col(number_format($gsubamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $part = $data->part;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
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
          $brand = strtoupper($data->brand);
        }
      }

      $str .= $this->reporter->startrow();
      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      if ($companyid == 15 && $part == 'NO PART') { //nathina
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      } else {
        $str .= $this->reporter->col($part, '100', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      }
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $font_size9, 'B', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

      $str .= $this->reporter->startrow();
      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      if ($companyid == 15 && $brand == 'NO BRAND') { //nathina
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      } else {
        $str .= $this->reporter->col($brand, '100', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      }
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $font_size9, 'Bi', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'L', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojan, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($momar, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($moapr, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($momay, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojun, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mojul, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($moaug, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mosep, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($mooct, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($monov, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col($modec, '60', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, $ab), '100', null, false, $border, '', 'R', $font, $font_size9, '', '', '', '');

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
      $part = $data->part;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->Nathina_displayHeader($config);
        }
        $str .= $this->nathina_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    if ($companyid == 15 && $brand == 'NO BRAND') { //nathina
      $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    } else {
      $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    }
    $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subjan, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subfeb, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submar, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subapr, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($submay, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjun, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjul, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subaug, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subsep, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($suboct, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subnov, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subdec, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($subamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($companyid == 15 && $part == 'NO PART') {
      $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    } else {
      $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '100', null, false, $border, '', 'R', $font, $font_size9, 'Bi', '', '', '');
    }
    $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjan, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubfeb, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmar, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubapr, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmay, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjun, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjul, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubaug, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubsep, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsuboct, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnov, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubdec, $ab), '60', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamt, $ab), '100', null, false, '1px dotted ', 'T', 'R', $font, $font_size9, '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, $ab), '60', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $ab), '100', null, false, $border, 'TB', 'R', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  //transpower
  private function transpower_displayHeader($config)
  {
    $border = '1px solid';
    $font = 'Tahoma';
    $font_size = '8';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $partname   = $config['params']['dataparams']['partname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $year       = $config['params']['dataparams']['year'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $agent      = $config['params']['dataparams']['agent'];

    if ($whname == "") $whname = "ALL";
    if ($client == "") $client = "ALL";
    if ($clientname == "") $clientname = "ALL";
    if ($barcode == "") $barcode = "ALL";
    if ($groupname == "") $groupname = "ALL";
    if ($brandname == "") $brandname = "ALL";

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = "ALL";
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    $class = $config['params']['dataparams']['classic'];
    $model = $config['params']['dataparams']['modelname'];

    $str = '';
    $layoutsize = '1400';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ANALYZE ITEM SALES (MONTHLY)', null, null, false, $border, '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . strtoupper($client), '220', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item :' . strtoupper($barcode), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group :' . strtoupper($groupname), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Part :' . strtoupper($partname), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '380', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '380', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    if ($agent == '') {
      $str .= $this->reporter->col('Agent : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Agent : ' . $agent, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }

    if ($whname == '') {
      $str .= $this->reporter->col('Warehouse : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Warehouse : ' . $whname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $font_size, '', '', '');
    if ($class == '') {
      $str .= $this->reporter->col('Class : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Class : ' . $class, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    if ($model == '') {
      $str .= $this->reporter->col('Model : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Model : ' . $model, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->col('', '0', null, false, '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '0', null, false, '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '0', null, false, '', '', '', '', '', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  private function transpower_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $layoutsize = '1400';
    $companyid  = $config['params']['companyid'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $font       = 'Tahoma';
    $fontsize   = '8';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1400', '10', '', '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '', '', '', '', '', '', '#dad4d3');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '90', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '171', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GROUP', '50', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('JUL', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '67', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    if (strtoupper($analyzedby) == "UNIT") {
      $str .= $this->reporter->col('QUANTITY', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('AMOUNT', '70', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('BAL', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MIN', '50', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAX', '50', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayoutTranspower($config)
  {
    $border = '1px solid';
    $font = 'Tahoma';
    $fontsize = '8';

    $result     = $this->reportDefault($config);

    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $uom        = $config['params']['dataparams']['uom'];

    $count = 25;
    $page  = 25;
    $firstPageCount = 20; // adjust number of rows for the first page if needed
    $pageIndex = 0;       // tracks which page we're currently filling
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $layoutsize = '1400';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:150px');
    $str .= $this->transpower_displayHeader($config);
    $str .= $this->transpower_table_cols($layoutsize, $border, $font, $fontsize, $config);

    if (strtoupper($analyzedby) == "UNIT") {
      $ab = 2;
    } else {
      $ab = 2;
    }

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

    $part  = "";
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

    $totalItems = count($result);
    $currentIndex = 0;

    foreach ($result as $key => $data) {
      $currentIndex++;

      $balanceQry = $this->TRANSPOWER_ALL($config, $data->itemid);
      $balanceResult = $this->coreFunctions->opentable($balanceQry);
      $balance = (!empty($balanceResult) && isset($balanceResult[0]->balance)) ? $balanceResult[0]->balance : 0;


      $uombal = 0;
      if ($uom != "") {
        $qry = "select ifnull(factor,1) as value from uom 
        left join item on item.itemid = uom.itemid
        where item.barcode = ? and uom.uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->barcode, $uom]);
      }
      // To make the groupid empty if it is 'NO GROUP' to avoid displaying 'NO GROUP' in the report
      $groupid = (strtoupper($data->groupid) == 'NO GROUP') ? '' : $data->groupid;

      if ($uombal != 0 && $analyzedby == 'unit') {
        $mojan = number_format($data->mojan / $uombal, $ab);
        $mofeb = number_format($data->mofeb / $uombal, $ab);
        $momar = number_format($data->momar / $uombal, $ab);
        $moapr = number_format($data->moapr / $uombal, $ab);
        $momay = number_format($data->momay / $uombal, $ab);
        $mojun = number_format($data->mojun / $uombal, $ab);
        $mojul = number_format($data->mojul / $uombal, $ab);
        $moaug = number_format($data->moaug / $uombal, $ab);
        $mosep = number_format($data->mosep / $uombal, $ab);
        $mooct = number_format($data->mooct / $uombal, $ab);
        $monov = number_format($data->monov / $uombal, $ab);
        $modec = number_format($data->modec / $uombal, $ab);
      } else {
        $mojan = number_format($data->mojan, $ab);
        $mofeb = number_format($data->mofeb, $ab);
        $momar = number_format($data->momar, $ab);
        $moapr = number_format($data->moapr, $ab);
        $momay = number_format($data->momay, $ab);
        $mojun = number_format($data->mojun, $ab);
        $mojul = number_format($data->mojul, $ab);
        $moaug = number_format($data->moaug, $ab);
        $mosep = number_format($data->mosep, $ab);
        $mooct = number_format($data->mooct, $ab);
        $monov = number_format($data->monov, $ab);
        $modec = number_format($data->modec, $ab);
      }

      if ($mojan == 0) $mojan = '-';
      if ($mofeb == 0) $mofeb = '-';
      if ($momar == 0) $momar = '-';
      if ($moapr == 0) $moapr = '-';
      if ($momay == 0) $momay = '-';
      if ($mojun == 0) $mojun = '-';
      if ($mojul == 0) $mojul = '-';
      if ($moaug == 0) $moaug = '-';
      if ($mosep == 0) $mosep = '-';
      if ($mooct == 0) $mooct = '-';
      if ($monov == 0) $monov = '-';
      if ($modec == 0) $modec = '-';

      if ($uombal != 0 && $analyzedby == 'unit') {
        $amt = ($data->mojan / $uombal) + ($data->mofeb / $uombal) + ($data->momar / $uombal) + ($data->moapr / $uombal) + ($data->momay / $uombal) + ($data->mojun / $uombal) + ($data->mojul / $uombal) + ($data->moaug / $uombal) + ($data->mosep / $uombal) + ($data->mooct / $uombal) + ($data->monov / $uombal) + ($data->modec / $uombal);
      } else {
        $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;
      }

      if ($part == strtoupper($data->part)) {
        $part = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {
            $str .= $this->reporter->begintable();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '90', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '50', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '171', '', false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
            $str .= $this->reporter->col($subjan == 0 ? '-' : number_format($subjan, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subfeb == 0 ? '-' : number_format($subfeb, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($submar == 0 ? '-' : number_format($submar, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subapr == 0 ? '-' : number_format($subapr, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($submay == 0 ? '-' : number_format($submay, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subjun == 0 ? '-' : number_format($subjun, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subjul == 0 ? '-' : number_format($subjul, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subaug == 0 ? '-' : number_format($subaug, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subsep == 0 ? '-' : number_format($subsep, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($suboct == 0 ? '-' : number_format($suboct, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subnov == 0 ? '-' : number_format($subnov, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subdec == 0 ? '-' : number_format($subdec, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($subamt == 0 ? '-' : number_format($subamt, $ab), '70', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '65', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // Increment counter for subtotal row
            $this->reporter->linecounter++;
          }
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
          $brand = strtoupper($data->brand);
        }
      } else {
        if ($brand != '') {
          $str .= $this->reporter->begintable();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '90', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '171', '', false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
          $str .= $this->reporter->col($subjan == 0 ? '-' : number_format($subjan, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subfeb == 0 ? '-' : number_format($subfeb, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($submar == 0 ? '-' : number_format($submar, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subapr == 0 ? '-' : number_format($subapr, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($submay == 0 ? '-' : number_format($submay, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subjun == 0 ? '-' : number_format($subjun, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subjul == 0 ? '-' : number_format($subjul, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subaug == 0 ? '-' : number_format($subaug, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subsep == 0 ? '-' : number_format($subsep, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($suboct == 0 ? '-' : number_format($suboct, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subnov == 0 ? '-' : number_format($subnov, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subdec == 0 ? '-' : number_format($subdec, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($subamt == 0 ? '-' : number_format($subamt, $ab), '70', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '65', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          // Increment counter for subtotal row
          $this->reporter->linecounter++;
        }
        if ($part != '') {
          $str .= $this->reporter->begintable();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '90', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '171', '', false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
          $str .= $this->reporter->col($gsubjan == 0 ? '-' : number_format($gsubjan, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubfeb == 0 ? '-' : number_format($gsubfeb, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubmar == 0 ? '-' : number_format($gsubmar, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubapr == 0 ? '-' : number_format($gsubapr, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubmay == 0 ? '-' : number_format($gsubmay, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubjun == 0 ? '-' : number_format($gsubjun, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubjul == 0 ? '-' : number_format($gsubjul, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubaug == 0 ? '-' : number_format($gsubaug, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubsep == 0 ? '-' : number_format($gsubsep, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsuboct == 0 ? '-' : number_format($gsuboct, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubnov == 0 ? '-' : number_format($gsubnov, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubdec == 0 ? '-' : number_format($gsubdec, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($gsubamt == 0 ? '-' : number_format($gsubamt, $ab), '70', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '65', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          // Increment counter for subtotal row
          $this->reporter->linecounter++;
        }
        $part = $data->part;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
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
          $brand = strtoupper($data->brand);
        }
      }

      $str .= $this->reporter->begintable();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($part, '1400', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($brand, '1400', '', false, $border, '', 'L', $font, $fontsize, 'Bi', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '90', '', false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(strtoupper($data->itemname), '171', '', false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($groupid, '50', '', false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojan, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momar, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moapr, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momay, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojun, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojul, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moaug, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mosep, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mooct, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($monov, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($modec, '67', '', false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, $ab), '70', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($balance == 0 ? '-' : number_format($balance, 2), '65', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->itemmin == 0 ? '-' : number_format($data->itemmin, 2), '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->itemmax == 0 ? '-' : number_format($data->itemmax, 2), '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data->uom, '50', '', false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      if ($uombal != 0 && $analyzedby == 'unit') {
        $subjan += $data->mojan / $uombal;
        $subfeb += $data->mofeb / $uombal;
        $submar += $data->momar / $uombal;
        $subapr += $data->moapr / $uombal;
        $submay += $data->momay / $uombal;
        $subjun += $data->mojun / $uombal;
        $subjul += $data->mojul / $uombal;
        $subaug += $data->moaug / $uombal;
        $subsep += $data->mosep / $uombal;
        $suboct += $data->mooct / $uombal;
        $subnov += $data->monov / $uombal;
        $subdec += $data->modec / $uombal;
        $subamt += $amt;

        $gsubjan += $data->mojan / $uombal;
        $gsubfeb += $data->mofeb / $uombal;
        $gsubmar += $data->momar / $uombal;
        $gsubapr += $data->moapr / $uombal;
        $gsubmay += $data->momay / $uombal;
        $gsubjun += $data->mojun / $uombal;
        $gsubjul += $data->mojul / $uombal;
        $gsubaug += $data->moaug / $uombal;
        $gsubsep += $data->mosep / $uombal;
        $gsuboct += $data->mooct / $uombal;
        $gsubnov += $data->monov / $uombal;
        $gsubdec += $data->modec / $uombal;
        $gsubamt += $amt;

        $totalmojan += $data->mojan / $uombal;
        $totalmofeb += $data->mofeb / $uombal;
        $totalmomar += $data->momar / $uombal;
        $totalmoapr += $data->moapr / $uombal;
        $totalmomay += $data->momay / $uombal;
        $totalmojun += $data->mojun / $uombal;
        $totalmojul += $data->mojul / $uombal;
        $totalmoaug += $data->moaug / $uombal;
        $totalmosep += $data->mosep / $uombal;
        $totalmooct += $data->mooct / $uombal;
        $totalmonov += $data->monov / $uombal;
        $totalmodec += $data->modec / $uombal;
        $totalamt += $amt;
      } else {
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
        $subamt += $amt;

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
        $gsubamt += $amt;

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
      }

      $brand = strtoupper($data->brand);
      $part  = $data->part;


      $this->reporter->linecounter++;

      $currentPageLimit = ($pageIndex == 0) ? $firstPageCount : $page;

      if ($this->reporter->linecounter >= $currentPageLimit && $currentIndex < $totalItems) {
        $str .= $this->reporter->page_break();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('1400');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '1400', null, false, '1px solid', '', 'R', 'Tahoma', '8', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<div style="height:5px;"></div>';


        $this->reporter->linecounter = 0;
        $pageIndex++; // moving to next page
        // Add table column headers for next page
        $str .= $this->transpower_table_cols($layoutsize, $border, $font, $fontsize, $config);
      }
    }

    // brand sub total (last)
    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '171', '', false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
    $str .= $this->reporter->col($subjan == 0 ? '-' : number_format($subjan, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subfeb == 0 ? '-' : number_format($subfeb, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($submar == 0 ? '-' : number_format($submar, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subapr == 0 ? '-' : number_format($subapr, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($submay == 0 ? '-' : number_format($submay, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subjun == 0 ? '-' : number_format($subjun, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subjul == 0 ? '-' : number_format($subjul, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subaug == 0 ? '-' : number_format($subaug, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subsep == 0 ? '-' : number_format($subsep, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($suboct == 0 ? '-' : number_format($suboct, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subnov == 0 ? '-' : number_format($subnov, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subdec == 0 ? '-' : number_format($subdec, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($subamt == 0 ? '-' : number_format($subamt, $ab), '70', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '65', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // part sub total (last)
    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($part . ' ' . 'SUB TOTAL:', '171', '', false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
    $str .= $this->reporter->col($gsubjan == 0 ? '-' : number_format($gsubjan, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubfeb == 0 ? '-' : number_format($gsubfeb, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubmar == 0 ? '-' : number_format($gsubmar, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubapr == 0 ? '-' : number_format($gsubapr, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubmay == 0 ? '-' : number_format($gsubmay, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubjun == 0 ? '-' : number_format($gsubjun, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubjul == 0 ? '-' : number_format($gsubjul, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubaug == 0 ? '-' : number_format($gsubaug, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubsep == 0 ? '-' : number_format($gsubsep, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsuboct == 0 ? '-' : number_format($gsuboct, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubnov == 0 ? '-' : number_format($gsubnov, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubdec == 0 ? '-' : number_format($gsubdec, $ab), '67', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col($gsubamt == 0 ? '-' : number_format($gsubamt, $ab), '70', '', false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '65', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // grand total
    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', '', false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '171', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($totalmojan == 0 ? '-' : number_format($totalmojan, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmofeb == 0 ? '-' : number_format($totalmofeb, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmomar == 0 ? '-' : number_format($totalmomar, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmoapr == 0 ? '-' : number_format($totalmoapr, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmomay == 0 ? '-' : number_format($totalmomay, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmojun == 0 ? '-' : number_format($totalmojun, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmojul == 0 ? '-' : number_format($totalmojul, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmoaug == 0 ? '-' : number_format($totalmoaug, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmosep == 0 ? '-' : number_format($totalmosep, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmooct == 0 ? '-' : number_format($totalmooct, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmonov == 0 ? '-' : number_format($totalmonov, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalmodec == 0 ? '-' : number_format($totalmodec, $ab), '67', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalamt == 0 ? '-' : number_format($totalamt, $ab), '70', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '65', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function reportHeaderSbc($config)
  {
    $border = '1px solid';
    $font = 'Tahoma';
    $font_size = '9';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $partname   = $config['params']['dataparams']['partname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $whname     = $config['params']['dataparams']['whname'];
    $year       = $config['params']['dataparams']['year'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $agent   = $config['params']['dataparams']['agent'];

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($client == "") {
      $client = "ALL";
    }
    if ($clientname == "") {
      $clientname = "ALL";
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

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = "ALL";
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    $str = '';
    $layoutsize = '1350';

    // Start with letterhead
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // Title
    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ANALYZE ITEM SALES (MONTHLY)', $layoutsize, null, false, $border, '', 'L', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    // Filter information - ONLY 2 ROWS
    $str .= $this->reporter->begintable($layoutsize);

    // ROW 1: Customer, Item, Group, Brand, Part, Category (6 columns) -- each col = 1350/6 = 225
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . strtoupper($client), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item : ' . strtoupper($barcode), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group : ' . strtoupper($groupname), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Part : ' . strtoupper($partname), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '225', null, false, $border, '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '225', null, false, $border, '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->endrow();

    // ROW 2: Sub-Category, Agent, Transaction, Analyze By, Item Type, Page (6 columns)
    $str .= $this->reporter->startrow();
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category : ALL', '225', null, false, $border, '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '225', null, false, $border, '', 'L', $font, $font_size, '', $padding, $margin);
    }
    if ($agent == '') {
      $str .= $this->reporter->col('Agent : ALL', '225', null, false, $border, '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Agent : ' . $agent, '225', null, false, $border, '', 'L', $font, $font_size, '', $padding, $margin);
    }
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Analyze By : ' . strtoupper($analyzedby), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '225', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page', '225', null, false, $border, '', 'R', $font, $font_size, '', $padding, $margin);
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  private function sbc_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1350', '5', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('BARCODE', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUL', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '80', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    if (strtoupper($analyzedby) == "UNIT") {
      $str .= $this->reporter->col('QUANTITY', '90', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('AMOUNT', '90', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '50', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayoutSbc($config)
  {
    // --- PREVENT TIMEOUTS AND MEMORY CRASHES ---
    // set_time_limit(0);
    // ini_set('memory_limit', '512M');

    $border = '1px solid';
    $font = 'Tahoma';
    $fontsize = '9';

    $result = $this->reportDefault($config);
    $analyzedby = $config['params']['dataparams']['analyzedby'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1400';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:150px');
    $str .= $this->reportHeaderSbc($config);
    $str .= $this->sbc_table_cols($layoutsize, $border, $font, $fontsize, $config);

    if (strtoupper($analyzedby) == "UNIT") {
      $ab = 2;
    } else {
      $ab = 2;
    }

    $rowsPerPage    = 29;
    $maxRowsPerPage = 31;
    $rowCounter     = 0;

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
    $totalamt    = 0;

    $subjan  = 0;
    $subfeb  = 0;
    $submar  = 0;
    $subapr  = 0;
    $submay  = 0;
    $subjun  = 0;
    $subjul  = 0;
    $subaug  = 0;
    $subsep  = 0;
    $suboct  = 0;
    $subnov  = 0;
    $subdec  = 0;
    $subamt  = 0;

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

    $currentPart  = null;
    $currentBrand = null;

    foreach ($result as $key => $data) {

      $itemname = wordwrap($data->itemname, 31, "\n");
      $lines    = substr_count($itemname, "\n") + 1;

      $mojan = number_format($data->mojan, $ab);
      if ($mojan == 0) $mojan = '-';
      $mofeb = number_format($data->mofeb, $ab);
      if ($mofeb == 0) $mofeb = '-';
      $momar = number_format($data->momar, $ab);
      if ($momar == 0) $momar = '-';
      $moapr = number_format($data->moapr, $ab);
      if ($moapr == 0) $moapr = '-';
      $momay = number_format($data->momay, $ab);
      if ($momay == 0) $momay = '-';
      $mojun = number_format($data->mojun, $ab);
      if ($mojun == 0) $mojun = '-';
      $mojul = number_format($data->mojul, $ab);
      if ($mojul == 0) $mojul = '-';
      $moaug = number_format($data->moaug, $ab);
      if ($moaug == 0) $moaug = '-';
      $mosep = number_format($data->mosep, $ab);
      if ($mosep == 0) $mosep = '-';
      $mooct = number_format($data->mooct, $ab);
      if ($mooct == 0) $mooct = '-';
      $monov = number_format($data->monov, $ab);
      if ($monov == 0) $monov = '-';
      $modec = number_format($data->modec, $ab);
      if ($modec == 0) $modec = '-';

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun
        + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $rowPart  = $data->part;
      $rowBrand = strtoupper($data->brand);

      $newPart  = false;
      $newBrand = false;


      if ($currentPart !== null && strtoupper($rowPart) !== strtoupper($currentPart)) {


        if ($rowCounter + 2 > $maxRowsPerPage) {
          $str .= $this->reporter->page_break();
          $str .= $this->reportHeaderSbc($config);
          $str .= $this->sbc_table_cols($layoutsize, $border, $font, $fontsize, $config);
          $rowCounter = 0;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($currentBrand . ' SUB TOTAL:', '200', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
        $str .= $this->reporter->col(number_format($subjan, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subfeb, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($submar, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subapr, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($submay, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subjun, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subjul, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subaug, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subsep, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($suboct, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subnov, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subdec, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subamt, $ab), '90', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $rowCounter += 1.0;


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($currentPart . ' SUB TOTAL:', '200', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
        $str .= $this->reporter->col(number_format($gsubjan, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubfeb, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubmar, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubapr, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubmay, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubjun, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubjul, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubaug, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubsep, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsuboct, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubnov, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubdec, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($gsubamt, $ab), '90', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $rowCounter += 1.0;

        $subjan  = 0;
        $subfeb  = 0;
        $submar  = 0;
        $subapr  = 0;
        $submay  = 0;
        $subjun  = 0;
        $subjul  = 0;
        $subaug  = 0;
        $subsep  = 0;
        $suboct  = 0;
        $subnov  = 0;
        $subdec  = 0;
        $subamt  = 0;
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

        $newPart  = true;
        $newBrand = true;
      } elseif ($currentBrand !== null && $rowBrand !== $currentBrand) {


        if ($rowCounter + 1 > $maxRowsPerPage) {
          $str .= $this->reporter->page_break();
          $str .= $this->reportHeaderSbc($config);
          $str .= $this->sbc_table_cols($layoutsize, $border, $font, $fontsize, $config);
          $rowCounter = 0;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($currentBrand . ' SUB TOTAL:', '200', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
        $str .= $this->reporter->col(number_format($subjan, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subfeb, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($submar, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subapr, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($submay, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subjun, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subjul, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subaug, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subsep, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($suboct, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subnov, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subdec, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($subamt, $ab), '90', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $rowCounter += 1.0;


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

        $newBrand = true;
      }

      if ($currentPart === null) {
        $newPart  = true;
        $newBrand = true;
      }

      $rowHeight = $lines;

      $rowsNeeded = $rowHeight;
      if ($newPart)  $rowsNeeded += 1.0;
      if ($newBrand) $rowsNeeded += 1.0;

      if ($rowCounter + $rowsNeeded > $rowsPerPage) {
        $str .= $this->reporter->page_break();
        $str .= $this->reportHeaderSbc($config);
        $str .= $this->sbc_table_cols($layoutsize, $border, $font, $fontsize, $config);
        $rowCounter = 0;


        $newPart = true;
        $newBrand = true;
      }


      if ($newPart) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($rowPart), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $rowCounter += 1.0;
      }


      if ($newBrand) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($rowBrand, '100', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $rowCounter += 1.0;
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($data->barcode), '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojan, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momar, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moapr, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momay, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojun, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojul, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moaug, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mosep, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mooct, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($monov, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($modec, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, $ab), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $rowCounter += $rowHeight;

      $subjan  += $data->mojan;
      $subfeb  += $data->mofeb;
      $submar  += $data->momar;
      $subapr  += $data->moapr;
      $submay  += $data->momay;
      $subjun  += $data->mojun;
      $subjul  += $data->mojul;
      $subaug  += $data->moaug;
      $subsep  += $data->mosep;
      $suboct  += $data->mooct;
      $subnov  += $data->monov;
      $subdec  += $data->modec;
      $subamt  += $amt;

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
      $gsubamt += $amt;

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
      $totalamt   += $amt;

      $currentPart  = $rowPart;
      $currentBrand = $rowBrand;
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($currentBrand . ' SUB TOTAL:', '200', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subjan, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subfeb, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($submar, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subapr, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($submay, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjun, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subjul, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subaug, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subsep, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($suboct, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subnov, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subdec, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($subamt, $ab), '90', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col($currentPart . ' SUB TOTAL:', '200', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjan, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubfeb, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmar, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubapr, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmay, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjun, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubjul, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubaug, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubsep, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsuboct, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnov, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubdec, $ab), '80', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamt, $ab), '90', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, $ab), '80', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, $ab), '80', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, $ab), '80', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, $ab), '80', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, $ab), '80', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $ab), '90', null, false, '1px solid', 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class