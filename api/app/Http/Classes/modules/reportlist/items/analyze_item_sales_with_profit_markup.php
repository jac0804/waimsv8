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


class analyze_item_sales_with_profit_markup
{
  public $modulename = 'Analyze Item Sales With Profit Markup';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'ditemname', 'divsion', 'brandname', 'brandid', 'part', 'dwhname', 'categoryname', 'subcatname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'project', 'agentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'agentname.label', 'Sales Person');
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
        break;
      case 21: //kinggeorge
      case 23: //labsol cebu
      case 41: //LABSOL manila
      case 52: //technolab
        array_push($fields, 'dagentname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        $fields = ['radioposttype', 'radioreportitemtype'];
        $col2 = $this->fieldClass->create($fields);
        if ($companyid == 21) { //kinggeorge
          data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'orange']
          ]);
        } else {
          data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'orange'],
            ['label' => 'All', 'value' => '2', 'color' => 'orange']
          ]);
        }
        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
        break;

      default:
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        $fields = ['radioposttype', 'radioreportitemtype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioposttype.options', [
          ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Unposted', 'value' => '1', 'color' => 'orange'],
        ]);
        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
        break;
    }
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      0 as clientid,
      '' as client,
      '' as clientname,
      '' as ditemname,
      0 as itemid,
      '' as barcode,
      '' as groupid,
      '' as categoryid,
      '' as categoryname,
      '' as stockgrp,
      '' as brandid,
      '' as brandname,
      '' as partid,
      '' as partname,
      0 as whid,
      '' as wh,
      '' as whname,
      '0' as posttype,
      '(0,1)' as itemtype,
      '' as dclientname,
      '' as divsion,
      '' as brand,
      '' as part,
      '' as dwhname,
      '' as category,
      '' as subcatname,
      '' as subcat";

    if ($companyid == 21 || $companyid == 23 || $companyid == 41 || $companyid == 52) { //kinggeorge,labsol cebu, labsol manila,technolab
      $paramstr .= ",'' as dagentname,'' as agent,'' as agentname, 0 as agentid";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= "
        ,'' as agentname,
        '' as agent,
        0 as agentid,
        '' as project,
        '' as projectid,
        '' as projectname";
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
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    switch ($companyid) {
      case 12: //afti usd
      case 10: //afti
        $result = $this->afti_DefaultLayout($config);
        break;
      case 21: //kinggeorge
        $result = $this->kinggeorge_Layout($config);
        break;
      case 14: //majesty
        $result = $this->MAJESTY_Layout($config);
        break;
      case 23: //labsol
        $result = $this->LABSOL_Layout($config);
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
      case 10: //afti
      case 12: //afti usd
        $query = $this->afti_query($config);
        break;

      case 14: //majesty
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->MAJESTY_QUERY_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->MAJESTY_QUERY_UNPOSTED($config);
            break;
          default:
            $query = $this->MAJESTY_QUERY_ALL($config);
            break;
        }
        break;
      case 23: //labsol cebu
      case 52: //technolab
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->LABSOL_QUERY_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->LABSOL_QUERY_UNPOSTED($config);
            break;
          default:
            $query = $this->LABSOL_QUERY_ALL($config);
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
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }


  public function LABSOL_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $agent     = $config['params']['dataparams']['agent'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";
    $filter1 = "";


    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    if ($agent != "") {
      $filter .= " and agent.client = '$agent'";
    }


    $query = "select size,tr, groupid, brand, part, model,body,barcode, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty,
    ifnull(agentcode,'') as agentcode, ifnull(agentname,'') as agentname,docno
    
    from (
      select item.sizeid as size,'p' as tr, item.barcode, item.itemname, ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold , agent.client as agentcode, agent.clientname as agentname,head.docno
   
      from (((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      Where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name, agent.client, agent.clientname,head.docno

      union all

      select item.sizeid as size,'p' as tr, item.barcode, item.itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales,
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold, agent.client as agentcode, agent.clientname as agentname,head.docno

      from (((glhead as head 
      left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      Where head.doc='CM' and date(head.dateid) between '$start' and '$end' and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name , agent.client, agent.clientname,head.docno

    ) as ais
    group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname,agentcode,agentname,docno
    order by agentcode,agentname,groupid, brand, itemname ";

    return $query;
  }

  public function LABSOL_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $agent     = $config['params']['dataparams']['agent'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";
    $filter1 = "";


    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    if ($agent != "") {
      $filter .= " and agent.client = '$agent'";
    }



    $query = "select size,tr, groupid, brand, part, model,body, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty,
    ifnull(agentcode,'') as agentcode, ifnull(agentname,'') as agentname,docno
  
    from (
      select item.sizeid as size,'u' as tr, item.barcode, item.itemname,
      ifnull(stockgrp.stockgrp_name,'') as groupid, 
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount, 
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold, agent.client as agentcode, agent.clientname as agentname,head.docno
      
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid,cat.name , subcat.name  , agent.client, agent.clientname,head.docno
      
      UNION ALL

      select item.sizeid as size,'u' as tr, item.barcode, item.itemname, 
      ifnull(stockgrp.stockgrp_name,'') as groupid, 
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part, ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client, 
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales, 
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold, agent.client as agentcode, agent.clientname as agentname ,head.docno

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc='CM' and date(head.dateid) between '$start' and '$end' 
      and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name  , agent.client, agent.clientname,head.docno

    ) as ais
    group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname,agentcode,agentname,docno
    order by agentcode,agentname,groupid, brand, itemname";

    return $query;
  }
  private function LABSOL_QUERY_ALL($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $agent     = $config['params']['dataparams']['agent'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";
    $filter1 = "";


    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    if ($agent != "") {
      $filter .= " and agent.client = '$agent'";
    }

    $query = "select size,tr, groupid, brand, part, model,body,barcode, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty,
    ifnull(agentcode,'') as agentcode, ifnull(agentname,'') as agentname,docno
    
    from (
      select item.sizeid as size,'p' as tr, item.barcode, item.itemname, ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold , agent.client as agentcode, agent.clientname as agentname,head.docno

      from (((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name, agent.client, agent.clientname,head.docno

      union all

      select item.sizeid as size,'p' as tr, item.barcode, item.itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales,
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold, agent.client as agentcode, agent.clientname as agentname,head.docno

      from (((glhead as head
      left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc='CM' and date(head.dateid) between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name , agent.client, agent.clientname,head.docno

      union all

      select item.sizeid as size,'u' as tr, item.barcode, item.itemname,
      ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold, agent.client as agentcode, agent.clientname as agentname,head.docno

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid,cat.name , subcat.name  , agent.client, agent.clientname,head.docno

      UNION ALL

      select item.sizeid as size,'u' as tr, item.barcode, item.itemname,
      ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part, ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales,
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold, agent.client as agentcode, agent.clientname as agentname ,head.docno

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc='CM' and date(head.dateid) between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name  , agent.client, agent.clientname,head.docno ) as ais
    group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname,agentcode,agentname,docno
    order by agentcode,agentname,groupid, brand, itemname";
    return $query;
  }

  public function MAJESTY_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";



    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }


    $query = "select size,tr, groupid, brand, part, model,body,barcode, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty 
    
    from (
      select item.sizeid as size,'p' as tr, item.barcode, item.itemname, ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold 
   
      from (((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      Where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' and stock.isamt>0 and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name 

      union all

      select item.sizeid as size,'p' as tr, item.barcode, item.itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales,
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold 

      from (((glhead as head 
      left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      Where head.doc='CM' and date(head.dateid) between '$start' and '$end' and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name 

    ) as ais
    group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname 
    order by groupid, brand, itemname ";

    return $query;
  }

  public function MAJESTY_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";


    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }



    $query = "select size,tr, groupid, brand, part, model,body, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty 
  
    from (
      select item.sizeid as size,'u' as tr, item.barcode, item.itemname,
      ifnull(stockgrp.stockgrp_name,'') as groupid, 
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount, 
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold 
      
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid,cat.name , subcat.name 
      
      UNION ALL

      select item.sizeid as size,'u' as tr, item.barcode, item.itemname, 
      ifnull(stockgrp.stockgrp_name,'') as groupid, 
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part, ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client, 
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales, 
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold 

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc='CM' and date(head.dateid) between '$start' and '$end' 
      and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name 

    ) as ais
    group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname 
    order by groupid, brand, itemname";

    return $query;
  }
  private function MAJESTY_QUERY_ALL($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";



    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }


    $query = "

select size,tr, groupid, brand, part, model,body,barcode, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty 
  
    from (
      select item.sizeid as size,'u' as tr, item.barcode, item.itemname,
      ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold 

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

     Where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' 
     and '$end' and stock.isamt>0 and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid,cat.name , subcat.name 
      
      UNION ALL

      select item.sizeid as size,'u' as tr, item.barcode, item.itemname, 
      ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part, ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales, 
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where  head.doc='CM'  and date(head.dateid) between '$start' 
      and '$end' and stock.isamt>0 and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name

union all
 select item.sizeid as size,'p' as tr, item.barcode, item.itemname, ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold

      from (((glhead as head left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

     Where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$start' 
     and '$end' and stock.isamt>0 and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name

      union all

      select item.sizeid as size,'p' as tr, item.barcode, item.itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales,
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold 

      from (((glhead as head 
      left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.clientid=head.clientid)
      left join cntnum on cntnum.trno=head.trno
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.clientid=head.agentid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      Where  head.doc='CM'  and date(head.dateid) between '$start' 
      and '$end' and stock.isamt>0 and item.barcode<>'' $filter  and item.isofficesupplies=0

      group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name

    ) as ais
    group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname 
    order by groupid, brand, itemname";

    return $query;
  }
  public function default_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];

    $leftjoin = "";
    $filter = " and item.isimport in $loc";
    $innerfilter = '';

    $blnleftitem = false;

    if ($barcode != "") {
      $innerfilter = " and stock.itemid=" . $itemid;
    }

    if ($groupid != "") {
      $innerfilter = $innerfilter . " and item.groupid='$groupid'";
      $blnleftitem = true;
    }

    if ($category != "") {
      $innerfilter = $innerfilter . " and item.category='$category'";
      $blnleftitem = true;
    }

    if ($subcatname != "") {
      $innerfilter = $innerfilter . " and item.subcat='$subcatname'";
      $blnleftitem = true;
    }

    if ($brandname != "") {
      $innerfilter = $innerfilter . " and item.brand='$brand'";
      $blnleftitem = true;
    }

    if ($partid != "") {
      $innerfilter = $innerfilter . " and item.part='$partid'";
      $blnleftitem = true;
    }
    if ($wh != "") {
      $innerfilter = $innerfilter . " and stock.whid='$whid'";
    }
    if ($client != "") {
      $innerfilter = $innerfilter . " and head.clientid='$clientid'";
    }

    $filterdateid = 'head.dateid';
    if ($companyid == 19) { //housegem
      $filterdateid = 'head.deldate';
    }

    if ($companyid == 21) { //kinggeorge
      $agent     = $config['params']['dataparams']['agent'];
      $agentid     = $config['params']['dataparams']['agentid'];

      if ($agent != "") {
        $innerfilter .= " and head.agentid = $agentid";
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      $indus = $config['params']['dataparams']['industry'];

      if ($indus != "") {
        $innerfilter .= " and client.industry = '$indus'";
        $leftjoin .= " left join client on client.clientid=head.clientid";
      }
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $innerfilter .= " and item.projectid = $project";
      }
      if ($deptid != "") {
        $innerfilter .= " and head.deptid = $dept";
      }
    }

    if ($blnleftitem) {
      $leftjoin .= " left join item on item.itemid=stock.itemid";
    }

    // 2025.02.14 - FMM - revised query
    $query = "
          select item.color,item.sizeid as size,tr, groupid, brand.brand_desc as brand, part, model,body,barcode, itemname,category, subcat.name as subcatname, 
          ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
          ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty , ais.tax
          from (
          select 'P' as tr, stock.itemid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
          0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold ,head.tax
          from glhead as head left join glstock as stock on stock.trno=head.trno $leftjoin
          Where head.doc in ('sj','mj','sd','se','sf') and date(" . $filterdateid . ") between '$start' and '$end' 
          and stock.isamt>0 $innerfilter
          group by stock.itemid ,head.tax
          union all
          select 'P' as tr, stock.itemid, 0 as gsales, 0 as discount, sum(stock.ext) as sreturn, 0 as sales, 
          (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold ,head.tax
          from glhead as head left join glstock as stock on stock.trno=head.trno $leftjoin
          Where head.doc='CM' and date(head.dateid) between '$start' and '$end' 
          and stock.isamt>0 $innerfilter
          group by stock.itemid ,head.tax
          ) as ais left join item on item.itemid=ais.itemid
          left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
          left join model_masterfile as mm on mm.model_id = item.model
          left join part_masterfile as pp on pp.part_id = item.part
          left join frontend_ebrands as brand on brand.brandid = item.brand    
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where ''='' $filter
          group by size,tr, groupid, brand.brand_desc, part, model,body, barcode,itemname,category,subcatname ,tax,color
          order by groupid, brand, itemname";



    // $query = "select size,tr, groupid, brand, part, model,body,barcode, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    // ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty $outerfield

    // from (
    //   select item.sizeid as size,'p' as tr, item.barcode, item.itemname, ifnull(stockgrp.stockgrp_name,'') as groupid,
    //   cat.name as category, subcat.name as subcatname,
    //   brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
    //   client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
    //   0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold $innerfield

    //   from (((glhead as head left join glstock as stock on stock.trno=head.trno)
    //   left join item on item.itemid=stock.itemid
    //   left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    //   left join model_masterfile as mm on mm.model_id = item.model
    //   left join part_masterfile as pp on pp.part_id = item.part
    //   left join frontend_ebrands as brand on brand.brandid = item.brand
    //   left join client on client.clientid=head.clientid)
    //   left join cntnum on cntnum.trno=head.trno 
    //   left join client as wh on wh.clientid = stock.whid
    //   left join client as agent on agent.clientid=head.agentid
    //   left join itemcategory as cat on cat.line = item.category
    //   left join itemsubcategory as subcat on subcat.line = item.subcat

    //   Where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

    //   group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
    //   brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
    //   client.clientname, head.dateid, cat.name , subcat.name $innerfield

    //   union all

    //   select item.sizeid as size,'p' as tr, item.barcode, item.itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,
    //   cat.name as category, subcat.name as subcatname,
    //   brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,item.body, item.isimport, client.client,
    //   client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.isamt*stock.rrqty) as sreturn, 0 as sales,
    //   (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold $innerfield

    //   from (((glhead as head 
    //   left join glstock as stock on stock.trno=head.trno)
    //   left join item on item.itemid=stock.itemid
    //   left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    //   left join model_masterfile as mm on mm.model_id = item.model
    //   left join part_masterfile as pp on pp.part_id = item.part
    //   left join frontend_ebrands as brand on brand.brandid = item.brand
    //   left join client on client.clientid=head.clientid)
    //   left join cntnum on cntnum.trno=head.trno 
    //   left join client as wh on wh.clientid = stock.whid
    //   left join client as agent on agent.clientid=head.agentid
    //   left join itemcategory as cat on cat.line = item.category
    //   left join itemsubcategory as subcat on subcat.line = item.subcat

    //   Where head.doc='CM' and date(head.dateid) between '$start' and '$end' and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

    //   group by item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
    //   brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
    //   client.clientname, head.dateid, cat.name , subcat.name $innerfield

    // ) as ais
    // group by size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname $outerfield
    // order by groupid, brand, itemname ";

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];


    $filter = " and item.isimport in $loc";
    $filter1 = "";


    $innerfield = '';
    $outerfield = '';

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($partid != "") {
      $filter = $filter . " and pp.part_id='$partid'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }
    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    $filterdateid = 'head.dateid';
    if ($companyid == 19) { //housegem
      $filterdateid = 'head.deldate';
    }

    if ($companyid == 21) { //kinggeorge
      $agent     = $config['params']['dataparams']['agent'];

      if ($agent != "") {
        $filter .= " and agent.client = '$agent'";
      }

      $innerfield = ',head.tax';
      $outerfield = ',tax';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      $indus = $config['params']['dataparams']['industry'];

      if ($indus != "") {
        $filter1 .= " and client.industry = '$indus'";
      }
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

    $query = "select color,size,tr, groupid, brand, part, model,body, itemname,category,subcatname, ifnull(sum(gsales),0) as gsales, ifnull(sum(discount),0) as disc,
    ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, ifnull(sum(qtysold),0) as qty $outerfield
  
    from (
      select item.color,item.sizeid as size,'u' as tr, item.barcode, item.itemname,
      ifnull(stockgrp.stockgrp_name,'') as groupid, 
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part,ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client,
      client.clientname, head.dateid, sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount, 
      0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold $innerfield
      
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc in ('sj','mj','sd','se','sf') and date(" . $filterdateid . ") between '$start' and '$end' 
      and stock.isamt>0 and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.color,item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid,cat.name , subcat.name $innerfield
      
      UNION ALL

      select item.color,item.sizeid as size,'u' as tr, item.barcode, item.itemname, 
      ifnull(stockgrp.stockgrp_name,'') as groupid, 
      cat.name as category, subcat.name as subcatname,
      brand.brand_desc as brand, item.class, pp.part_name as part, ifnull(mm.model_name,'') as model,
      item.body, item.isimport, client.client, 
      client.clientname, head.dateid, 0 as gsales, 0 as discount, sum(stock.ext) as sreturn, 0 as sales, 
      (sum(stock.cost*stock.qty)*-1) as cogs, (sum(stock.qty)*-1) as qtysold $innerfield

      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as mm on mm.model_id = item.model
      left join part_masterfile as pp on pp.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
      left join client on client.client=head.client)
      left join cntnum on cntnum.trno=head.trno 
      left join client as wh on wh.clientid = stock.whid
      left join client as agent on agent.client=head.agent
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat

      where head.doc='CM' and date(head.dateid) between '$start' and '$end' 
      and item.barcode<>'' $filter $filter1 and item.isofficesupplies=0

      group by item.color,item.sizeid, item.barcode, item.itemname, stockgrp.stockgrp_name,
      brand.brand_desc, item.class, pp.part_name,mm.model_name,item.body, item.isimport, client.client,
      client.clientname, head.dateid, cat.name , subcat.name $innerfield

    ) as ais
    group by color,size,tr, groupid, brand, part, model,body, barcode,itemname,category,subcatname $outerfield
    order by groupid, brand, itemname";

    return $query;
  }

  public function afti_query($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];

    $filter   = "";

    if ($agent != "") {
      $filter .= " and head.agentid = '$agentid'";
    }

    if ($project != "") {
      $filter .= " and item.projectid = '$projectid'";
    }

    $query = "select docno, yourref, clientname, postdate, barcode, itemname, itemgroup, brandname, ifnull(sum(gsales),0) as gsales,
    ifnull(sum(discount),0) as disc,ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, 
    ifnull(sum(qtysold),0) as qty
    from (select head.docno, head.yourref, head.clientname, date(cnt.postdate) as postdate, item.barcode, item.itemname, 
    ifnull(proj.name,'') as itemgroup, ifnull(brand.brand_desc,'') as brandname,
    sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
    0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid
    left join projectmasterfile as proj on proj.line = item.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join cntnum as cnt on cnt.trno = head.trno
    left join iteminfo as info on info.line = stock.line
    where head.doc in ('sj','ai') and stock.isamt>0 and item.barcode<>'' and date(cnt.postdate) between '$start' and '$end' $filter
    group by head.docno, head.yourref, head.clientname, date(cnt.postdate), item.barcode, item.itemname, 
    proj.name, brand.brand_desc
    ) as x
    group by docno, yourref, clientname, postdate, barcode, itemname, itemgroup, brandname";

    return $query;
  }

  //START DEFAULT LAYOUT
  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($companyid == 21) { //kinggeorge
      $agentname   = $config['params']['dataparams']['agentname'];
      $agent   = $config['params']['dataparams']['agent'];

      if ($agentname == "") {
        $agentname = 'ALL';
      }
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];

      if ($indus == "") {
        $indus = 'ALL';
      }
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
    if ($partname == "") {
      $partname = "ALL";
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES WITH PROFIT MARKUP', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Warehouse: ' . strtoupper($whname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Industry: ' . strtoupper($indus), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Item Type: ' . strtoupper($itemtype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Item Type: ' . strtoupper($itemtype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Warehouse: ' . strtoupper($wh), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      }
      if ($companyid == 21) { //kinggeorge
        if ($agentname != '') {
          $str .= $this->reporter->col('Agent : ' . $agentname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
        }
      }
      $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->pagenumber('Page', null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<BR>ITEM DESCRIPTION', '175', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AMOUNT<BR>DISCOUNT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>RETURNS', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('NET<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('COST OF<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>PROFIT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs COST', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('UNITS<BR>SOLD', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>PRICE', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>COST', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $totalgrosssales = 0;
    $totalamountdiscount = 0;
    $totalreturns = 0;
    $totalnetsales = 0;
    $totalcostsales = 0;
    $totalgrossprofit = 0;
    $totalmarginvscost = 0;
    $totalmarginvssales = 0;
    $totalunitsold = 0;
    $totalaverageprice = 0;
    $totalaveragecost = 0;

    $group = "";
    $brand = "";

    $netsales = 0;
    $grossprofit = 0;
    $marginvscost = 0;
    $marginvssales = 0;
    $averageprice = 0;
    $averagecost = 0;

    $subgrosssales = 0;
    $subamountdiscount = 0;
    $subreturns = 0;
    $subnetsales = 0;
    $subcostsales = 0;
    $subgrossprofit = 0;
    $submarginvscost = 0;
    $submarginvssales = 0;
    $subunitsold = 0;
    $subaverageprice = 0;
    $subaveragecost = 0;
    //part
    $gsubgrosssales = 0;
    $gsubamountdiscount = 0;
    $gsubreturns = 0;
    $gsubnetsales = 0;
    $gsubcostsales = 0;
    $gsubgrossprofit = 0;
    $gsubmarginvscost = 0;
    $gsubmarginvssales = 0;
    $gsubunitsold = 0;
    $gsubaverageprice = 0;
    $gsubaveragecost = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if ($group == strtoupper($data->groupid)) {
        $group = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {
            if ($config['params']['companyid'] == 19) { //housegem
              $submarginvscost = $subcostsales != 0 ? ($subgrossprofit / $subcostsales) * 100 : 0;
              $submarginvssales = $subnetsales != 0 ? ($subgrossprofit / $subnetsales) * 100 : 0;
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        } #brand condition  
      } else {
        if ($brand != '') {
          if (strtoupper($brand) == strtoupper($data->brand)) {
          } else {
            if ($config['params']['companyid'] == 19) { //housegem
              $submarginvscost = $subcostsales != 0 ? ($subgrossprofit / $subcostsales) * 100 : 0;
              $submarginvssales = $subnetsales != 0 ? ($subgrossprofit / $subnetsales) * 100 : 0;
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
        }
        if ($group != '') {
        }
        $group = $data->groupid;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        }
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($brand, '175', null, false, $border, '', 'L', $font, '9', 'Bi', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->endrow();



      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }
      $str .= $this->reporter->startrow();
      if ($companyid == 47) { //kitchenstar
        $str .= $this->reporter->col($data->itemname . ' ' . $data->color . ' ' . $data->size, '175', '', '', $border, '', 'L', $font, '9', '', '', '');
      } else {
        $str .= $this->reporter->col($data->itemname, '175', '', '', $border, '', 'L', $font, '9', '', '', '');
      }


      $str .= $this->reporter->col($gsales, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($disc, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($sreturn, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($netsales, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($cogs, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($grossprofit, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvscost, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvssales, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averageprice, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averagecost, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');

      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }

      $totalgrosssales = $totalgrosssales + $data->gsales;
      $totalamountdiscount = $totalamountdiscount + $data->disc;
      $totalreturns = $totalreturns + $data->sreturn;
      $totalnetsales = $totalnetsales + $netsales;
      $totalcostsales = $totalcostsales + $data->cogs;
      $totalgrossprofit = $totalgrossprofit + $grossprofit;
      $totalmarginvscost = $totalmarginvscost + $marginvscost;
      $totalmarginvssales = $totalmarginvssales + $marginvssales;
      $totalunitsold = $totalunitsold + $data->qty;
      $totalaverageprice = $totalaverageprice + $averageprice;
      $totalaveragecost = $totalaveragecost + $averagecost;


      $subgrosssales = $subgrosssales + $data->gsales;
      $subamountdiscount = $subamountdiscount + $data->disc;
      $subreturns = $subreturns + $data->sreturn;
      $subnetsales = $subnetsales + $netsales;
      $subcostsales = $subcostsales + $data->cogs;
      $subgrossprofit = $subgrossprofit + $grossprofit;
      $submarginvscost = $submarginvscost + $marginvscost;
      $submarginvssales = $submarginvssales + $marginvssales;
      $subunitsold = $subunitsold + $data->qty;
      $subaverageprice = $subaverageprice + $averageprice;
      $subaveragecost = $subaveragecost + $averagecost;
      //part
      $gsubgrosssales = $subgrosssales + $gsales;
      $gsubamountdiscount = $subamountdiscount + $disc;
      $gsubreturns = $subreturns + $sreturn;
      $gsubnetsales = $subnetsales + $netsales;
      $gsubcostsales = $subcostsales + $cogs;
      $gsubgrossprofit = $subgrossprofit + $grossprofit;
      $gsubmarginvscost = $submarginvscost + $marginvscost;
      $gsubmarginvssales = $submarginvssales + $marginvssales;
      $gsubunitsold = $subunitsold + $data->qty;
      $gsubaverageprice = $subaverageprice + $averageprice;
      $gsubaveragecost = $subaveragecost + $averagecost;


      $brand = strtoupper($data->brand);
      $group = $data->groupid;

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

    if ($config['params']['companyid'] == 19) { //housegem
      $submarginvscost = $subcostsales != 0 ? ($subgrossprofit / $subcostsales) * 100 : 0;
      $submarginvssales = $subnetsales != 0 ? ($subgrossprofit / $subnetsales) * 100 : 0;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();

    if ($config['params']['companyid'] == 19) { //housegem
      $gsubmarginvscost = $gsubcostsales != 0 ? ($gsubgrossprofit / $gsubcostsales) * 100 : 0;
      $gsubmarginvssales = $gsubnetsales != 0 ? ($gsubgrossprofit / $gsubnetsales) * 100 : 0;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($group . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();

    if ($config['params']['companyid'] == 19) { //housegem
      $totalmarginvscost = $totalcostsales != 0 ? ($totalgrossprofit / $totalcostsales) * 100 : 0;
      $totalmarginvssales = $totalnetsales != 0 ? ($totalgrossprofit / $totalnetsales) * 100 : 0;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '175', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrosssales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamountdiscount, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalreturns, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetsales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcostsales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrossprofit, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvscost, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvssales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  //END DEFAULT LAYOUT

  //START LABSOL LAYOUT
  private function LABSOL_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($companyid == 21) { //kinggeorge
      $agentname   = $config['params']['dataparams']['agentname'];
      $agent   = $config['params']['dataparams']['agent'];

      if ($agentname == "") {
        $agentname = 'ALL';
      }
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];

      if ($indus == "") {
        $indus = 'ALL';
      }
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
    if ($partname == "") {
      $partname = "ALL";
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES WITH PROFIT MARKUP', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Warehouse: ' . strtoupper($whname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Industry: ' . strtoupper($indus), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Item Type: ' . strtoupper($itemtype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Item Type: ' . strtoupper($itemtype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->col('Warehouse: ' . strtoupper($wh), null, null, '', $border, '', 'l', $font, '10', '', '', '');
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      }
      if ($companyid == 21) { //kinggeorge
        if ($agentname != '') {
          $str .= $this->reporter->col('Agent : ' . $agentname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
        }
      }
      $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->pagenumber('Page', null, null, '', $border, '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->endtable();
    return $str;
  }

  private function LABSOL_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<BR>AGENT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>DOCNO', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>ITEM DESCRIPTION', '175', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AMOUNT<BR>DISCOUNT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>RETURNS', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('NET<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('COST OF<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>PROFIT', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs COST', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs SALES', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('UNITS<BR>SOLD', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>PRICE', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>COST', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');

    return $str;
  }

  public function LABSOL_Layout($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->LABSOL_displayHeader($config);

    $str .= $this->LABSOL_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);



    $totalgrosssales = 0;
    $totalamountdiscount = 0;
    $totalreturns = 0;
    $totalnetsales = 0;
    $totalcostsales = 0;
    $totalgrossprofit = 0;
    $totalmarginvscost = 0;
    $totalmarginvssales = 0;
    $totalunitsold = 0;
    $totalaverageprice = 0;
    $totalaveragecost = 0;

    $group = "";
    $brand = "";
    $agent = "";

    $netsales = 0;
    $grossprofit = 0;
    $marginvscost = 0;
    $marginvssales = 0;
    $averageprice = 0;
    $averagecost = 0;

    $subgrosssales = 0;
    $subamountdiscount = 0;
    $subreturns = 0;
    $subnetsales = 0;
    $subcostsales = 0;
    $subgrossprofit = 0;
    $submarginvscost = 0;
    $submarginvssales = 0;
    $subunitsold = 0;
    $subaverageprice = 0;
    $subaveragecost = 0;
    //part
    $gsubgrosssales = 0;
    $gsubamountdiscount = 0;
    $gsubreturns = 0;
    $gsubnetsales = 0;
    $gsubcostsales = 0;
    $gsubgrossprofit = 0;
    $gsubmarginvscost = 0;
    $gsubmarginvssales = 0;
    $gsubunitsold = 0;
    $gsubaverageprice = 0;
    $gsubaveragecost = 0;

    //agent
    $agentsubgrosssales = 0;
    $agentsubamountdiscount = 0;
    $agentsubreturns = 0;
    $agentsubnetsales = 0;
    $agentsubcostsales = 0;
    $agentsubgrossprofit = 0;
    $agentsubmarginvscost = 0;
    $agentsubmarginvssales = 0;
    $agentsubunitsold = 0;
    $agentsubaverageprice = 0;
    $agentsubaveragecost = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if ($group == strtoupper($data->groupid)) {
        $group = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {
            if ($config['params']['companyid'] == 19) { //housegem
              $submarginvscost = $subcostsales != 0 ? ($subgrossprofit / $subcostsales) * 100 : 0;
              $submarginvssales = $subnetsales != 0 ? ($subgrossprofit / $subnetsales) * 100 : 0;
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        } #brand condition  
      } else {
        if ($brand != '') {
          if (strtoupper($brand) == strtoupper($data->brand)) {
          } else {
            if ($config['params']['companyid'] == 19) { //housegem
              $submarginvscost = $subcostsales != 0 ? ($subgrossprofit / $subcostsales) * 100 : 0;
              $submarginvssales = $subnetsales != 0 ? ($subgrossprofit / $subnetsales) * 100 : 0;
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
        }
        if ($group != '') {
        }
        $group = $data->groupid;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        }
      }

      if (strtoupper($agent) == strtoupper($data->agentname) || (strtoupper($agent) == 'NO AGENT' && $data->agentname == '')) {
        $agent = "";
      } else {
        if ($agent != '') {

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '75', null, false, '1px dotted ', '', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px dotted ', '', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col($agent . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubgrossprofit, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubmarginvscost, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col(number_format($agentsubmarginvssales, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
          $str .= $this->reporter->endrow();
        }
        //agent
        $agentsubgrosssales = 0;
        $agentsubamountdiscount = 0;
        $agentsubreturns = 0;
        $agentsubnetsales = 0;
        $agentsubcostsales = 0;

        $agentsubgrossprofit = 0;
        $agentsubmarginvscost = 0;
        $agentsubmarginvssales = 0;
        $agentsubunitsold = 0;
        $agentsubaverageprice = 0;

        $agentsubaveragecost = 0;

        $agent = strtoupper($data->agentname);
      } #agent condition  

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col($brand, '175', null, false, $border, '', 'L', $font, '9', 'Bi', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->endrow();



      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->agentname, '75', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->docno, '75', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->itemname, '175', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($gsales, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($disc, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($sreturn, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($netsales, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($cogs, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($grossprofit, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvscost, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvssales, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averageprice, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averagecost, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');

      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }

      $totalgrosssales = $totalgrosssales + $data->gsales;
      $totalamountdiscount = $totalamountdiscount + $data->disc;
      $totalreturns = $totalreturns + $data->sreturn;
      $totalnetsales = $totalnetsales + $netsales;
      $totalcostsales = $totalcostsales + $data->cogs;
      $totalgrossprofit = $totalgrossprofit + $grossprofit;
      $totalmarginvscost = $totalmarginvscost + $marginvscost;
      $totalmarginvssales = $totalmarginvssales + $marginvssales;
      $totalunitsold = $totalunitsold + $data->qty;
      $totalaverageprice = $totalaverageprice + $averageprice;
      $totalaveragecost = $totalaveragecost + $averagecost;


      $subgrosssales = $subgrosssales + $data->gsales;
      $subamountdiscount = $subamountdiscount + $data->disc;
      $subreturns = $subreturns + $data->sreturn;
      $subnetsales = $subnetsales + $netsales;
      $subcostsales = $subcostsales + $data->cogs;
      $subgrossprofit = $subgrossprofit + $grossprofit;
      $submarginvscost = $submarginvscost + $marginvscost;
      $submarginvssales = $submarginvssales + $marginvssales;
      $subunitsold = $subunitsold + $data->qty;
      $subaverageprice = $subaverageprice + $averageprice;
      $subaveragecost = $subaveragecost + $averagecost;
      //part
      $gsubgrosssales = $subgrosssales + $gsales;
      $gsubamountdiscount = $subamountdiscount + $disc;
      $gsubreturns = $subreturns + $sreturn;
      $gsubnetsales = $subnetsales + $netsales;
      $gsubcostsales = $subcostsales + $cogs;
      $gsubgrossprofit = $subgrossprofit + $grossprofit;
      $gsubmarginvscost = $submarginvscost + $marginvscost;
      $gsubmarginvssales = $submarginvssales + $marginvssales;
      $gsubunitsold = $subunitsold + $data->qty;
      $gsubaverageprice = $subaverageprice + $averageprice;
      $gsubaveragecost = $subaveragecost + $averagecost;

      //agent
      $agentsubgrosssales = $agentsubgrosssales + $data->gsales;
      $agentsubamountdiscount = $agentsubamountdiscount + $data->disc;
      $agentsubreturns = $agentsubreturns + $data->sreturn;
      $agentsubnetsales = $agentsubnetsales + $netsales;
      $agentsubcostsales = $agentsubcostsales + $data->cogs;

      $agentsubgrossprofit = $agentsubgrossprofit + $grossprofit;
      $agentsubmarginvscost = $agentsubmarginvscost + $marginvscost;
      $agentsubmarginvssales = $agentsubmarginvssales + $marginvssales;
      $agentsubunitsold = $agentsubunitsold + $data->qty;
      $agentsubaverageprice = $agentsubaverageprice + $averageprice;
      $agentsubaveragecost = $agentsubaveragecost + $averagecost;

      $brand = strtoupper($data->brand);
      if ($data->agentname == '') {
        $agent = 'NO AGENT';
      } else {
        $agent = strtoupper($data->agentname);
      }

      $group = $data->groupid;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->LABSOL_displayHeader($config);
        }
        $str .= $this->LABSOL_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    if ($config['params']['companyid'] == 19) { //housegem
      $submarginvscost = $subcostsales != 0 ? ($subgrossprofit / $subcostsales) * 100 : 0;
      $submarginvssales = $subnetsales != 0 ? ($subgrossprofit / $subnetsales) * 100 : 0;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', '', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', '', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col($agent . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubgrossprofit, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubmarginvscost, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($agentsubmarginvssales, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subgrossprofit, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvscost, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvssales, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();

    if ($config['params']['companyid'] == 19) { //housegem
      $gsubmarginvscost = $gsubcostsales != 0 ? ($gsubgrossprofit / $gsubcostsales) * 100 : 0;
      $gsubmarginvssales = $gsubnetsales != 0 ? ($gsubgrossprofit / $gsubnetsales) * 100 : 0;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col($group . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrossprofit, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvscost, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvssales, 2), '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();

    if ($config['params']['companyid'] == 19) { //housegem
      $totalmarginvscost = $totalcostsales != 0 ? ($totalgrossprofit / $totalcostsales) * 100 : 0;
      $totalmarginvssales = $totalnetsales != 0 ? ($totalgrossprofit / $totalnetsales) * 100 : 0;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '175', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrosssales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamountdiscount, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalreturns, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetsales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcostsales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrossprofit, 2), '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvscost, 2), '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvssales, 2), '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  //END LABSOL LAYOUT


  //START AFTI LAYOUT
  private function afti_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];
    $agentid = $config['params']['dataparams']['agentid'];
    $project = $config['params']['dataparams']['project'];
    $projectname = $config['params']['dataparams']['projectname'];
    $projectid = $config['params']['dataparams']['projectid'];

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
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent Name : ' . ($agent != '' ? $agentname : 'ALL'), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Item Group : ' . ($project != '' ? $projectname : 'ALL'), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Invoice', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Customer PO', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Customer', '100', '', '', $border, 'TB', 'L', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Posting Date', '100', '', '', $border, 'TB', 'L', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Item Code', '100', '', '', $border, 'TB', 'L', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Item Name', '100', '', '', $border, 'TB', 'L', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Item Group', '100', '', '', $border, 'TB', 'L', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Brand', '100', '', '', $border, 'TB', 'L', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Qty', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Avg. Selling', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Avg. Buying', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Selling', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Buying', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Gross Profit', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('Gross profit %', '100', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function afti_DefaultLayout($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];
    $agentid = $config['params']['dataparams']['agentid'];
    $project = $config['params']['dataparams']['project'];
    $projectname = $config['params']['dataparams']['projectname'];
    $projectid = $config['params']['dataparams']['projectid'];

    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;
    $result = $this->reportDefault($config);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1200';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->afti_displayHeader($config);


    $gqty = $gselling = $gbuying = $gnet = $gcogs = $gprofitt =  0;

    foreach ($result as $key => $data) {

      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $data->cogs / $data->qty;
      } else {
        $averagecost = 0;
      }

      $totp = $averageprice * $data->qty;
      $totc = $averagecost * $data->qty;
      $grossprofit = $totp - $totc;
      $pgrossprofit = ($grossprofit / $totp) * 100;

      $gqty += $data->qty;
      $gselling += $averageprice;
      $gbuying += $averagecost;
      $gnet += $totp;
      $gcogs += $totc;
      $gprofitt += $grossprofit;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->clientname, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->postdate, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->itemgroup, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->brandname, '100', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 0), '100', '', '', $border, '', 'C', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averageprice, $decimalprice), '100', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averagecost, $decimalprice), '100', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($totp, $decimalprice), '100', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($totc, $decimalprice), '100', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($grossprofit, $decimalprice), '100', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($pgrossprofit, 2), '100', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->afti_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col('GRAND  TOTAL', '100', '', '', $border, 'T', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->col(number_format($gqty, 0), '100', '', '', $border, 'T', 'C', $font, '9', '', '', '');
    $str .= $this->reporter->col(number_format($gselling, $decimalprice), '100', '', '', $border, 'T', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->col(number_format($gbuying, $decimalprice), '100', '', '', $border, 'T', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->col(number_format($gnet, $decimalprice), '100', '', '', $border, 'T', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->col(number_format($gcogs, $decimalprice), '100', '', '', $border, 'T', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->col(number_format($gprofitt, $decimalprice), '100', '', '', $border, 'T', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();

    return $str;
  }
  //END AFTI LAYOUT

  //START KINGGEORGE LAYOUT
  private function kinggeorge_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


    $agentname   = $config['params']['dataparams']['agentname'];
    $agent   = $config['params']['dataparams']['agent'];

    if ($agentname == "") {
      $agentname = 'ALL';
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
    if ($partname == "") {
      $partname = "ALL";
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES WITH PROFIT MARKUP', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . strtoupper($client), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Group : ' . strtoupper($groupname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Item Type: ' . strtoupper($itemtype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Warehouse: ' . strtoupper($wh), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    }
    if ($companyid == 21) { //kinggeorge
      if ($agentname != '') {
        $str .= $this->reporter->col('Agent : ' . $agentname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
      }
    }
    $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endtable();
    return $str;
  }

  private function kinggeorge_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<BR>ITEM DESCRIPTION', '175', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AMOUNT<BR>DISCOUNT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>RETURNS', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('NET<BR>SALES', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('NET OF<BR>VAT', '80', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');

    $str .= $this->reporter->col('COST OF<BR>SALES', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>PROFIT', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs COST', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs SALES', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('UNITS<BR>SOLD', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');

    $str .= $this->reporter->col('AVERAGE<BR>PRICE', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>COST', '65', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');

    return $str;
  }

  public function kinggeorge_Layout($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_displayHeader($config);

    $str .= $this->kinggeorge_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $totalgrosssales = 0;
    $totalamountdiscount = 0;
    $totalreturns = 0;
    $totalnetsales = 0;
    $totalnetvat = 0;
    $totalcostsales = 0;
    $totalgrossprofit = 0;
    $totalmarginvscost = 0;
    $totalmarginvssales = 0;
    $totalunitsold = 0;
    $totalaverageprice = 0;
    $totalaveragecost = 0;

    $group = "";
    $brand = "";

    $netsales = 0;
    $netvat = 0;
    $grossprofit = 0;
    $marginvscost = 0;
    $marginvssales = 0;
    $averageprice = 0;
    $averagecost = 0;

    $subgrosssales = 0;
    $subamountdiscount = 0;
    $subreturns = 0;
    $subnetsales = 0;
    $subnetvat = 0;
    $subcostsales = 0;
    $subgrossprofit = 0;
    $submarginvscost = 0;
    $submarginvssales = 0;
    $subunitsold = 0;
    $subaverageprice = 0;
    $subaveragecost = 0;
    //part
    $gsubgrosssales = 0;
    $gsubamountdiscount = 0;
    $gsubreturns = 0;
    $gsubnetsales = 0;
    $gsubnetvat = 0;
    $gsubcostsales = 0;
    $gsubgrossprofit = 0;
    $gsubmarginvscost = 0;
    $gsubmarginvssales = 0;
    $gsubunitsold = 0;
    $gsubaverageprice = 0;
    $gsubaveragecost = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if ($group == strtoupper($data->groupid)) {
        $group = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetvat, 2), '80', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

            $str .= $this->reporter->col(number_format($subcostsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

            $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subnetvat = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubnetvat = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        } #brand condition  
      } else {
        if ($brand != '') {
          if (strtoupper($brand) == strtoupper($data->brand)) {
          } else {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetvat, 2), '80', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

            $str .= $this->reporter->col(number_format($subcostsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

            $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
        }
        if ($group != '') {
        }
        $group = $data->groupid;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subnetvat = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubnetvat = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        }
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($brand, '175', null, false, $border, '', 'L', $font, '9', 'Bi', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, '9', '', '', '', '');

      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');

      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, '9', '', '', '', '');
      $str .= $this->reporter->endrow();



      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netvat = 0;
      if ($data->tax == 12) {
        $netvat = ($data->sales - $data->sreturn)  / 1.12;
      } else {
        $netvat = ($data->sales - $data->sreturn);
      }

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->itemname, '175', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($gsales, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($disc, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($sreturn, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($netsales, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($netvat, 2), '80', '', '', $border, '', 'R', $font, '9', '', '', '');


      $str .= $this->reporter->col($cogs, '65', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($grossprofit, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvscost, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvssales, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');

      $str .= $this->reporter->col(number_format($averageprice, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averagecost, 2), '65', '', '', $border, '', 'R', $font, '9', '', '', '');

      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      if ($data->tax == 12) {
        $netvat = ($data->sales - $data->sreturn)  / 1.12;
      } else {
        $netvat = ($data->sales - $data->sreturn);
      }

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }

      $totalgrosssales = $totalgrosssales + $data->gsales;
      $totalamountdiscount = $totalamountdiscount + $data->disc;
      $totalreturns = $totalreturns + $data->sreturn;
      $totalnetsales = $totalnetsales + $netsales;
      $totalnetvat = $totalnetvat + $netvat;
      $totalcostsales = $totalcostsales + $data->cogs;
      $totalgrossprofit = $totalgrossprofit + $grossprofit;
      $totalmarginvscost = $totalmarginvscost + $marginvscost;
      $totalmarginvssales = $totalmarginvssales + $marginvssales;
      $totalunitsold = $totalunitsold + $data->qty;
      $totalaverageprice = $totalaverageprice + $averageprice;
      $totalaveragecost = $totalaveragecost + $averagecost;


      $subgrosssales = $subgrosssales + $data->gsales;
      $subamountdiscount = $subamountdiscount + $data->disc;
      $subreturns = $subreturns + $data->sreturn;
      $subnetsales = $subnetsales + $netsales;
      $subnetvat = $subnetvat + $netvat;
      $subcostsales = $subcostsales + $data->cogs;
      $subgrossprofit = $subgrossprofit + $grossprofit;
      $submarginvscost = $submarginvscost + $marginvscost;
      $submarginvssales = $submarginvssales + $marginvssales;
      $subunitsold = $subunitsold + $data->qty;
      $subaverageprice = $subaverageprice + $averageprice;
      $subaveragecost = $subaveragecost + $averagecost;
      //part
      $gsubgrosssales = $subgrosssales + $gsales;
      $gsubamountdiscount = $subamountdiscount + $disc;
      $gsubreturns = $subreturns + $sreturn;
      $gsubnetsales = $subnetsales + $netsales;
      $gsubnetvat = $subnetvat + $netvat;
      $gsubcostsales = $subcostsales + $cogs;
      $gsubgrossprofit = $subgrossprofit + $grossprofit;
      $gsubmarginvscost = $submarginvscost + $marginvscost;
      $gsubmarginvssales = $submarginvssales + $marginvssales;
      $gsubunitsold = $subunitsold + $data->qty;
      $gsubaverageprice = $subaverageprice + $averageprice;
      $gsubaveragecost = $subaveragecost + $averagecost;


      $brand = strtoupper($data->brand);
      $group = $data->groupid;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->kinggeorge_displayHeader($config);
        }
        $str .= $this->kinggeorge_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subnetsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subnetvat, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

    $str .= $this->reporter->col(number_format($subcostsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subgrossprofit, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvscost, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvssales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

    $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($group . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnetsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnetvat, 2), '80', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

    $str .= $this->reporter->col(number_format($gsubcostsales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrossprofit, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvscost, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvssales, 2), '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');

    $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '65', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '175', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrosssales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamountdiscount, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalreturns, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetsales, 2), '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetvat, 2), '80', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');

    $str .= $this->reporter->col(number_format($totalcostsales, 2), '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrossprofit, 2), '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvscost, 2), '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvssales, 2), '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');

    $str .= $this->reporter->col('', '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  //END KINGGEORGE LAYOUT

  //START MAJESTY LAYOUT
  private function MAJESTY_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
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
    if ($partname == "") {
      $partname = "ALL";
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
    $str .= $this->reporter->col('ANALYZE ITEM SALES WITH PROFIT MARKUP', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . strtoupper($client), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Item :' . strtoupper($barcode), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Group : ' . strtoupper($groupname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brandname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Part :' . strtoupper($partname), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Item Type: ' . strtoupper($itemtype), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->col('Warehouse: ' . strtoupper($wh), null, null, '', $border, '', 'l', $font, '10', '', '', '');
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', $padding, $margin);
    }

    $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, '', $border, '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endtable();
    return $str;
  }

  private function MAJESTY_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<BR>ITEM CODE', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>ITEM DESCRIPTION', '175', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AMOUNT<BR>DISCOUNT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('<BR>RETURNS', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('NET<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('COST OF<BR>SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GROSS<BR>PROFIT', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs COST', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('%MARGIN<BR>vs SALES', '75', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('UNITS<BR>SOLD', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>PRICE', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('AVERAGE<BR>COST', '50', '', '', $border, 'TB', 'C', $font, '9', 'B', '', '');

    return $str;
  }

  public function MAJESTY_Layout($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname   = $config['params']['dataparams']['partname'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);
    $str .= $this->MAJESTY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $totalgrosssales = 0;
    $totalamountdiscount = 0;
    $totalreturns = 0;
    $totalnetsales = 0;
    $totalcostsales = 0;
    $totalgrossprofit = 0;
    $totalmarginvscost = 0;
    $totalmarginvssales = 0;
    $totalunitsold = 0;
    $totalaverageprice = 0;
    $totalaveragecost = 0;

    $group = "";
    $brand = "";

    $netsales = 0;
    $grossprofit = 0;
    $marginvscost = 0;
    $marginvssales = 0;
    $averageprice = 0;
    $averagecost = 0;

    $subgrosssales = 0;
    $subamountdiscount = 0;
    $subreturns = 0;
    $subnetsales = 0;
    $subcostsales = 0;
    $subgrossprofit = 0;
    $submarginvscost = 0;
    $submarginvssales = 0;
    $subunitsold = 0;
    $subaverageprice = 0;
    $subaveragecost = 0;
    //part
    $gsubgrosssales = 0;
    $gsubamountdiscount = 0;
    $gsubreturns = 0;
    $gsubnetsales = 0;
    $gsubcostsales = 0;
    $gsubgrossprofit = 0;
    $gsubmarginvscost = 0;
    $gsubmarginvssales = 0;
    $gsubunitsold = 0;
    $gsubaverageprice = 0;
    $gsubaveragecost = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      if ($group == strtoupper($data->groupid)) {
        $group = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          if ($brand != '') {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        } #brand condition  
      } else {
        if ($brand != '') {
          if (strtoupper($brand) == strtoupper($data->brand)) {
          } else {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
            $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($subgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col(number_format($submarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
            $str .= $this->reporter->endrow();
          }
        }
        if ($group != '') {
        }
        $group = $data->groupid;
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          //brand
          $subgrosssales = 0;
          $subamountdiscount = 0;
          $subreturns = 0;
          $subnetsales = 0;
          $subcostsales = 0;
          $subgrossprofit = 0;
          $submarginvscost = 0;
          $submarginvssales = 0;
          $subunitsold = 0;
          $subaverageprice = 0;
          $subaveragecost = 0;
          //part
          $gsubgrosssales = 0;
          $gsubamountdiscount = 0;
          $gsubreturns = 0;
          $gsubnetsales = 0;
          $gsubcostsales = 0;
          $gsubgrossprofit = 0;
          $gsubmarginvscost = 0;
          $gsubmarginvssales = 0;
          $gsubunitsold = 0;
          $gsubaverageprice = 0;
          $gsubaveragecost = 0;
          $brand = strtoupper($data->brand);
        }
      }


      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '75', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($data->itemname, '175', '', '', $border, '', 'L', $font, '9', '', '', '');
      $str .= $this->reporter->col($gsales, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($disc, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($sreturn, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($netsales, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col($cogs, '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($grossprofit, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvscost, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($marginvssales, 2), '75', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averageprice, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');
      $str .= $this->reporter->col(number_format($averagecost, 2), '50', '', '', $border, '', 'R', $font, '9', '', '', '');

      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $cogs / $data->qty;
      } else {
        $averagecost = 0;
      }

      $totalgrosssales = $totalgrosssales + $data->gsales;
      $totalamountdiscount = $totalamountdiscount + $data->disc;
      $totalreturns = $totalreturns + $data->sreturn;
      $totalnetsales = $totalnetsales + $netsales;
      $totalcostsales = $totalcostsales + $data->cogs;
      $totalgrossprofit = $totalgrossprofit + $grossprofit;
      $totalmarginvscost = $totalmarginvscost + $marginvscost;
      $totalmarginvssales = $totalmarginvssales + $marginvssales;
      $totalunitsold = $totalunitsold + $data->qty;
      $totalaverageprice = $totalaverageprice + $averageprice;
      $totalaveragecost = $totalaveragecost + $averagecost;


      $subgrosssales = $subgrosssales + $data->gsales;
      $subamountdiscount = $subamountdiscount + $data->disc;
      $subreturns = $subreturns + $data->sreturn;
      $subnetsales = $subnetsales + $netsales;
      $subcostsales = $subcostsales + $data->cogs;
      $subgrossprofit = $subgrossprofit + $grossprofit;
      $submarginvscost = $submarginvscost + $marginvscost;
      $submarginvssales = $submarginvssales + $marginvssales;
      $subunitsold = $subunitsold + $data->qty;
      $subaverageprice = $subaverageprice + $averageprice;
      $subaveragecost = $subaveragecost + $averagecost;
      //part
      $gsubgrosssales = $subgrosssales + $gsales;
      $gsubamountdiscount = $subamountdiscount + $disc;
      $gsubreturns = $subreturns + $sreturn;
      $gsubnetsales = $subnetsales + $netsales;
      $gsubcostsales = $subcostsales + $cogs;
      $gsubgrossprofit = $subgrossprofit + $grossprofit;
      $gsubmarginvscost = $submarginvscost + $marginvscost;
      $gsubmarginvssales = $submarginvssales + $marginvssales;
      $gsubunitsold = $subunitsold + $data->qty;
      $gsubaverageprice = $subaverageprice + $averageprice;
      $gsubaveragecost = $subaveragecost + $averagecost;


      $brand = strtoupper($data->brand);
      $group = $data->groupid;
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col($brand . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($subgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($subgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($submarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col($group . ' ' . 'SUB TOTAL:', '175', null, false, $border, '', 'R', $font, '9', 'Bi', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrosssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubamountdiscount, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubreturns, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubnetsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubcostsales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubgrossprofit, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvscost, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col(number_format($gsubmarginvssales, 2), '75', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'R', $font, '9', '', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '175', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrosssales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamountdiscount, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalreturns, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetsales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcostsales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgrossprofit, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvscost, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmarginvssales, 2), '75', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->col('', '50', '', '', $border, 'TB', 'R', $font, '9', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  //END MAJESTY LAYOUT

}//end class