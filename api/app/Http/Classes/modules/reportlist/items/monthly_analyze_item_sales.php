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
        array_push($fields, 'luom', 'dagentname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'luom.action', 'replookupuom');
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
          'value' as analyzedby,
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
          '' as dwhname,
          '' as project, 
          0 as projectid, 
          '' as projectname,
          0 as deptid,
          '' as ddeptname, 
          '' as dept, 
          '' as deptname,
          '' as industry,
          '' as area";

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
      $filter1 .= " and client.area='".$area."'";
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

      if ($agent != "") {
        $agentid = $config['params']['dataparams']['agentid'];
        $filter1 .= " and agent.clientid=" . $agentid;
      }
    }
    $sort = "order by $agfield part, brand, barcode, itemname";
    if ($companyid == 60) { //transpower
      $sort = "order by itemname,barcode,part, brand";
    }

    $query = "select $agfield barcode,size,uom,category,groupid,category1, subcatname, part, brand, model,body, itemname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
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
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec, item.uom
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
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname, item.uom) as x
      group by $agfield part, brand, barcode, size, category, groupid,  model,body, itemname, yr,category1,subcatname, uom
      $sort";

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
      $filter1 .= " and client.area='".$area."'";
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

      if ($agent != "") {
        $agentid = $config['params']['dataparams']['agentid'];
        $filter1 .= " and agent.clientid=" . $agentid;
      }
    }

    $sort = "order by $agfield part, brand, barcode, itemname";
    if ($companyid == 60) { //transpower
      $sort = "order by itemname,barcode,part, brand";
    }

    $query = "select $agfield barcode, size, uom,category, groupid, part, brand,category1, subcatname, model,body, 
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
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, item.uom
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
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)=$year and item.isimport in $itemtype $filter $filter1 and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname,item.uom) as x 
      group by $agfield part, brand, barcode, size, category, groupid,  model,body, itemname, yr,category1,subcatname,uom
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
      $filter1 .= "and client.area='".$area."'";
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

      if ($agent != "") {
        $agentid = $config['params']['dataparams']['agentid'];
        $filter1 .= " and agent.clientid=" . $agentid;
      }
    }

    $sort = "order by $agfield part, brand, barcode, itemname";
    if ($companyid == 60) { //transpower
      $sort = "order by itemname,barcode,part, brand";
    }

    $query = "select $agfield barcode,size,uom,category, groupid,category1, subcatname, part, brand, model,body, itemname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
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
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, item.uom
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
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)= $year and item.isimport in $itemtype $filter $filter1  and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname, item.uom

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
      sum(case when month(head.dateid)=12 then $war else 0 end) as modec,item.category, item.uom
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
      where head.doc in ('sj','mj','sd','se','sf') and year(head.dateid)= $year and item.isimport in $itemtype $filter $filter1  and item.isofficesupplies=0
      group by $grpagent
      item.barcode, client.clientname,item.sizeid,
      ifnull(stockgrp.stockgrp_name,'NO GROUP'),
      ifnull(frontend_ebrands.brand_desc, 'NO BRAND'),
      ifnull(mm.model_name,'NO MODEL'),
      ifnull(parts.part_name,'NO PART'),
      item.body,item.itemname, year(head.dateid),
      item.category,frontend_ebrands.brand_desc,category1,subcatname,item.uom) as x
      group by $agfield part, brand, barcode, size, category, groupid,  model,body, itemname, yr,category1,subcatname, uom
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
      $str .= $this->reporter->col($data->size.'/'.$data->uom, '60', null, false, $border, 'TLRB', 'C', $font, $font_size9, '', '', '', '');
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
}//end class