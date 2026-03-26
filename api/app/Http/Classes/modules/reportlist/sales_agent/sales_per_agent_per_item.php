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

class sales_per_agent_per_item
{
  public $modulename = 'Sales Per Agent Per Item';
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
    $fields = ['radioprint', 'start', 'end', 'dagentname', 'part', 'divsion', 'category'];

    switch ($companyid) {
      case 32: //3m
        array_push($fields, 'brand', 'dclientname', 'region', 'province', 'area', 'brgy');
        break;
      case 23: //labsol cebu
        array_push($fields, 'dclientname', 'salestype');
        break;
      default:
        array_push($fields, 'salestype');
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'part.label', 'Part');
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'category.name', 'categoryname');

    switch ($companyid) {
      case 32: //3m
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        break;
      case 23: //labsol cebu
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'salestype.required', false);
        break;
      default:
        data_set($col1, 'salestype.required', false);
        break;
    }

    unset($col1['divsion']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'part.name', 'partname');

    $fields = ['radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    switch ($config['params']['companyid']) {
      case 32: //3m
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summarized', 'value' => 'summary', 'color' => 'teal'],
            ['label' => 'Detailed', 'value' => 'detail', 'color' => 'teal'],
            ['label' => 'By Area', 'value' => 'area', 'color' => 'teal'],
            ['label' => 'By Customer', 'value' => 'client', 'color' => 'teal'],
            ['label' => 'By Item Category', 'value' => 'category', 'color' => 'teal']
          ]
        );
        break;
      case 52: //technolab
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Sales Per Agent Per Brand', 'value' => 'summary', 'color' => 'teal'],
            ['label' => 'Detailed', 'value' => 'detail', 'color' => 'teal']
          ]
        );
        break;
      default:
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summarized', 'value' => 'summary', 'color' => 'teal'],
            ['label' => 'Detailed', 'value' => 'detail', 'color' => 'teal']
          ]
        );
        break;
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $params = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      0 as agentid,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      0 as partid,
      '' as part,
      '' as partname,
      0 as groupid,
      '' as stockgrp,
      '' as divsion,
      '' as category,
      '' as categoryname,
      '' as salestype, 
      '' as brand,
      0 as brandid,
      '' as brandname, 
      '' as client, 
      '' as dclientname, 
      '' as region, 
      '' as province, 
      '' as area, 
      '' as brgy,
      'summary' as reporttype";

    return $this->coreFunctions->opentable($params);
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
    $companyid = $config['params']['companyid'];
    $reptype = $config['params']['dataparams']['reporttype'];

    switch ($reptype) {
      case 'detail':
        switch ($companyid) {
          case 14: // majesty
            $result = $this->majesty_Detail_Layout($config);
            break;

          default:
            $result = $this->report_Detail_Layout($config);
            break;
        }

        break;
      case 'summary':
        if ($companyid == 52) { //technolab
          $result = $this->report_PerBrand_Layout($config);
        } else {
          $result = $this->report_Summary_Layout($config);
        }
        break;
      case 'area':
      case 'client':
      case 'category': //3M
        $result = $this->report_ByArea_Layout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {

    $reptype = $config['params']['dataparams']['reporttype'];
    switch ($reptype) {
      case 'detail':
        $query = $this->Detail_QUERY($config);
        break;
      case 'summary':
        if ($config['params']['companyid'] == 52) { //technolab
          $query = $this->SalesPerBrand_QUERY($config);
        } else {
          $query = $this->Summary_QUERY($config);
        }
        break;
      case 'area':
      case 'client':
      case 'category': //3M
        $query = $this->ByArea_QUERY($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function SalesPerBrand_QUERY($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent     = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];
    $principal     = $config['params']['dataparams']['partid'];
    $partname    = $config['params']['dataparams']['partname'];
    $division     = $config['params']['dataparams']['groupid'];
    $grpname     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];

    $filter = "";
    $salestype     = $config['params']['dataparams']['salestype'];
    if ($salestype != "") {
      $filter .= " and head.salestype = '$salestype'";
    }
    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }
    if ($partname != "") {
      $filter .= " and i.part = $principal ";
    }
    if ($grpname != "") {
      $filter .= " and i.groupid = $division ";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category'";
    }

    $query = "select agname, brand, count(notr) as notr, sum(qty) as qty, sum(amt) as amt
              from (select a.agname,a.docno as notr,sum(a.qty) as qty, sum(a.amt) as amt, brand
                    from (select ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
                                 head.docno,ifnull(stock.isqty,0.00) as qty,
                                 ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, 
                                 stock.uom,brand.brand_desc as brand
                          from lahead as head
                          left join lastock as stock on stock.trno=head.trno
                          left join client as a on a.client=head.agent
                          left join item as i on i.itemid=stock.itemid
                          left join part_masterfile as p on p.part_id=i.part
                          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
                          left join itemcategory as ic on ic.line=i.category
                          left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                          left join client as c on c.client=head.client
                          left join frontend_ebrands as brand on brand.brandid = i.brand
                          where head.doc in ('SJ','MJ') and left(head.docno,3)<>'SJS' 
                                and head.dateid between '$start' and '$end' $filter
                          UNION ALL
                          select ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
                                 head.docno,ifnull(stock.isqty,0.00) as qty,
                                 ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, 
                                 stock.uom,brand.brand_desc as brand
                          from glhead as head
                          left join glstock as stock on stock.trno=head.trno
                          left join client as a on a.clientid=head.agentid
                          left join item as i on i.itemid=stock.itemid
                          left join part_masterfile as p on p.part_id=i.part
                          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
                          left join itemcategory as ic on ic.line=i.category
                          left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                          left join client as c on c.clientid=head.clientid
                          left join frontend_ebrands as brand on brand.brandid = i.brand
                          where head.doc in ('SJ','MJ') and left(head.docno,3)<>'SJS' 
                                and head.dateid between '$start' and '$end' $filter
                          UNION ALL
                          select ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
                                 head.docno,ifnull(stock.isqty,0.00) as qty,
                                 ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, 
                                 stock.uom,brand.brand_desc as brand
                          from lahead as head
                          left join lastock as stock on stock.trno=head.trno
                          left join client as a on a.clientid=stock.agentid
                          left join item as i on i.itemid=stock.itemid
                          left join part_masterfile as p on p.part_id=i.part
                          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
                          left join itemcategory as ic on ic.line=i.category
                          left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                          left join client as c on c.client=head.client
                          left join frontend_ebrands as brand on brand.brandid = i.brand
                          where head.doc in ('SJ','MJ') and left(head.docno,3)='SJS' 
                                and head.dateid between '$start' and '$end' $filter
                          UNION ALL
                          select ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
                                 head.docno,ifnull(stock.isqty,0.00) as qty,
                                 ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, 
                                 stock.uom,brand.brand_desc as brand
                          from glhead as head
                          left join glstock as stock on stock.trno=head.trno
                          left join client as a on a.clientid=stock.agentid
                          left join item as i on i.itemid=stock.itemid
                          left join part_masterfile as p on p.part_id=i.part
                          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
                          left join itemcategory as ic on ic.line=i.category
                          left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                          left join client as c on c.clientid=head.clientid
                          left join frontend_ebrands as brand on brand.brandid = i.brand
                          where head.doc in ('SJ','MJ') and left(head.docno,3)='SJS' 
                                and head.dateid between '$start' and '$end' $filter ) as a
                    group by a.agcode, a.agname, a.docno, a.brand) as xx
              group by agname, brand";
    return $query;
  }

  public function Summary_QUERY($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent     = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];
    $principal     = $config['params']['dataparams']['partid'];
    $partname    = $config['params']['dataparams']['partname'];
    $division     = $config['params']['dataparams']['groupid'];
    $grpname     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];

    $filter = "";
    switch ($config['params']['companyid']) {
      case 32: //3M
        $brandid     = $config['params']['dataparams']['brandid'];
        $brandname = $config['params']['dataparams']['brandname'];
        $client = $config['params']['dataparams']['client'];
        $region = $config['params']['dataparams']['region'];
        $province = $config['params']['dataparams']['province'];
        $area = $config['params']['dataparams']['area'];
        $brgy = $config['params']['dataparams']['brgy'];

        if ($brandname != "") {
          $filter .= " and i.brand = '$brandid'";
        }
        if ($client != "") {
          $filter .= " and c.client = '$client'";
        }
        if ($region != "") {
          $filter .= " and c.region = '$region'";
        }
        if ($province != "") {
          $filter .= " and c.province = '$province'";
        }
        if ($area != "") {
          $filter .= " and c.area = '$area'";
        }
        if ($brgy != "") {
          $filter .= " and c.brgy = '$brgy'";
        }
        break;
      case 23: //labsol cebu
        $client = $config['params']['dataparams']['client'];
        $salestype     = $config['params']['dataparams']['salestype'];

        if ($client != "") {
          $filter .= " and c.client = '$client'";
        }
        if ($salestype != "") {
          $filter .= " and head.salestype = '$salestype'";
        }
        break;

      default:
        $salestype     = $config['params']['dataparams']['salestype'];
        if ($salestype != "") {
          $filter .= " and head.salestype = '$salestype'";
        }
        break;
    }

    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }
    if ($partname != "") {
      $filter .= " and i.part = $principal ";
    }
    if ($grpname != "") {
      $filter .= " and i.groupid = $division ";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category'";
    }

    $query = "
      select agname, count(notr) as notr, sum(qty) as qty, sum(amt) as amt, uom
        from (
        select a.agname,a.docno as notr,sum(a.qty) as qty, sum(a.amt) as amt, uom
        from (
          select
            ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
            head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, stock.uom
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client as a on a.client=head.agent
            left join item as i on i.itemid=stock.itemid
            left join part_masterfile as p on p.part_id=i.part
            left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
            left join itemcategory as ic on ic.line=i.category
            left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
            left join client as c on c.client=head.client
            where head.doc in ('SJ','MJ') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
          UNION ALL
          select
            ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
            head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, stock.uom
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client as a on a.clientid=head.agentid
            left join item as i on i.itemid=stock.itemid
            left join part_masterfile as p on p.part_id=i.part
            left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
            left join itemcategory as ic on ic.line=i.category
            left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            left join client as c on c.clientid=head.clientid
            where head.doc in ('SJ','MJ') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
          UNION ALL
          select
            ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
            head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, stock.uom
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client as a on a.clientid=stock.agentid
            left join item as i on i.itemid=stock.itemid
            left join part_masterfile as p on p.part_id=i.part
            left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
            left join itemcategory as ic on ic.line=i.category
            left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
            left join client as c on c.client=head.client
            where head.doc in ('SJ','MJ') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter
          UNION ALL
          select
            ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
            head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt, stock.uom
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client as a on a.clientid=stock.agentid
            left join item as i on i.itemid=stock.itemid
            left join part_masterfile as p on p.part_id=i.part
            left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
            left join itemcategory as ic on ic.line=i.category
            left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            left join client as c on c.clientid=head.clientid
            where head.doc in ('SJ','MJ') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter  
        ) as a
        group by a.agcode, a.agname, a.docno, a.uom) as xx
        group by agname, uom
    ";

    return $query;
  }

  public function report_PerBrand_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $principal     = $config['params']['dataparams']['partname'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['categoryname'];

    $salestype     = $config['params']['dataparams']['salestype'];
    if ($salestype != "") {
      $salestype = $config['params']['dataparams']['salestype'];
    } else {
      $salestype = "ALL";
    }


    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }

    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Per Agent Per Brand', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Trans Type: ' . $salestype, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Principal: ' . $principal, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Division: ' . $division, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category: ' . $category, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT. NAME', '300', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BRAND', '300', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function report_PerBrand_Layout($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $subborder = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_PerBrand_Header($config);

    $totaltrnx = 0;
    $totalqty = 0;
    $totalamt = 0;

    $subtrnx = 0;
    $subqty = 0;
    $subamt = 0;
    $agname = '';

    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      if ($agname != '' && $agname != $data->agname) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUB TOTAL', '600', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '200', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      if ($agname == '' || $agname != $data->agname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $agname = $data->agname;
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->brand, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->amt != 0 ? number_format($data->amt, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totaltrnx += $data->notr;
      $totalqty += $data->qty;
      $totalamt += $data->amt;
      $subtrnx += $data->notr;
      $subqty += $data->qty;
      $subamt += $data->amt;
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUB TOTAL', '600', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '200', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;

        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->report_PerBrand_Header($config);

        $page = $page + $count;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_Summary_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $principal     = $config['params']['dataparams']['partname'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['categoryname'];

    switch ($config['params']['companyid']) {
      case 32: //3M
        $brand     = $config['params']['dataparams']['brand'];
        $client   = $config['params']['dataparams']['client'];
        $region     = $config['params']['dataparams']['region'];
        $province     = $config['params']['dataparams']['province'];
        $area     = $config['params']['dataparams']['area'];
        $brgy     = $config['params']['dataparams']['brgy'];


        if ($brand != "") {
          $brand = $config['params']['dataparams']['brandname'];
        } else {
          $brand = "ALL";
        }

        if ($client != "") {
          $client = $config['params']['dataparams']['clientname'];
        } else {
          $client = "ALL";
        }

        if ($region != "") {
          $region = $config['params']['dataparams']['region'];
        } else {
          $region = "ALL";
        }

        if ($province != "") {
          $province = $config['params']['dataparams']['province'];
        } else {
          $province = "ALL";
        }

        if ($area != "") {
          $area = $config['params']['dataparams']['area'];
        } else {
          $area = "ALL";
        }

        if ($brgy != "") {
          $brgy = $config['params']['dataparams']['brgy'];
        } else {
          $brgy = "ALL";
        }
        break;
      case 23: //labsol cebu
        $salestype     = $config['params']['dataparams']['salestype'];
        $client   = $config['params']['dataparams']['client'];
        if ($client != "") {
          $client = $config['params']['dataparams']['clientname'];
        } else {
          $client = "ALL";
        }
        if ($salestype != "") {
          $salestype = $config['params']['dataparams']['salestype'];
        } else {
          $salestype = "ALL";
        }
        break;

      default:
        $salestype     = $config['params']['dataparams']['salestype'];
        if ($salestype != "") {
          $salestype = $config['params']['dataparams']['salestype'];
        } else {
          $salestype = "ALL";
        }
        break;
    }


    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }

    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename . ' (Summarized)', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    switch ($config['params']['companyid']) {
      case 32: //3M
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Agent: ' . $agentname, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Customer: ' . $client, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Region: ' . $region, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part: ' . $principal, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Group: ' . $division, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Province: ' . $province, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Category: ' . $category, '292', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Brand: ' . $brand, '294', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Area: ' . $area, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Barangay: ' . $brgy, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;

      default:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Trans Type: ' . $salestype, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Principal: ' . $principal, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Agent: ' . $agentname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Division: ' . $division, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category: ' . $category, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($config['params']['companyid'] == 23) { //labsol cebu
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $client, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        break;
    }
    $str .= '<br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($config['params']['companyid']) {
      case 32: //3m
        $str .= $this->reporter->col('AGENT. NAME', '400', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UOM', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('AGENT. NAME', '500', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function report_Summary_Layout($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_Summary_Header($config);

    $totaltrnx = 0;
    $totalqty = 0;
    $totalamt = 0;


    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      switch ($companyid) {
        case 32: //3m
          $str .= $this->reporter->col($data->agname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          break;
        default:
          $str .= $this->reporter->col($data->agname, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          break;
      }
      $totaltrnx += $data->notr;
      $totalqty += $data->qty;
      $totalamt += $data->amt;
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 32: //3m
        $str .= $this->reporter->col('GRAND TOTAL', '400', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function Detail_QUERY($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $companyid = $config['params']['companyid'];
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent     = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];
    $principal = $config['params']['dataparams']['partid'];
    $partname  = $config['params']['dataparams']['partname'];
    $division  = $config['params']['dataparams']['groupid'];
    $grpname   = $config['params']['dataparams']['stockgrp'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];

    $filter = "";
    $uomfield = "i.uom";
    switch ($config['params']['companyid']) {
      case 32: //3M
        $uomfield = "stock.uom";
        $brandid     = $config['params']['dataparams']['brandid'];
        $brandname = $config['params']['dataparams']['brandname'];
        $client = $config['params']['dataparams']['client'];
        $region = $config['params']['dataparams']['region'];
        $province = $config['params']['dataparams']['province'];
        $area = $config['params']['dataparams']['area'];
        $brgy = $config['params']['dataparams']['brgy'];

        if ($brandname != "") {
          $filter .= " and i.brand = $brandid";
        }
        if ($client != "") {
          $filter .= " and c.client = '$client'";
        }
        if ($region != "") {
          $filter .= " and c.region = '$region'";
        }
        if ($province != "") {
          $filter .= " and c.province = '$province'";
        }
        if ($area != "") {
          $filter .= " and c.area = '$area'";
        }
        if ($brgy != "") {
          $filter .= " and c.brgy = '$brgy'";
        }
        break;
      case 23: //labsol cebu
        $client = $config['params']['dataparams']['client'];
        $salestype     = $config['params']['dataparams']['salestype'];
        if ($client != "") {
          $filter .= " and c.client = '$client'";
        }
        if ($salestype != "") {
          $filter .= " and head.salestype = '$salestype'";
        }
        break;
      default:
        $salestype     = $config['params']['dataparams']['salestype'];
        if ($salestype != "") {
          $filter .= " and head.salestype = '$salestype'";
        }
        break;
    }

    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }
    if ($partname != "") {
      $filter .= " and i.part = $principal ";
    }
    if ($grpname != "") {
      $filter .= " and i.groupid = $division ";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category'";
    }

    switch ($companyid) {
      case 14: // majesty
        $query = "
          select a.agname,a.barcode,a.itemname,a.uom,count(a.docno) as notr,sum(a.qty) as qty,sum(a.amt) as amt, a.principal, sum(a.lessvat) as lessvat, sum(a.sramt) as sramt, sum(a.pwdamt) as pwdamt
          from (
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client as a on a.client=head.agent
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='SJ' and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
            UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client as a on a.clientid=head.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='SJ' and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
              UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client as a on a.client=stock.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='SJ' and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter
            UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client as a on a.clientid=stock.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='SJ' and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter              
          ) as a
          group by a.agcode,a.agname,a.itemname,a.barcode,a.uom, a.principal, a.lessvat, a.sramt, a.pwdamt
          order by a.agcode desc
        ";
        break;

      default:
        $query = "
          select a.agname,a.barcode,a.itemname,a.uom,count(a.docno) as notr,sum(a.qty) as qty,sum(a.amt) as amt, a.principal, a.lessvat, a.sramt, a.pwdamt
          from (
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(" . $uomfield . ",'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client as a on a.client=head.agent
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.client=head.client
              where head.doc in ('SJ','MJ') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
            UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(" . $uomfield . ",'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client as a on a.clientid=head.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.clientid=head.clientid
              where head.doc in ('SJ','MJ') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
              UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(" . $uomfield . ",'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client as a on a.client=stock.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.client=head.client
              where head.doc in ('SJ','MJ') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter
            UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(" . $uomfield . ",'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client as a on a.clientid=stock.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.clientid=head.clientid
              where head.doc in ('SJ','MJ') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter              
          ) as a
          group by a.agcode,a.agname,a.itemname,a.barcode,a.uom, a.principal, a.lessvat, a.sramt, a.pwdamt
          order by a.agcode desc
        ";
        break;
    }

    return $query;
  }

  public function ByArea_QUERY($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent     = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];
    $principal     = $config['params']['dataparams']['partid'];
    $division     = $config['params']['dataparams']['groupid'];
    $category     = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $brandid     = $config['params']['dataparams']['brandid'];
    $brandname = $config['params']['dataparams']['brandname'];
    $client = $config['params']['dataparams']['client'];
    $region = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $area = $config['params']['dataparams']['area'];
    $brgy = $config['params']['dataparams']['brgy'];

    $filter = "";
    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }
    if ($principal != "") {
      $filter .= " and i.part = $principal ";
    }
    if ($division != "") {
      $filter .= " and i.groupid = $division ";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category'";
    }
    if ($brandname != "") {
      $filter .= " and i.brand = $brandid";
    }
    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }
    if ($region != "") {
      $filter .= " and c.region = '$region'";
    }
    if ($province != "") {
      $filter .= " and c.province = '$province'";
    }
    if ($area != "") {
      $filter .= " and c.area = '$area'";
    }
    if ($brgy != "") {
      $filter .= " and c.brgy = '$brgy'";
    }

    $add = '';
    $add2 = '';
    $reptype = $config['params']['dataparams']['reporttype'];
    switch ($reptype) {
      case 'area':
        $add = ', c.area';
        $add2 = ' , a.area';
        break;
      case 'client':
        $add = ' , head.clientname, c.brgy, c.area';
        $add2 = ' , a.clientname, a.brgy, a.area';
        break;
      case 'category':
        $add = '';
        $add2 = ', a.category';
        break;
    }

    $query = "
          select a.agname,sum(a.qty) as qty,sum(a.amt) as amt, a.uom $add2
          from (
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(stock.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt $add
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client as a on a.client=head.agent
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.client=head.client
              where head.doc='SJ' and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
            UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(stock.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt $add
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client as a on a.clientid=head.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.clientid=head.clientid
              where head.doc='SJ' and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
              UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(stock.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt $add
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client as a on a.client=stock.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.client=head.client
              where head.doc='SJ' and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter
            UNION ALL
            select
              ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division, info.lessvat, info.sramt, info.pwdamt,
              ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
              ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
              head.docno,
              ifnull(i.itemname,'NO ITEM') as itemname,ifnull(stock.uom,'NO UNIT') as uom, ifnull(i.barcode,'') as barcode,
              ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt $add
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client as a on a.clientid=stock.agentid
              left join item as i on i.itemid=stock.itemid
              left join part_masterfile as p on p.part_id=i.part
              left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
              left join itemcategory as ic on ic.line=i.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              left join client as c on c.clientid=head.clientid
              where head.doc='SJ' and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter              
          ) as a
          group by a.agcode,a.agname, a.uom $add2
          order by a.agname $add2
        ";
    return $query;
  }

  public function report_Detail_Headertable($config)
  {
    $str = "";
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($config['params']['companyid']) {
      case 32: //3m
        $str .= $this->reporter->col('AGENT. NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('AGENT. NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function report_Detail_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $principal     = $config['params']['dataparams']['partname'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['categoryname'];

    switch ($config['params']['companyid']) {
      case 32: //3M
        $brand     = $config['params']['dataparams']['brand'];
        $client   = $config['params']['dataparams']['client'];
        $region     = $config['params']['dataparams']['region'];
        $province     = $config['params']['dataparams']['province'];
        $area     = $config['params']['dataparams']['area'];
        $brgy     = $config['params']['dataparams']['brgy'];

        if ($brand != "") {
          $brand = $config['params']['dataparams']['brandname'];
        } else {
          $brand = "ALL";
        }

        if ($client != "") {
          $client = $config['params']['dataparams']['clientname'];
        } else {
          $client = "ALL";
        }

        if ($region != "") {
          $region = $config['params']['dataparams']['region'];
        } else {
          $region = "ALL";
        }

        if ($province != "") {
          $province = $config['params']['dataparams']['province'];
        } else {
          $province = "ALL";
        }

        if ($area != "") {
          $area = $config['params']['dataparams']['area'];
        } else {
          $area = "ALL";
        }

        if ($brgy != "") {
          $brgy = $config['params']['dataparams']['brgy'];
        } else {
          $brgy = "ALL";
        }
        break;
      case 23: //labsol cebu
        $salestype     = $config['params']['dataparams']['salestype'];
        $client   = $config['params']['dataparams']['client'];
        if ($client != "") {
          $client = $config['params']['dataparams']['clientname'];
        } else {
          $client = "ALL";
        }
        if ($salestype != "") {
          $salestype = $config['params']['dataparams']['salestype'];
        } else {
          $salestype = "ALL";
        }
        break;

      default:
        $salestype     = $config['params']['dataparams']['salestype'];
        if ($salestype != "") {
          $salestype = $config['params']['dataparams']['salestype'];
        } else {
          $salestype = "ALL";
        }
        break;
    }

    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }

    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename . ' (Detailed)', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    switch ($config['params']['companyid']) {
      case 32: //3M
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Agent: ' . $agentname, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Customer: ' . $client, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Region: ' . $region, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part: ' . $principal, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Group: ' . $division, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Province: ' . $province, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Category: ' . $category, '292', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Brand: ' . $brand, '298', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Area: ' . $area, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '305', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Barangay: ' . $brgy, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;

      default:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Trans Type: ' . $salestype, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Principal: ' . $principal, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Agent: ' . $agentname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Division: ' . $division, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category: ' . $category, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($config['params']['companyid'] == 23) { //labsol cebu
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $client, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        break;
    }

    $str .= '<br>';

    return $str;
  }

  public function report_Detail_Layout($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $subborder = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_Detail_Header($config);
    $str .= $this->report_Detail_Headertable($config);

    $totaltrnx = 0;
    $totalqty = 0;
    $totalamt = 0;
    $subtrnx = 0;
    $subqty = 0;
    $subamt = 0;
    $agname = '';

    foreach ($result as $key => $data) {

      if ($agname != '' && $agname != $data->agname) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        switch ($config['params']['companyid']) {
          case 32: //3m
            $str .= $this->reporter->col('SUB TOTAL', '400', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            break;
          default:
            $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            break;
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      if ($agname == '' || $agname != $data->agname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $agname = $data->agname;
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      switch ($config['params']['companyid']) {
        case 32: //3m
          $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          break;
      }
      $str .= $this->reporter->endrow();
      $totaltrnx += $data->notr;
      $totalqty += $data->qty;
      $totalamt += $data->amt;
      $subtrnx += $data->notr;
      $subqty += $data->qty;
      $subamt += $data->amt;
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        switch ($config['params']['companyid']) {
          case 32: //3m
            $str .= $this->reporter->col('SUB TOTAL', '400', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            break;
          default:
            $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
            break;
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;

        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->report_Detail_Header($config);
        $str .= $this->report_Detail_Headertable($config);

        $page = $page + $count;
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($config['params']['companyid']) {
      case 32: //3m
        $str .= $this->reporter->col('SUB TOTAL', '400', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($config['params']['companyid']) {
      case 32: //3m
        $str .= $this->reporter->col('GRAND TOTAL', '400', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function majesty_Detail_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $principal     = $config['params']['dataparams']['partname'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $salestype     = $config['params']['dataparams']['salestype'];
    $category     = $config['params']['dataparams']['categoryname'];

    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }

    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }
    if ($salestype != "") {
      $salestype = $config['params']['dataparams']['salestype'];
    } else {
      $salestype = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename . ' (Detailed)', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Trans Type: ' . $salestype, '600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Principal: ' . $principal, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Division: ' . $division, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category: ' . $category, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT. NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PRINCIPAL', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DISC', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function majesty_Detail_Layout($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $subborder = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->majesty_Detail_Header($config);

    $totaltrnx = 0;
    $totalqty = 0;
    $totalamt = 0;
    $totaldisc = 0;
    $totalvat = 0;
    $subtrnx = 0;
    $subqty = 0;
    $subamt = 0;
    $subvat = 0;
    $subdisc = 0;
    $agname = '';

    foreach ($result as $key => $data) {

      if ($agname != '' && $agname != $data->agname) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUB TOTAL', '200', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subdisc, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subvat, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;
        $subdisc = 0;
        $subvat = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      if ($agname == '' || $agname != $data->agname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '1200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $agname = $data->agname;
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->principal, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(number_format($data->sramt, $this->companysetup->getdecimal('currency', $config['params'])) . ' / ' . number_format($data->pwdamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->lessvat, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;
        $str .= $this->reporter->page_break();
        $str .= $this->majesty_Detail_Header($config);

        $page = $page + $count;
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '1200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }
      $totaltrnx += $data->notr;
      $totalqty += $data->qty;
      $totalamt += $data->amt;
      $totaldisc += round($data->sramt, $this->companysetup->getdecimal('currency', $config['params'])) + round($data->pwdamt, $this->companysetup->getdecimal('currency', $config['params']));
      $totalvat += $data->lessvat;
      $subtrnx += $data->notr;
      $subqty += $data->qty;
      $subamt += $data->amt;
      $subdisc += round($data->sramt, $this->companysetup->getdecimal('currency', $config['params'])) + round($data->pwdamt, $this->companysetup->getdecimal('currency', $config['params']));
      $subvat += $data->lessvat;
      // }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUB TOTAL', '200', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subdisc, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subvat, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldisc, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvat, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_ByArea_Headertable($config)
  {
    $reptype = $config['params']['dataparams']['reporttype'];
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    if ($reptype == "client") {
      $layoutsize = '1000';
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('AGENT. NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('AREA', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $layoutsize = '800';
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('AGENT. NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
      switch ($reptype) {
        case 'area':
          $str .= $this->reporter->col('AREA', '300', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
          break;
        case 'category':
          $str .= $this->reporter->col('CATEGORY', '300', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
          break;
      }
      $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function report_ByArea_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $principal     = $config['params']['dataparams']['partname'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['categoryname'];

    $brand     = $config['params']['dataparams']['brand'];
    $client   = $config['params']['dataparams']['client'];
    $region     = $config['params']['dataparams']['region'];
    $province     = $config['params']['dataparams']['province'];
    $area     = $config['params']['dataparams']['area'];
    $brgy     = $config['params']['dataparams']['brgy'];


    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }

    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }

    if ($brand != "") {
      $brand = $config['params']['dataparams']['brandname'];
    } else {
      $brand = "ALL";
    }

    if ($client != "") {
      $client = $config['params']['dataparams']['clientname'];
    } else {
      $client = "ALL";
    }

    if ($region != "") {
      $region = $config['params']['dataparams']['region'];
    } else {
      $region = "ALL";
    }

    if ($province != "") {
      $province = $config['params']['dataparams']['province'];
    } else {
      $province = "ALL";
    }

    if ($area != "") {
      $area = $config['params']['dataparams']['area'];
    } else {
      $area = "ALL";
    }

    if ($brgy != "") {
      $brgy = $config['params']['dataparams']['brgy'];
    } else {
      $brgy = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename . ' (By Area)', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '295', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer: ' . $client, '295', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Region: ' . $region, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part: ' . $principal, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Group: ' . $division, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Province: ' . $province, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Category: ' . $category, '292', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Brand: ' . $brand, '294', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Area: ' . $area, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barangay: ' . $brgy, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    return $str;
  }

  public function report_ByArea_Layout($config)
  {
    $result = $this->reportDefault($config);
    $reptype = $config['params']['dataparams']['reporttype'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $reptype == 'client' ? '1000' : '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $subborder = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_ByArea_Header($config);
    $str .= $this->report_ByArea_Headertable($config);

    // $totaltrnx = 0;
    $totalqty = 0;
    $totalamt = 0;
    // $subtrnx = 0;
    $subqty = 0;
    $subamt = 0;
    $agname = '';


    foreach ($result as $key => $data) {
      if ($agname != '' && $agname != $data->agname) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($reptype == 'client') {
          $str .= $this->reporter->col('SUB TOTAL', '700', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      if ($agname == '' || $agname != $data->agname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $agname = $data->agname;
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      switch ($reptype) {
        case 'area':
          $str .= $this->reporter->col($data->area, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          break;
        case 'client':
          $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          break;
        case 'category':
          $str .= $this->reporter->col($data->category, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          break;
      }
      $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalqty += $data->qty;
      $totalamt += $data->amt;
      $subqty += $data->qty;
      $subamt += $data->amt;
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($reptype == 'client') {
          $str .= $this->reporter->col('SUB TOTAL', '700', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;

        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->report_ByArea_Header($config);
        $str .= $this->report_ByArea_Headertable($config);
        $page = $page + $count;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($reptype == 'client') {
      $str .= $this->reporter->col('SUB TOTAL', '700', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($reptype == 'client') {
      $str .= $this->reporter->col('GRAND TOTAL', '700', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}
