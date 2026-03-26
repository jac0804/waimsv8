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

class sales_per_agent
{
  public $modulename = 'Sales Per Agent';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
    switch ($companyid) {
      case 34: //evergreen
        $fields = ['radioprint', 'start', 'end', 'dagentname'];
        break;
      default:
        $fields = ['radioprint', 'start', 'end', 'dagentname', 'part', 'divsion', 'category', 'salestype'];
        break;
    }
    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
      array_push($fields, 'brandname');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'part.label', 'Part');
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'category.name', 'categoryname');
    data_set($col1, 'salestype.required', false);

    unset($col1['divsion']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'part.name', 'partname');

    $fields = ['radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    data_set(
      $col2,
      'radioreporttype.options',
      [
        ['label' => 'Summarized', 'value' => 'summary', 'color' => 'teal'],
        ['label' => 'Detailed', 'value' => 'detail', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      0 as agentid,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      0 as partid,
      '' as partname,
      '' as part,
      '' as divsion,
      0 as groupid,
      '' as stockgrp,
      '' as category,
      '' as categoryname,
      '' as salestype,
      '' as brandname, 
      0 as brandid,
      'summary' as reporttype
    ");
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
    switch ($companyid) {
      case 34: //evergreen
        switch ($reptype) {
          case 'detail':
            $result = $this->eapp_Detail_Layout($config);
            break;
          case 'summary':
            $result = $this->eapp_Summary_Layout($config);
            break;
        }
        break;

      default:
        switch ($reptype) {
          case 'detail':
            $result = $this->report_Detail_Layout($config);
            break;
          case 'summary':
            $result = $this->report_Summary_Layout($config);
            break;
        }
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $reptype = $config['params']['dataparams']['reporttype'];
    switch ($companyid) {
      case 34: //evergreen
        switch ($reptype) {
          case 'detail':
            $query = $this->eapp_Detail_QUERY($config);
            break;
          case 'summary':
            $query = $this->eapp_Summary_QUERY($config);
            break;
        }
        break;

      default:
        switch ($reptype) {
          case 'detail':
            $query = $this->Detail_QUERY($config);
            break;
          case 'summary':
            $query = $this->Summary_QUERY($config);
            break;
        }
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function Detail_QUERY($config)
  {
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid  = $config['params']['dataparams']['agentid'];
    $agent     = $config['params']['dataparams']['agent'];
    $principal     = $config['params']['dataparams']['partid'];
    $partname = $config['params']['dataparams']['partname'];
    $grpname     = $config['params']['dataparams']['stockgrp'];
    $division     = $config['params']['dataparams']['groupid'];
    $category     = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $salestype     = $config['params']['dataparams']['salestype'];

    $filter = "";

    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }
    if ($partname != "") {
      $filter .= " and i.part = $principal";
    }
    if ($grpname != "") {
      $filter .= " and i.groupid = $division";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category' ";
    }
    if ($salestype != "") {
      $filter .= " and head.salestype = '$salestype' ";
    }

    //labsol cebu, labsol manila, technolab
    if ($config['params']['companyid'] == 23 || $config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) { //labsol
      $brandid = $config['params']['dataparams']['brandid'];
      if ($config['params']['dataparams']['brandname'] != '') {
        $filter .= " and i.brand=$brandid";
      }
    }

    $query = "
      select a.agcode, a.agname,a.itemname,a.uom,count(a.docno) as notr, a.docno,sum(a.qty) as qty,sum(a.amt) as amt
      from (
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,
          ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom,
          ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as a on a.client=head.agent
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
        UNION ALL
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,
          ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom,
          ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as a on a.clientid=head.agentid
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
          UNION ALL
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,
          ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom,
          ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as a on a.clientid=stock.agentid
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter
        UNION ALL
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,
          ifnull(i.itemname,'NO ITEM') as itemname,ifnull(i.uom,'NO UNIT') as uom,
          ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as a on a.clientid=stock.agentid
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter          
      ) as a
      group by a.agcode,a.agname,a.itemname,a.uom,a.docno
      order by a.agcode desc
    ";

    return $query;
  }

  public function Summary_QUERY($config)
  {
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agent     = $config['params']['dataparams']['agent'];
    $principal     = $config['params']['dataparams']['partid'];
    $partname = $config['params']['dataparams']['partname'];
    $division     = $config['params']['dataparams']['groupid'];
    $grpname = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['category'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $salestype     = $config['params']['dataparams']['salestype'];

    $filter = "";

    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }
    if ($partname != "") {
      $filter .= " and i.part = $principal";
    }
    if ($grpname != "") {
      $filter .= " and i.groupid = $division";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category'";
    }
    if ($salestype != "") {
      $filter .= " and head.salestype = '$salestype'";
    }

    //labsol cebu, labsol manila, technolab
    if ($config['params']['companyid'] == 23 || $config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) {
      $brandid = $config['params']['dataparams']['brandid'];
      if ($config['params']['dataparams']['brandname'] != '') {
        $filter .= " and brand.brandid=" . $brandid;
      }
    }

    $query = "
      select a.agcode, a.agname,count(a.docno) as notr,sum(a.qty) as qty,sum(a.amt) as amt 
      from (
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as a on a.client=head.agent
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
        UNION ALL
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as a on a.clientid=head.agentid
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
        UNION ALL
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as a on a.clientid=stock.agentid
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter
        UNION ALL
        select
          ifnull(p.part_name,'') as principal,ifnull(g.stockgrp_name,'') as division,
          ifnull(ic.name,'') as category,ifnull(head.salestype,'') as stype,
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          head.docno,ifnull(stock.isqty,0.00) as qty,ifnull(stock.ext-abs(ifnull(info.lessvat,0))-abs(ifnull(info.sramt,0))-abs(ifnull(info.pwdamt,0)),0.00) as amt
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as a on a.clientid=stock.agentid
          left join item as i on i.itemid=stock.itemid
          left join part_masterfile as p on p.part_id=i.part
          left join stockgrp_masterfile as g on g.stockgrp_id=i.groupid
          left join itemcategory as ic on ic.line=i.category
          left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join frontend_ebrands as brand on brand.brandid=i.brand
          where head.doc in ('SJ','MJ','CP') and left(head.docno,3)='SJS' and head.dateid between '$start' and '$end' $filter 
      ) as a
      group by a.agcode,a.agname
      order by a.agcode desc
    ";

    return $query;
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
    $fontsize = "11";
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
    $str .= $this->reporter->col($this->modulename . ' (Summarized)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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

    // labsol cebu, labsol manila, technolab
    if ($config['params']['companyid'] == 23 || $config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) {
      $str .= $this->reporter->col('Brand: ' . ($config['params']['dataparams']['brandname'] != '' ? $config['params']['dataparams']['brandname'] : 'ALL'), '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Category: ' . $category, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function default_summary_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT. NAME', '500', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function report_Summary_Layout($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_Summary_Header($config);
    $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);

    $totaltrnx = 0;
    $totalqty = 0;
    $totalamt = 0;


    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->agname . ' (' . $data->agcode . ')', '500', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $totaltrnx += $data->notr;
      $totalqty += $data->qty;
      $totalamt += $data->amt;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();



        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->report_Summary_Header($config);
        }
        $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);
        $str .= $this->reporter->begintable($layoutsize);

        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

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
    $fontsize = "11";
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
    $str .= $this->reporter->col($this->modulename . ' (Detailed)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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

    // labsol cebu, labsol manila, technolab
    if ($config['params']['companyid'] == 23 || $config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) {
      $str .= $this->reporter->col('Brand: ' . ($config['params']['dataparams']['brandname'] != '' ? $config['params']['dataparams']['brandname'] : 'ALL'), '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Category: ' . $category, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function default_detail_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->printline();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $str .= $this->reporter->col('AGENT. NAME', '150', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '150', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('AGENT. NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
        break;
    }
    $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function report_Detail_Layout($config)
  {
    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";
    $subborder = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_Detail_Header($config);
    $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);

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
        $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      if ($agname == '' || $agname != $data->agname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname . ' (' . $data->agcode . ')', '800', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
        $agname = $data->agname;
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      switch ($companyid) {
        case 23: //labsol cebu
        case 41: //labsol manila
        case 52: //technolab
          $str .= $this->reporter->col('', '150', null, false, $border, '', 'LT', $font, $fontsize - 1, '', '', '');
          $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'LT', $font, $fontsize - 1, '', '', '');
          $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'LT', $font, $fontsize - 1, '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
          break;
      }
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'RT', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'RT', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'RT', $font, $fontsize - 1, '', '', '');
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
        $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtrnx = 0;
        $subqty = 0;
        $subamt = 0;

        $str .= $this->reporter->page_break();



        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->report_Detail_Header($config);
        }
        $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);


        $page = $page + $count;
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function eapp_Detail_QUERY($config)
  {
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agent     = $config['params']['dataparams']['agent'];

    $filter = "";

    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }

    $query = "
      select a.agcode, a.agname,ifnull(a.plantype,'') as plantype,a.contract,sum(a.amt) as amt,ifnull(a.payor,'') as payor,ifnull(a.planholder,'') as planholder
      from (
        select
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          pt.name as plantype,
          concat(num.bref,lpad(num.seq,8,0)) as contract,
          ifnull(case info.issenior when 1 then pt.amount/1.12 else pt.amount end,0) as amt,
          concat(af.lname,', ',af.fname,' ',af.mname) as payor,
          concat(info.lname,', ',info.fname,' ',info.mname) as planholder
          from lahead as head
          left join client as a on a.client=head.agent
          left join cntnum as num on num.trno=head.trno
          left join heahead as af on af.trno = head.aftrno
          left join heainfo as info on info.trno = af.trno
          left join plantype as pt on pt.line = af.planid
          where head.doc in ('SJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
        UNION ALL
        select
          ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
          pt.name as plantype,
          concat(num.bref,lpad(num.seq,8,0)) as contract,
          ifnull(case info.issenior when 1 then pt.amount/1.12 else pt.amount end,0) as amt,
          concat(af.lname,', ',af.fname,' ',af.mname) as payor,
          concat(info.lname,', ',info.fname,' ',info.mname) as planholder
          from glhead as head
          left join client as a on a.clientid=head.agentid
          left join cntnum as num on num.trno=head.trno
          left join heahead as af on af.trno = head.aftrno
          left join heainfo as info on info.trno = af.trno
          left join plantype as pt on pt.line = af.planid
          where head.doc in ('SJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
              
      ) as a
      group by a.agcode, a.agname,a.plantype,a.contract,a.payor,a.planholder
      order by a.agname, a.plantype
    ";

    return $query;
  }

  public function eapp_Summary_QUERY($config)
  {

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $agent     = $config['params']['dataparams']['agent'];

    $filter = "";

    if ($agent != "") {
      $filter .= " and a.clientid = $agentid";
    }

    $query = "
      select a.agcode, a.agname,a.plantype,count(a.contract) as notr,sum(a.amt) as amt
      from (
        select
                  ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
                  pt.name as plantype,
                  concat(num.bref,lpad(num.seq,8,0)) as contract,
                  ifnull(case info.issenior when 1 then pt.amount/1.12 else pt.amount end,0) as amt,
                  concat(af.lname,', ',af.fname,' ',af.mname) as payor
                  from lahead as head
                  left join client as a on a.client=head.agent
                  left join cntnum as num on num.trno=head.trno
                  left join heahead as af on af.trno = head.aftrno
                  left join heainfo as info on info.trno = af.trno
                  left join plantype as pt on pt.line = af.planid
                  where head.doc in ('SJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
                UNION ALL
                select
                  ifnull(a.client,'NO CODE') as agcode,ifnull(a.clientname,'NO AGENT') as agname,
                  pt.name as plantype,
                  concat(num.bref,lpad(num.seq,8,0)) as contract,
                  ifnull(case info.issenior when 1 then pt.amount/1.12 else pt.amount end,0) as amt,
                  concat(af.lname,', ',af.fname,' ',af.mname) as payor
                  from glhead as head
                  left join client as a on a.clientid=head.agentid
                  left join cntnum as num on num.trno=head.trno
                  left join heahead as af on af.trno = head.aftrno
                  left join heainfo as info on info.trno = af.trno
                  left join plantype as pt on pt.line = af.planid
                  where head.doc in ('SJ','CP') and left(head.docno,3)<>'SJS' and head.dateid between '$start' and '$end' $filter
      ) as a
      group by a.agcode,a.agname,a.plantype
      order by a.agname,plantype
    ";

    return $query;
  }

  public function eapp_Detail_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    $agentname   = $config['params']['dataparams']['agentname'];

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename . ' (Detailed)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function eapp_detail_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT', '200', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PLAN TYPE', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PLAN HOLDER', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CONTRACT #', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PAYOR', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function eapp_Detail_Layout($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";
    $subborder = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->eapp_Detail_Header($config);
    $str .= $this->eapp_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);

    $totalamt = 0;
    $subamt = 0;
    $agname = '';

    foreach ($result as $key => $data) {

      if ($agname != '' && $agname != $data->agname) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUB TOTAL', '650', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '150', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $subamt = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      if ($agname == '' || $agname != $data->agname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname . ' (' . $data->agcode . ')', '800', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
        $agname = $data->agname;
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->plantype, '100', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->planholder, '100', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->contract, '100', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->payor, '150', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '150', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt += $data->amt;

      $subamt += $data->amt;
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUB TOTAL', '650', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '150', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subamt = 0;

        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->eapp_Detail_Header($config);
        }
        $str .= $this->eapp_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);


        $page = $page + $count;
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '800', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUB TOTAL', '650', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');

    $str .= $this->reporter->col(number_format($subamt, $this->companysetup->getdecimal('currency', $config['params'])), '150', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '650', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '150', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function eapp_Summary_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    $agentname   = $config['params']['dataparams']['agentname'];


    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename . ' (Summary)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function eapp_summary_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT. NAME', '500', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PLAN TYPE', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO OF TRNX', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function eapp_Summary_Layout($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->eapp_Summary_Header($config);
    $str .= $this->eapp_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);

    $totaltrnx = 0;
    $totalamt = 0;

    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->agname . ' (' . $data->agcode . ')', '500', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->plantype, '100', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->notr, '100', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      $totaltrnx += $data->notr;

      $totalamt += $data->amt;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();



        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->eapp_Summary_Header($config);
        }
        $str .= $this->eapp_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);
        $str .= $this->reporter->begintable($layoutsize);

        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaltrnx, $this->companysetup->getdecimal('qty', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('currency', $config['params'])), '100', null, false, $border, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}
