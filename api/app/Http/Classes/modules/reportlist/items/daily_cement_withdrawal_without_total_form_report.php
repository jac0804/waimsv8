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

class daily_cement_withdrawal_without_total_form_report
{
  public $modulename = 'Daily Cement Withdrawal Without Total Form Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1000px;max-width:1000px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '975'];



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

    $fields = ['radioprint', 'start', 'end', 'divsion', 'brandname', 'categoryname', 'prepared', 'approved', 'checked'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'brandid.name', 'brandid');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'radioposttype.options', [
      ['label' => 'Unposted', 'value' => '0', 'color' => 'red'],
      ['label' => 'Posted', 'value' => '1', 'color' => 'red'],
      ['label' => 'All', 'value' => '2', 'color' => 'red']
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
      '' as groupid,
      '' as stockgrp,

      '' as brand,
      '' as brandid,
      '' as brandname,

      '' as category,
      '' as categoryname,
      '2' as posttype,
      '' as prepared,
      '' as checked,
      '' as approved";

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
    // <-- layout switching
    switch ($config['params']['dataparams']['print']) {
      default:
        $result = $this->reportDefaultLayout($config, $result);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  // QUERY
  public function DEFAULT_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];

    $category  = $config['params']['dataparams']['category'];

    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];

    $posttype = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    //start

