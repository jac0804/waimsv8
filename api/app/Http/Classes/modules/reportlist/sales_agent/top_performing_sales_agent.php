<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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

class top_performing_sales_agent
{
  public $modulename = 'Top Performing Sales Agent';
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

    $systype = $this->companysetup->getsystemtype($config['params']);
    if ($systype == 'EAPPLICATION' || $systype == 'AMS') {
      $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname'];
    } else {
      $fields = ['radioprint', 'start', 'end', 'radioreporttrnxtype', 'dclientname', 'dagentname', 'ditemname', 'divsion', 'part', 'deptcode', 'brand', 'model', 'class', 'categoryname', 'prefix'];
    }

    $col1 = $this->fieldClass->create($fields);
    if ($systype != 'EAPPLICATION' && $systype != 'AMS') {
      data_set($col1, 'dclientname.lookupclass', 'rcustomer');
      data_set($col1, 'dclientname.label', 'Customer');
      data_set($col1, 'divsion.label', 'Division');
      data_set($col1, 'part.label', 'Principal');
      data_set($col1, 'deptcode.label', 'Department');

      data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
      data_set($col1, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
      data_set($col1, 'categoryname.class', 'cscscategocsryname sbccsreadonly');

      data_set($col1, 'prefix.readonly', false);
    }

    //divsion, part, model, brand, class
    unset($col1['divsion']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['model']['labeldata']);
    unset($col1['brand']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['part']);
    unset($col1['labeldata']['model']);
    unset($col1['labeldata']['brand']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'part.name', 'partname');
    data_set($col1, 'model.name', 'modelname');
    data_set($col1, 'brand.name', 'brandname');
    data_set($col1, 'class.name', 'classic');

    switch ($systype) {
      case 'EAPPLICATION':
        $fields = ['plantype', 'print'];
        break;
      case 'AMS':
        $fields = ['print'];
        break;
      default:
        $fields = ['radiosalescustomerperitem', 'radiotypeofreportsales', 'radiopaymenttype', 'print'];
        break;
    }

    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'EAPPLICATION':
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,left(now(),10) as end,
        '' as client,'' as clientname,
        '' as agent,'' as agentname,'' as agentid,
        '' as plangrpid,'' as planid,'' as plantype
        ");
        break;
      case 'AMS':
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,left(now(),10) as end,
        '' as client,'' as clientname,
        '' as agent,'' as agentname,'' as agentid
        ");
        break;

      default:
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        'all' as reporttrnxtype,
        '' as client,
        '' as clientname,
        '' as dclientname,
        '' as agent,
        '' as agentname,
        0 as agentid,
        '' dagentname,
        '' as barcode,
        0 as itemid,
        '' as itemname,
        '' as ditemname,
        '' as modelname,
        0 as modelid,
        '' as model,
        0 as groupid,
        '' as stockgrp,
        '' as divsion,
        0 as partid,
        '' as partname,
        '' as part,
        0 as deptid,
        '' as deptcode,
        '' as deptname,
        0 as brandid,
        '' as brandname,
        '' as brand,
        0 as classid,
        '' as class,
        '' as classic,
        '' as category,
        '' as categoryname,
        '' as prefix,
        'sales' as options,
        'report' as typeofreport,
        'all' as paymenttype
        ");
        break;
    }
  }

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

    if ($this->companysetup->getsystemtype($config['params']) == 'EAPPLICATION' || $this->companysetup->getsystemtype($config['params']) == 'AMS') {
      return $this->AMS_EAP_Layout($config);
    } else {
      return $this->reportDefaultLayout($config);  
    }
  }

  public function reportDefault($config)
  {
    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'EAPPLICATION':
        return $this->EAP_QRY($config);
        break;
      case 'AMS':
        return $this->AMS_QRY($config);
        break;
      default:
        return $this->default_QRY($config);
        break;
    }
    if ($this->companysetup->getsystemtype($config['params']) == 'EAPPLICATION' || $this->companysetup->getsystemtype($config['params']) == 'AMS') {
    } else {
    }
  }

  public function default_QRY($config)
  {
    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $trnxtype     = $config['params']['dataparams']['reporttrnxtype'];

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $agentid     = $config['params']['dataparams']['agentid'];
    $agentname = $config['params']['dataparams']['agentname'];
    $itemid     = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $stockgrp = $config['params']['dataparams']['stockgrp'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname = $config['params']['dataparams']['partname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname = $config['params']['dataparams']['deptname'];
    $brandid     = $config['params']['dataparams']['brandid'];
    $brandname = $config['params']['dataparams']['brandname'];
    $modelid     = $config['params']['dataparams']['modelid'];
    $modelname = $config['params']['dataparams']['modelname'];
    $classid     = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $prefix     = $config['params']['dataparams']['prefix'];
    $options     = $config['params']['dataparams']['options'];
    $typeofreport     = $config['params']['dataparams']['typeofreport'];
    $paymenttype = $config['params']['dataparams']['paymenttype'];

    
    $center     = $config['params']['center'];

    $filter = '';
    if ($center != '') {
      $filter .= " and num.center='$center'";
    }
    if ($clientname != '') {
      $filter .= " and cust.client='$client'";
    }
    if ($agentname != '') {
      $filter .= " and ag.clientid=$agentid";
    }
    if ($itemname != '') {
      $filter .= " and item.itemid=$itemid";
    }
    if ($stockgrp != '') {
      $filter .= " and item.groupid=$groupid";
    }
    if ($partname != '') {
      $filter .= " and item.part=$partid";
    }
    if ($deptname != '') {
      $filter .= " and head.deptid=$deptid";
    }
    if ($brandname != '') {
      $filter .= " and item.brand=$brandid";
    }
    if ($modelname != '') {
      $filter .= " and item.model=$modelid";
    }
    if ($classname != '') {
      $filter .= " and item.class=$classid";
    }
    if ($prefix != "") {
      $filter .= " and num.bref='$prefix'";
    }

    switch ($options) {
      case 'sales':
        $viewfield = 'stock.ext';
        break;

      case 'qty':
        $viewfield = 'stock.iss';
        break;
    }

    switch (strtolower($paymenttype)) {
      case 'Cash':
        $filter .= " and head.salestype='CASH' ";
        break;

      case 'Charge':
        $filter .= " and head.salestype='CHARGE' ";
        break;

      case 'Check':
        $filter .= " and head.salestype='CHECK' ";
        break;

      case 'Deposit':
        $filter .= " and head.salestype='DEPOSIT' ";
        break;

      default:
        $filter .= "";
        break;
    }

    // TRNX TYPE
    switch (strtolower($trnxtype)) {
      case 'regular':
        $filter .= " and head.uv_transtype='Regular' ";
        break;

      case 'pwd':
        $filter .= " and head.uv_transtype='P.W.D.' ";
        break;

      case 'senior':
        $filter .= " and head.uv_transtype='Senior' ";
        break;

      case 'diplomat':
        $filter .= " and head.uv_transtype='Diplomat' ";
        break;

      case 'all':
        $filter .= "";
        break;
    }

    $qryset = "set @row_num = 0";
    $this->coreFunctions->execqry($qryset);

    switch ($typeofreport) {
      case 'report':
        $query = "select @row_num := @row_num + 1 as rank,code,category,sales from (
        select code,category,sum(sales) as sales from (
        select ag.client as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) as `sales`
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.client = head.client
        left join client as ag on ag.client = head.agent
        left join client as dept on dept.clientid=head.deptid
        where head.doc in ('SJ','MJ')
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ifnull(ag.client,'') as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) as `sales`
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid
        left join client as dept on dept.clientid=head.deptid
        where head.doc in ('SJ','MJ')
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        ) as tbl
        group by code,category 
        order by sales desc)
        as tbl2 where sales <> 0";
        break;
      case 'lessreturn':
        $query = "select @row_num := @row_num + 1 as rank,code,category,sales from (
        select code,category,sum(sales) as sales from (
        select ag.client as code,ifnull(ag.clientname,'') as category,sum(" . $viewfield . ") as `sales`
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.client = head.client
        left join client as ag on ag.client = head.agent
        left join client as dept on dept.clientid=head.deptid
        where head.doc in ('SJ','MJ') and num.bref in ('SJ','MJ')
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ifnull(ag.client,'') as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) as `sales`
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid
        left join client as dept on dept.clientid=head.deptid
        where head.doc in ('SJ','MJ')
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ag.client as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) * -1  as `sales`
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.client = head.client
        left join client as ag on ag.client = head.agent
        left join client as dept on dept.clientid=head.deptid
        where head.doc = 'CM'
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ifnull(ag.client,'') as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) * -1 as `sales`
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid
        left join client as dept on dept.clientid=head.deptid
        where head.doc = 'CM' and num.bref = 'CM'
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        ) as tbl
        group by code,category 
        order by sales desc)
        as tbl2 where sales <> 0";
        break;

      case 'return':
        $query = "select @row_num := @row_num + 1 as rank,code,category,sales from (
        select code,category,sum(sales) as sales from (
        select ag.client as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) * -1  as `sales`
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.client = head.client
        left join client as ag on ag.client = head.agent
        left join client as dept on dept.clientid=head.deptid
        where head.doc = 'CM'
        and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ifnull(ag.client,'') as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) * -1 as `sales`
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join cntnum as num on num.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cust on cust.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid
        left join client as dept on dept.clientid=head.deptid
        where head.doc = 'CM'
        and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        ) as tbl
        group by code,category 
        order by sales desc)
        as tbl2 where sales <> 0";
        break;
    }


    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }


  public function EAP_QRY($config)
  {
    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $agentid     = $config['params']['dataparams']['agentid'];
    // $agent     = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];
    $plangrpid     = $config['params']['dataparams']['plangrpid'];
    $planid     = $config['params']['dataparams']['planid'];
    $plantype = $config['params']['dataparams']['plantype'];

    $filter = '';
    if ($clientname != '') {
      $filter .= " and cust.client= '" . $client . "' ";
    }

    if ($agentname != '') {
      $filter .= " and ag.clientid = '" . $agentid . "' ";
    }

    if ($plantype != '') {
      $filter .= " and app.plangrpid = '" . $plangrpid . "' and app.planid = '" . $planid . "' ";
    }


    $qryset = "set @row_num = 0";
    $this->coreFunctions->execqry($qryset);

    $query = "
    select @row_num := @row_num + 1 as rank,code,category,sales
    from (
      select code,category,sum(sales) as sales
      from (
        select ag.client as code,ifnull(ag.clientname,'') as category
        ,ifnull(sum(detail.cr-detail.db),0) as sales
        from lahead as head
        left join ladetail as detail on detail.trno = head.trno
        left join client as cust on cust.client = head.client
        left join client as ag on ag.client = head.agent
        left join coa as c on c.acnoid=detail.acnoid
        left join heahead as app on app.trno = head.aftrno
        left join plangrp as pg on pg.line = app.plangrpid
        left join plantype as pt on pt.line = app.planid
        where head.doc = 'CP'
        and left(c.alias,2)='SA'
        and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ag.client as code,ifnull(ag.clientname,'') as category
        ,ifnull(sum(detail.cr-detail.db),0) as sales
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join client as cust on cust.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid
        left join coa as c on c.acnoid=detail.acnoid
        left join heahead as app on app.trno = head.aftrno
        left join plangrp as pg on pg.line = app.plangrpid
        left join plantype as pt on pt.line = app.planid
        where head.doc = 'CP'
        and left(c.alias,2)='SA'
        and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
      ) as tbl
      group by code,category
      #order by sales desc
    )
    as tbl2
    where sales <> 0
    ;";

    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }


  public function AMS_QRY($config)
  {
    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $agentid     = $config['params']['dataparams']['agentid'];
    // $agent     = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];

    $filter = '';

    if ($clientname != '') {
      $filter .= " and cust.client= '" . $client . "' ";
    }

    if ($agentname != '') {
      $filter .= " and ag.clientid = '" . $agentid . "' ";
    }

    $qryset = "set @row_num = 0";
    $this->coreFunctions->execqry($qryset);

    $query = "
    select @row_num := @row_num + 1 as rank,code,category,sales
    from (
      select code,category,sum(sales) as sales
      from (
        select ag.client as code,ifnull(ag.clientname,'') as category
        ,ifnull(sum(detail.cr-detail.db),0) as sales
        from lahead as head
        left join ladetail as detail on detail.trno = head.trno
        left join client as cust on cust.client = head.client
        left join client as ag on ag.client = head.agent
        left join coa as c on c.acnoid=detail.acnoid
        
        where head.doc in ('CP','SJ')
        and left(c.alias,2)='SA'
        and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
        UNION ALL
        select ag.client as code,ifnull(ag.clientname,'') as category
        ,ifnull(sum(detail.cr-detail.db),0) as sales
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join client as cust on cust.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid
        left join coa as c on c.acnoid=detail.acnoid
        
        where head.doc in ('CP','SJ')
        and left(c.alias,2)='SA'
        and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by ag.client,ag.clientname
      ) as tbl
      group by code,category
      #order by sales desc
    )
    as tbl2
    where sales <> 0
    ;";

    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));


    // $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    // $agentid     = $config['params']['dataparams']['agentid'];
    // $agent     = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];


    $font = $this->companysetup->getrptfont($config['params']);
    $str = '';
    // $count = 38;
    // $page = 40;

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->startrow();
    $str .= '<br>';
    $str .= $this->reporter->col('TOP PERFORMING AGENT', null, null, false, '1px solid', '', 'C', $font, '15', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From:' . $start . ' to ' . $end, '180', null, false, '1px solid', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    if ($clientname == "") {
      $str .= $this->reporter->col('CUSTOMER: ALL', '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER: ' . $clientname, '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($agentname == "") {
      $str .= $this->reporter->col('AGENT: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('AGENT: ' . $agentname, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }




  private function AMS_EAP_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    // $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('R A N K I N G', '150', null, false, '1px solid', 'B', 'C', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('AGENT CODE', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', 'B', 'C', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('AGENT NAME', '300', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('V A L U E', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->endrow();

    return $str;
  }


  public function reportDefaultLayout($config)
  {
    $data = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $trnxtype     = $config['params']['dataparams']['reporttrnxtype'];

    // $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    // $agentid     = $config['params']['dataparams']['agentid'];
    // $agent     = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];

    // $itemid     = $config['params']['dataparams']['itemid'];
    // $barcode     = $config['params']['dataparams']['barcode'];
    $itemname = $config['params']['dataparams']['itemname'];

    // $groupid     = $config['params']['dataparams']['groupid'];
    $stockgrp = $config['params']['dataparams']['stockgrp'];

    // $partid     = $config['params']['dataparams']['partid'];
    $partname = $config['params']['dataparams']['partname'];

    // $deptid     = $config['params']['dataparams']['deptid'];
    // $deptcode     = $config['params']['dataparams']['deptcode'];
    $deptname = $config['params']['dataparams']['deptname'];

    // $brandid     = $config['params']['dataparams']['brandid'];
    $brandname = $config['params']['dataparams']['brandname'];

    // $modelid     = $config['params']['dataparams']['modelid'];
    $modelname = $config['params']['dataparams']['modelname'];

    // $class     = $config['params']['dataparams']['class'];
    $classname = $config['params']['dataparams']['classic'];

    $prefix     = $config['params']['dataparams']['prefix'];

    // $options     = $config['params']['dataparams']['options'];
    $typeofreport     = $config['params']['dataparams']['typeofreport'];
    $paymenttype = $config['params']['dataparams']['paymenttype'];

    $font = $this->companysetup->getrptfont($config['params']);
    $str = '';
    // $count = 38;
    // $page = 40;

    $str .= $this->reporter->beginreport('800');

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->startrow();
    $str .= '<br>';
    $str .= $this->reporter->col('TOP PERFORMING AGENT', null, null, false, '1px solid', '', 'C', $font, '18', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('From:' . $start . ' to ' . $end, '180', null, false, '1px solid', '', '', $font, '10', '', '', '');

    switch ($typeofreport) {
      case 'report':
        $str .= $this->reporter->col('TYPE OF REPORT: SALES', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
        break;
      case 'lessreturn':
        $str .= $this->reporter->col('TYPE OF REPORT: SALES NET OF RETURN', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
        break;
      case 'return':
        $str .= $this->reporter->col('TYPE OF REPORT: RETURNS', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
        break;
    }

    if ($partname == "") {
      $str .= $this->reporter->col('PRINCIPAL: ALL', '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('PRINCIPAL: ' . $partname, '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($stockgrp == "") {
      $str .= $this->reporter->col('DIVISION: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('DIVISION: ' . $stockgrp, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($classname == "") {
      $str .= $this->reporter->col('CLASSIFICATION: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('CLASSIFICATION: ' . $classname, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($modelname == "") {
      $str .= $this->reporter->col('GENERIC: ALL', '180', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('GENERIC: ' . $modelname, '180', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($brandname == "") {
      $str .= $this->reporter->col('BRAND: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('BRAND: ' . $brandname, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($deptname == "") {
      $str .= $this->reporter->col('DEPT.: ALL', '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPT.: ' . $deptname, '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }


    $str .= $this->reporter->col('SALES TYPE: ' . $paymenttype, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');



    $str .= $this->reporter->col('TRNX TYPE: ' . $trnxtype, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();


    if ($prefix == "") {
      $str .= $this->reporter->col('SALES PREF.: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('SALES PREF.: ' . $prefix, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($clientname == "") {
      $str .= $this->reporter->col('CUSTOMER: ALL', '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER: ' . $clientname, '140', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }

    if ($agentname == "") {
      $str .= $this->reporter->col('AGENT: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('AGENT: ' . $agentname, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }
    if ($itemname == "") {
      $str .= $this->reporter->col('ITEM: ALL', '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('ITEM: ' . $itemname, '160', null, false, '1px solid', '', '', $font, '10', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('R A N K I N G', '150', null, false, '1px solid', 'B', 'C', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('AGENT CODE', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', 'B', 'C', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col('AGENT NAME', '300', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '8px');

    switch ($config['params']['dataparams']['options']) {
      case 'sales':
        $viewfield = 'V A L U E';
        break;

      case 'qty':
        $viewfield = 'Q U A N T I T Y';
        break;
    }

    $str .= $this->reporter->col($viewfield, '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->endrow();

    $grandtotal = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['rank'], '150', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
      $str .= $this->reporter->col($data[$i]['code'], '150', null, false, '1px solid', '', 'L', $font, '10', '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
      $str .= $this->reporter->col($data[$i]['category'], '300', null, false, '1px solid', '', 'L', $font, '10', '', '', '8px');
      $str .= $this->reporter->col(number_format($data[$i]['sales'], 2), '100', null, false, '1px solid', '', 'R', $font, '10', '', '', '8px');
      $str .= $this->reporter->endrow();
      $grandtotal += $data[$i]['sales'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'L', $font, '10', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
    $str .= $this->reporter->col('GRAND TOTAL:', '300', null, false, '1px dotted', 'T', 'R', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, '1px dotted', 'T', 'R', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }


  public function AMS_EAP_Layout($config)
  {
    $data = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    // $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    // $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    // $font_size = '11';
    $fontsize12 = 10;
    $border = '1px dotted';
    // $client     = $config['params']['dataparams']['client'];
    // $clientname = $config['params']['dataparams']['clientname'];
    // $agentid     = $config['params']['dataparams']['agentid'];
    // $agent     = $config['params']['dataparams']['agent'];
    // $agentname = $config['params']['dataparams']['agentname'];


    $font = $this->companysetup->getrptfont($config['params']);
    $str = '';
    $count = 38;
    $page = 40;

    $str .= $this->reporter->beginreport('800');
    $str .= $this->displayHeader($config);
    $str .= $this->AMS_EAP_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $config);

    $grandtotal = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['rank'], '150', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
      $str .= $this->reporter->col($data[$i]['code'], '150', null, false, '1px solid', '', 'L', $font, '10', '', '', '8px');
      $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
      $str .= $this->reporter->col($data[$i]['category'], '300', null, false, '1px solid', '', 'L', $font, '10', '', '', '8px');
      $str .= $this->reporter->col(number_format($data[$i]['sales'], 2), '100', null, false, '1px solid', '', 'R', $font, '10', '', '', '8px');
      $str .= $this->reporter->endrow();
      $grandtotal += $data[$i]['sales'];

      if ($this->reporter->linecounter == $page) {
        $border = '1px dotted';
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->displayHeader($config);
        }
        $str .= $this->AMS_EAP_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'L', $font, '10', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'C', $font, '10', '', '', '8px');
    $str .= $this->reporter->col('GRAND TOTAL:', '300', null, false, '1px dotted', 'T', 'R', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, '1px dotted', 'T', 'R', $font, '10', 'B', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}
