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

class sales_per_item_per_customer
{
  public $modulename = 'Sales Per Item Per Customer';
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
    $fields = ['radioprint', 'start', 'end', 'ditemname', 'categoryname', 'subcatname'];
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
      case 21: //kinggeorge
        array_push($fields, 'brandname', 'stockgrp', 'agentname', 'customer', 'dwhname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'customer.lookupclass', 'oscustomer');
        data_set($col1, 'customer.type', 'lookup');
        data_set($col1, 'customer.action', 'lookupclient');
        data_set($col1, 'customer.readonly', true);
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        array_push($fields, 'brandname');
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'start.label', 'StartDate');
    data_set($col1, 'start.readonly', false);
    data_set($col1, 'end.label', 'EndDate');
    data_set($col1, 'end.readonly', false);
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    $fields = ['radiosalescustomerperitem', 'radioposttype'];

    if ($companyid == 19) { //housegem
      array_push($fields, 'radiodatetype');
    }

    array_push($fields, 'print');

    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'radiosalescustomerperitem.options', array(
      ['label' => 'Amount', 'value' => 'ext', 'color' => 'orange'],
      ['label' => 'Quantity', 'value' => 'iss', 'color' => 'orange'],
      ['label' => 'Both', 'value' => 'both', 'color' => 'orange']
    ));

    data_set($col2, 'radioposttype.options', array(
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal'],
    ));

    return array('col1' => $col1, 'col2' => $col2);
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
      '' as itemname,
      '' as barcode,
      '' as category,
      '' as subcat,
      ''as categoryname,
      '' as subcatname,
      'ext' as options,
      '0' as posttype,
      '0' as reporttype,
      '' as brandname,
      0 as brandid,
      0 as groupid,
      '' as stockgrp,
      0 as agentid,
      '' as agent,
      '' as agentname,
      0 as customerid,
      '' as customer,
      '' as client,
      '' as dagentname,
      'dateid' as transdate,
      0 as whid,
      '' as wh, 
      '' as whname, 
      '' as dwhname,
      '' as project, 
      0 as projectid, 
      '' as projectname,
      0 as deptid,
      '' as ddeptname, 
      '' as dept, 
      '' as deptname,
      '' as industry";

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
      case 21: //kinggeorge
        $result = $this->kinggeorge_layout($config);
        break;
      case 14: //majesty
        $result = $this->MAJESTY_layout($config);
        break;
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {

    $query = $this->DEFAULT_QUERY_SUMMARIZED_V2($config);

    //old query [2025.03.18]
    //   $query = $this->DEFAULT_QUERY_SUMMARIZED($config);

    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY_SUMMARIZED($config)
  {
    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];
    $option      = $config['params']['dataparams']['options'];
    $barcode     = $config['params']['dataparams']['barcode'];
    $posttype  = $config['params']['dataparams']['posttype'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcatname'];
    $brandname = $config['params']['dataparams']['brandname'];
    $stockgrp = $config['params']['dataparams']['stockgrp'];
    $agentname = $config['params']['dataparams']['agentname'];
    $clientname = $config['params']['dataparams']['customer'];
    $wh = $config['params']['dataparams']['wh'];

    $isqty = "stock.iss";
    $filter = "";

    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($clientname != "") {
      $clientid = $config['params']['dataparams']['customerid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($agentname != "") {
      $agentid = $config['params']['dataparams']['agentid'];
      $filter .= " and agent.clientid=" . $agentid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
      $filter .= " and stock.whid=" . $whid;
    }

    $filter1 = "";
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
    }

    $field = '';
    $grpfield = '';
    if ($option == 'both') {
      $field = "sum(qty) as qty, amt as sales";
      $grpfield = ",amt";
    } else {
      $field = "sum($option) as sales";
    }

    $addedfields = "";
    $whc = "";
    $leftjoin_p = "";
    $leftjoin_u = "";
    switch ($companyid) {
      case 21: // kinggeorge
        $addedfields  = ", dateid, docno, wh";
        $whc = ', wh.client as wh';
        $leftjoin_p = " left join client as wh on wh.clientid = head.whid";
        $leftjoin_u = " left join client as wh on wh.client = head.wh";
        break;
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select brand,size,model,barcode, itemname, client, clientname, $field,
        category,subcatname $addedfields

        from ( select item.sizeid as size,item.brand,ifnull(mm.model_name,'') as model, left(head.dateid,10) as dateid,
        'p' as tr, head.trno, head.doc, head.docno, client.client, head.clientname, item.barcode,
        item.itemname, " . $isqty . " as qty, stock.amt, stock.ext,stock.isqty,stock.isamt,
         cat.name as category, subcat.name as subcatname $whc

        from glhead as head left join glstock as stock on stock.trno=head.trno 
        left join client on client.clientid=head.clientid
        left join item on item.itemid=stock.itemid 
        left join model_masterfile as mm on mm.model_id = item.model
        left join cntnum on cntnum.trno=head.trno
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat

        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join client as agent on agent.clientid = head.agentid
        $leftjoin_p
        left join stockgrp_masterfile as grpx on grpx.stockgrp_id = item.groupid

        where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$startdate' and '$enddate' $filter $filter1 and item.isofficesupplies=0) as sa
        where sa.ext <> 0 
        group by brand,size,model,barcode, itemname, client, clientname,category,subcatname $addedfields $grpfield
        order by itemname, clientname";
        break;

      case 1: // unposted
        $query = "select brand,size,model,barcode, itemname, client, clientname, $field,
        category,subcatname $addedfields

        from (select item.sizeid as size,item.brand,ifnull(mm.model_name,'') as model, left(head.dateid,10) as dateid,
        'u' as tr, head.trno, head.doc, head.docno, head.client, head.clientname, item.barcode,
        item.itemname, " . $isqty . " as qty, stock.amt, stock.ext,stock.isqty,stock.isamt,
        cat.name as category, subcat.name as subcatname $whc

        from lahead as head 
        left join lastock as stock on stock.trno=head.trno 
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join cntnum on cntnum.trno=head.trno

        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join client as agent on agent.client = head.agent
        $leftjoin_u
        left join stockgrp_masterfile as grpx on grpx.stockgrp_id = item.groupid

        where head.doc in ('sj','sd','se','sf') and date(head.dateid) between '$startdate' and '$enddate' $filter $filter1 and item.isofficesupplies=0
        ) as sa
        where sa.ext <> 0 
        group by brand,size,model,barcode, itemname, client, clientname,category,subcatname $addedfields $grpfield
        order by itemname, clientname";
        break;

      default:
        $query = "select brand,size,model,barcode, itemname, client, clientname, $field,
        category,subcatname $addedfields

        from (  select item.sizeid as size,item.brand,ifnull(mm.model_name,'') as model, left(head.dateid,10) as dateid,
        'u' as tr, head.trno, head.doc, head.docno, head.client, head.clientname, item.barcode,
        item.itemname, " . $isqty . " as qty, stock.amt, stock.ext,stock.isqty,stock.isamt,
        cat.name as category, subcat.name as subcatname $whc
       
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno 
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join cntnum on cntnum.trno=head.trno

        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join client as agent on agent.client = head.agent
        $leftjoin_u
        left join stockgrp_masterfile as grpx on grpx.stockgrp_id = item.groupid
       
        where head.doc in ('sj','mj','sd','se','sf') and head.dateid between '$startdate' and '$enddate' $filter $filter1 and item.isofficesupplies=0
      
        union all
        
        select item.sizeid as size,item.brand,ifnull(mm.model_name,'') as model, left(head.dateid,10) as dateid,
        'p' as tr, head.trno, head.doc, head.docno, client.client, head.clientname, item.barcode,
        item.itemname, " . $isqty . " as qty, stock.amt, stock.ext,stock.isqty,stock.isamt,
        cat.name as category, subcat.name as subcatname $whc
        
        from glhead as head left join glstock as stock on stock.trno=head.trno left join client on client.clientid=head.clientid
        left join item on item.itemid=stock.itemid 
        left join model_masterfile as mm on mm.model_id = item.model
        left join cntnum on cntnum.trno=head.trno

        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join client as agent on agent.clientid = head.agentid
        $leftjoin_p
        left join stockgrp_masterfile as grpx on grpx.stockgrp_id = item.groupid
      
        where head.doc in ('sj','mj','sd','se','sf') and  date(head.dateid) between '$startdate' and '$enddate' $filter $filter1 and item.isofficesupplies=0) as sa
        where sa.ext <> 0 
        group by brand,size,model,barcode, itemname, client, clientname,category,subcatname $addedfields $grpfield
        order by itemname, clientname";
        break;
    }
    return $query;
  }


  public function DEFAULT_QUERY_SUMMARIZED_V2($config)
  {
    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];
    $option      = $config['params']['dataparams']['options'];
    $barcode     = $config['params']['dataparams']['barcode'];
    $posttype  = $config['params']['dataparams']['posttype'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcatname'];
    $brandname = $config['params']['dataparams']['brandname'];
    $stockgrp = $config['params']['dataparams']['stockgrp'];
    $agentname = $config['params']['dataparams']['agentname'];
    $clientname = $config['params']['dataparams']['customer'];
    $wh = $config['params']['dataparams']['wh'];

    $filter = "";

    $filter_p = "";
    $filter_u = "";

    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($clientname != "") {
      $clientid = $config['params']['dataparams']['customerid'];
      $filter_u .= " and client.clientid=" . $clientid;
      $filter_p .= " and head.clientid=" . $clientid;
    }
    if ($agentname != "") {
      $agentid = $config['params']['dataparams']['agentid'];
      $filter_u .= " and ag.clientid=" . $agentid;
      $filter_p .= " and head.agentid=" . $agentid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
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
      $filter .= " and stock.whid=" . $whid;
    }


    $filter1 = "";
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
    }

    $field = '';
    $grpfield = '';
    if ($option == 'both') {
      $field = "sum(stock.iss) as qty, stock.amt as sales";
      $grpfield = ",stock.amt";
    } else {
      $field = "sum($option) as sales";
    }

    switch ($posttype) {
      case 0: // posted

        $query = "select left(head.dateid,10) as dateid,'p' as tr, head.docno, $field,
                         head.clientname,stock.itemid,item.itemname
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$startdate' and '$enddate'
                        $filter $filter_p $filter1
                  group by head.dateid,head.docno,head.clientname,stock.itemid,item.itemname $grpfield
                  order by itemname,clientname";
        break;

      case 1: // unposted

        $query = "select left(head.dateid,10) as dateid,'u' as tr, head.docno,$field,
                         head.clientname,stock.itemid,item.itemname
                  from lahead as head
                  left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join client as ag on ag.client=head.agent
                  left join item on item.itemid=stock.itemid
                  where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$startdate' and '$enddate'
                        $filter $filter_u $filter1
                  group by head.dateid,head.docno,head.clientname,stock.itemid,item.itemname $grpfield
                  order by itemname,clientname";
        break;

      default:
        $query = "select left(head.dateid,10) as dateid,'p' as tr, head.docno, $field,
                         head.clientname,stock.itemid,item.itemname
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$startdate' and '$enddate'
                        $filter $filter_p $filter1
                  group by head.dateid,head.docno,head.clientname,stock.itemid,item.itemname $grpfield
                  union all
                  select left(head.dateid,10) as dateid,'u' as tr, head.docno,$field,
                         head.clientname,stock.itemid,item.itemname
                  from lahead as head
                  left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join client as ag on ag.client=head.agent
                  left join item on item.itemid=stock.itemid
                  where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$startdate' and '$enddate'
                        $filter $filter_u $filter1
                  group by head.dateid,head.docno,head.clientname,stock.itemid,item.itemname $grpfield
                  order by itemname,clientname";
        break;
    }
    return $query;
  }

  private function MAJESTY_displayHeader($config)
  {
    $border = '1px solid';
    $font_size = '10';
    $font = $this->companysetup->getrptfont($config['params']);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $option      = $config['params']['dataparams']['options'];
    $barcode     = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

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

    $str .= $this->reporter->col('SALES PER ITEM PER CUSTOMER', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Item : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Item :' . $barcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($option == 'ext') {
      $str .= $this->reporter->col('Option : AMOUNT', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Industry : ' . $indus, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', '150', null, '', $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($option == 'both') {
      $str .= $this->reporter->col('ITEM CODE', '200', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('ITEM NAME', '225', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '125', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '200', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('ITEM NAME', '300', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('AMOUNT/QUANTITY', '200', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function MAJESTY_layout($config)
  {
    $border = '1px solid';
    $font_size = '10';
    $font = $this->companysetup->getrptfont($config['params']);
    $result = $this->reportDefault($config);
    $option      = $config['params']['dataparams']['options'];
    $this->reporter->linecounter = 0;
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);

    $ordtotal = 0;
    $salestotal = 0;
    $qtytotal = 0;

    $item = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($option == 'both') {
        if ($item != $data->itemname) {
          // Item Description | Customer Name | Amount | Quantity | Total
          $str .= $this->reporter->col($data->barcode, '200', null, false, '1px dotted ', 'T', 'C', $font, $font_size, 'B', 'B', '');
          $str .= $this->reporter->col($data->itemname, '225', null, false, '1px dotted ', 'T', 'L', $font, $font_size, 'B', 'B', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
        $str .= $this->reporter->col('', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
        $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales, 2), '125', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->qty, 2), '125', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales * $data->qty, 2), '125', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();

        $item = $data->itemname;
        $ordtotal += $data->sales * $data->qty;
        $salestotal += $data->sales;
        $qtytotal += $data->qty;
      } else {
        if ($item != $data->itemname) {
          $str .= $this->reporter->col($data->barcode, '200', null, false, '1px dotted ', 'T', 'L', $font, $font_size, 'B', 'b', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, '1px dotted ', 'T', 'L', $font, $font_size, 'B', 'b', '');
          $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'L', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'L', 'B', '10', 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales, 2), '200', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();

        $item = $data->itemname;
        $ordtotal += $data->sales;
      }
    }

    if ($option == 'both') {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('', '225', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('GRANDTOTAL:', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('', '125', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('', '125', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($ordtotal, 2), '125', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('GRANDTOTAL: ', '300', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($ordtotal, 2), '200', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font_size = '10';
    $font = $this->companysetup->getrptfont($config['params']);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $option      = $config['params']['dataparams']['options'];
    $barcode     = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

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

    $str .= $this->reporter->col('SALES PER ITEM PER CUSTOMER', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Item : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Item :' . $barcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($option == 'ext') {
      $str .= $this->reporter->col('Option : AMOUNT', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Industry : ' . $indus, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', '150', null, '', $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    if ($companyid == 23 || $companyid == 41) { //labsol cebu, technolab
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Brand: ' . ($config['params']['dataparams']['brandname'] != '' ? $config['params']['dataparams']['brandname'] : 'ALL'), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    switch ($option) {
      case 'both':
        $str .= $this->reporter->col('ITEM NAME', '225', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('TOTAL', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');

        break;
      default:
        switch ($option) {
          case 'ext':
            $title = 'AMOUNT';
            break;
          case 'iss':
            $title = 'QUANTITY';
            break;
        }

        $str .= $this->reporter->col('ITEM NAME', '300', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col($title, '200', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');

        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font_size = '10';
    $font = $this->companysetup->getrptfont($config['params']);
    $result = $this->reportDefault($config);
    $option      = $config['params']['dataparams']['options'];

    $count = 42;
    $page = 41;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);

    $ordtotal = 0;
    $salestotal = 0;
    $qtytotal = 0;

    $item = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($option == 'both') {
        if ($item == $data->itemname) {
        } else {
          // Item Description | Customer Name | Amount | Quantity | Total
          $str .= $this->reporter->col($data->itemname, '225', null, false, '1px dotted ', 'T', 'L', $font, $font_size, 'B', 'b', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
        $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->qty, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales * $data->qty, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();

        $item = $data->itemname;
        $ordtotal += $data->sales * $data->qty;
        $salestotal += $data->sales;
        $qtytotal += $data->qty;


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $str .= $this->reporter->begintable($layoutsize);
          $page = $page + $count;
        }
      } else {
        if ($item == $data->itemname) {
        } else {

          $str .= $this->reporter->col($data->itemname, '300', null, false, '1px dotted ', 'T', 'L', $font, $font_size, 'B', 'b', '');
          $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'L', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'L', 'B', '10', 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales, 2), '200', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();

        $item = $data->itemname;
        $ordtotal += $data->sales;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $str .= $this->reporter->begintable($layoutsize);
          $page = $page + $count;
        }
      }
    }

    if ($option == 'both') {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '225', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('GRANDTOTAL:', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('', '125', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('', '125', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($ordtotal, 2), '125', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col('GRANDTOTAL: ', '300', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($ordtotal, 2), '200', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function kinggeorge_header($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $option      = $config['params']['dataparams']['options'];
    $barcode     = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $wh = $config['params']['dataparams']['wh'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $indus   = $config['params']['dataparams']['industry'];
      if ($indus == "") {
        $indus = 'ALL';
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

    $str .= $this->reporter->col('SALES PER ITEM PER CUSTOMER', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Item : ALL', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Item :' . $barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($option == 'ext') {
      $str .= $this->reporter->col('Option : AMOUNT', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($wh == '') {
      $str .= $this->reporter->col('Warehouse : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Warehouse : ' . strtoupper($wh), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM NAME', '225', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('DATE', '125', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '125', null, false, $border, 'B', 'L', $font, '12', 'B', '', '', '');

    switch ($option) {
      case 'iss':
        $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        break;
      case 'ext':
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        break;

      default:
        $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('TOTAL', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function kinggeorge_layout($config)
  {
    $border = '1px solid';
    $font_size = '10';
    $font = $this->companysetup->getrptfont($config['params']);
    $result = $this->reportDefault($config);
    $option      = $config['params']['dataparams']['options'];
    $option      = $config['params']['dataparams']['options'];

    $count = 42;
    $page = 41;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_header($config);
    $str .= $this->reporter->begintable($layoutsize);

    $ordtotal = 0;
    $salestotal = 0;
    $tototal = 0;

    $item = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($item == $data->itemname) {
      } else {
        // Item Description | Customer Name | Amount | Quantity | Total
        $str .= $this->reporter->col($data->itemname, '225', null, false, '1px dotted ', 'T', 'L', $font, $font_size, 'B', 'b', '');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        if ($option == 'both') {
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'T', 'C', 'B', '10', 'B', '', '');
        }
      }

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '225', null, false, $border, '', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      if ($option == 'both') {
        $str .= $this->reporter->col(number_format($data->qty, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data->sales * $data->qty, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      } else {
        $str .= $this->reporter->col(number_format($data->sales, 2), '125', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      }

      $str .= $this->reporter->endrow();

      $item = $data->itemname;
      if ($option == 'both') {
        $ordtotal += $data->qty;
        $tototal += $data->sales * $data->qty;
      }
      $salestotal += $data->sales;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->kinggeorge_header($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '225', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
    $str .= $this->reporter->col('GRANDTOTAL:', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
    $str .= $this->reporter->col('', '125', null, false, $border, 'T', 'L', $font, $font_size, '', '', '0px 0px 0px 50px');
    $str .= $this->reporter->col('', '125', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '5px');
    if ($option == 'both') {
      $str .= $this->reporter->col(number_format($ordtotal, 2), '125', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($salestotal, 2), '125', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($tototal, 2), '125', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
    } else {
      $str .= $this->reporter->col(number_format($salestotal, 2), '125', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class