    if ($groupid != "") {
      $filter =  $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($brandname != "") {
      $filter =  $filter . " and brand.brandid='$brand'";
    }


    if ($category != "") {
      $filter = $filter . " and i.category='$category'";
    }


    $order = " order by weightouttime asc";

    switch ($posttype) {
      case '0': //unposted
        $query = "select
        ifnull(info.cwatime,'') as cwatime,
        ifnull(info.cwano,'') as cwano,
        ifnull(stock.ref,'') as soref,
        ifnull(brand.brand_desc,'') as brand,
        ifnull(stock.iss,0) as qty,
        ifnull(info.plateno,'') as plateno,
        ifnull(right(head.docno,7),'') as cwor,
        ifnull(info.assignedlane,'') as assignedlane,
        ifnull(info.weightin,0) as weightin,
        ifnull(info.weightintime,'') as weightintime,
        ifnull(info.batchno,'') as batchno,
        ifnull(info.weightout,0) as weightout,
        ifnull(info.weightouttime,'') as weightouttime,
        ifnull((info.weightout-info.weightin)/stock.iss,0) as weightavg,
        case when trans.bref='ND' then concat(client.areacode,trans.seq) else trans.seq end  as seq,
        ifnull(trans.bref,'') as bref
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item as i on i.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid=i.brand
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid 
        left join itemcategory as cat on cat.line = i.category
        left join transnum as trans on trans.trno=stock.refx
        left join client on client.client=head.client
        where head.doc='SJ' and date(head.dateid) between '$start' and '$end' and stock.iscomponent = 0 $filter
        $order";
        break;
      case '1': //posted
        $query = "select
        ifnull(info.cwatime,'') as cwatime,
        ifnull(info.cwano,'') as cwano,
        ifnull(stock.ref,'') as soref,
        ifnull(brand.brand_desc,'') as brand,
        ifnull(stock.iss,0) as qty,
        ifnull(info.plateno,'') as plateno,
        ifnull(right(head.docno,7),'') as cwor,
        ifnull(info.assignedlane,'') as assignedlane,
        ifnull(info.weightin,0) as weightin,
        ifnull(info.weightintime,'') as weightintime,
        ifnull(info.batchno,'') as batchno,
        ifnull(info.weightout,0) as weightout,
        ifnull(info.weightouttime,'') as weightouttime,
        ifnull((info.weightout-info.weightin)/stock.iss,0) as weightavg,
        case when trans.bref='ND' then concat(client.areacode,trans.seq) else trans.seq end  as seq,
        ifnull(trans.bref,'') as bref
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join item as i on i.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid=i.brand
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid 
        left join itemcategory as cat on cat.line = i.category
        left join transnum as trans on trans.trno=stock.refx
        left join client on client.clientid=head.clientid
        where head.doc='SJ' and date(head.dateid) between '$start' and '$end' and stock.iscomponent = 0 $filter 
        $order";
        break;

      default: //ALL
        $query = "select
        ifnull(info.cwatime,'') as cwatime,
        ifnull(info.cwano,'') as cwano,
        ifnull(stock.ref,'') as soref,
        ifnull(brand.brand_desc,'') as brand,
        ifnull(stock.iss,0) as qty,
        ifnull(info.plateno,'') as plateno,
        ifnull(right(head.docno,7),'') as cwor,
        ifnull(info.assignedlane,'') as assignedlane,
        ifnull(info.weightin,0) as weightin,
        ifnull(info.weightintime,'') as weightintime,
        ifnull(info.batchno,'') as batchno,
        ifnull(info.weightout,0) as weightout,
        ifnull(info.weightouttime,'') as weightouttime,
        ifnull((info.weightout-info.weightin)/stock.iss,0) as weightavg,
        case when trans.bref='ND' then concat(client.areacode,trans.seq) else trans.seq end  as seq,
        ifnull(trans.bref,'') as bref
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item as i on i.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid=i.brand
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid 
        left join itemcategory as cat on cat.line = i.category
        left join transnum as trans on trans.trno=stock.refx
        left join client on client.client=head.client
        where head.doc='SJ' and date(head.dateid) between '$start' and '$end' and stock.iscomponent = 0 $filter
        union all
        select
        ifnull(info.cwatime,'') as cwatime,
        ifnull(info.cwano,'') as cwano,
        ifnull(stock.ref,'') as soref,
        ifnull(brand.brand_desc,'') as brand,
        ifnull(stock.iss,0) as qty,
        ifnull(info.plateno,'') as plateno,
        ifnull(right(head.docno,7),'') as cwor,
        ifnull(info.assignedlane,'') as assignedlane,
        ifnull(info.weightin,0) as weightin,
        ifnull(info.weightintime,'') as weightintime,
        ifnull(info.batchno,'') as batchno,
        ifnull(info.weightout,0) as weightout,
        ifnull(info.weightouttime,'') as weightouttime,
        ifnull((info.weightout-info.weightin)/stock.iss,0) as weightavg,
        case when trans.bref='ND' then concat(client.areacode,trans.seq) else trans.seq end  as seq,
        ifnull(trans.bref,'') as bref
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join item as i on i.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid=i.brand
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid 
        left join itemcategory as cat on cat.line = i.category
        left join transnum as trans on trans.trno=stock.refx
        left join client on client.clientid=head.clientid
        where head.doc='SJ' and date(head.dateid) between '$start' and '$end' and stock.iscomponent = 0 $filter 
        $order";
        break;
    }

    return $query;
  }


  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $groupid    = $config['params']['dataparams']['groupid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $brandname  = $config['params']['dataparams']['brandname'];


    $posttype = $config['params']['dataparams']['posttype'];

    $layoutsize = '975';

    if ($groupname == '') {
      $rgroup = ' All';
    } else {
      $rgroup = $groupname;
    }

    if ($brandname == '') {
      $rbrand = ' All';
    } else {
      $rbrand = $brandname;
    }

    if ($categoryname == '') {
      $rcategory = ' All';
    } else {
      $rcategory = $categoryname;
    }

    switch ($posttype) {
      case '0':
        $posttype = 'UNPOSTED';
        break;
      case '1':
        $posttype = 'POSTED';
        break;

      default:
        $posttype = 'ALL';
        break;
    }

    $supp = '';
    if ($companyid == 21) $supp = $config['params']['dataparams']['client']; //kinggeorge

    $logo = URL::to('/images/reports/gfc.png');
    
    
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();  
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="logo" width="975px" height ="100px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '700', null, false, $border, 'LT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Document Code', '275', null, 'rgb(0, 32, 96)', $border, 'TLR', 'C', $font, $fontsize, '', 'white', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '700', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('MKT03.2', '275', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '700', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Rev. No.', '70', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Eff. Date', '70', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ret.', '70', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Page', '65', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '700', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('6', '70', null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('11June18', '70', null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('3', '70', null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('1 of 1', '65', null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY CEMENT WITHDRAWAL REPORT', '700', null, false, $border, 'LB', 'C', $font, $fontsize+10, 'B', '', '');
    $str .= $this->reporter->col('Latest Update', '140', null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '135', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($start, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('to', '30', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($end, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '530', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('No.', '30', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('T.BK', '40', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('CWA NO', '60', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('SALES ORDER', '70', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');

    $str .= $this->reporter->col('BRAND', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('Q,TY', '60', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('PLATE', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('CWOR', '60', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('L', '20', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');

    $str .= $this->reporter->col('WT. IN', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('T` IN', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');

    $str .= $this->reporter->col('BATCH', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');

    $str .= $this->reporter->col('WT. OUT', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('T` OUT', '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');

    $str .= $this->reporter->col('WT. AV', '60', null, false, $border, 'TLR', 'C', $font, $fontsize - 1, '', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $padding = '';
    $margin = '';
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $groupid    = $config['params']['dataparams']['groupid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $brandname  = $config['params']['dataparams']['brandname'];


    $posttype = $config['params']['dataparams']['posttype'];

    $pagecount = 66;
    $page = 65;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '975';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;');
    $str .= $this->default_displayHeader($config);

    $part = "";
    $brand = "";
    $count = 1;
    $totalqty = 0;
    $totalweightin = 0;
    $totalweightout = 0;
    $totalweightavg = 0;
    $totalamt = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($count, '30', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->cwatime, '40', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->cwano, '60', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->seq, '70', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');

      $str .= $this->reporter->col($data->brand, '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '60', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->plateno, '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->cwor, '60', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      if ($data->assignedlane == 'BULK') {
        $str .= $this->reporter->col('B', '20', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      } else {
        $str .= $this->reporter->col('&nbsp;' . $data->assignedlane, '20', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      }
      $str .= $this->reporter->col(number_format($data->weightin, 2), '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->weightintime, '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->batchno, '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->weightout, 2), '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('&nbsp;' . $data->weightouttime, '50', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->weightavg, 2), '60', null, false, $border, 'TLR', 'R', $font, $fontsize - 1, '', '', '');


      $totalqty += $data->qty;
      $totalweightin += $data->weightin;
      $totalweightout += $data->weightout;
      $totalweightavg += $data->weightavg;

      $count += 1;
      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        
        $str .= $this->default_displayHeader($config);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->printline();
        $page = $page + $pagecount;
      }
    }
    $prepared  = $config['params']['dataparams']['prepared'];
    $approved  = $config['params']['dataparams']['approved'];
    $checked  = $config['params']['dataparams']['checked'];

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('TOTAL', '70', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '60', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'R', $font, $fontsize - 1, '', '', '');



    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By:', '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('Checked By:', '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('Approved By:', '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col($checked, '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class