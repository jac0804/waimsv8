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

class comparative_report_sales_qty_vs_qty_on_hand
{
  public $modulename = 'Comparative Report Sales Qty VS Qty On Hand';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dateid', 'due', 'datereq'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);
    data_set($col2, 'due.required', true);

    data_set($col2, 'datereq.label', 'Balance Of');
    data_set($col2, 'datereq.readonly', false);
    data_set($col2, 'datereq.required', true);

    $fields = ['part', 'divsion', 'dwhname'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'part.label', 'Principal');
    data_set($col3, 'divsion.label', 'Division');

    unset($col3['part']['labeldata']);
    unset($col3['divsion']['labeldata']);
    unset($col3['labeldata']['part']);
    unset($col3['labeldata']['divsion']);
    data_set($col3, 'part.name', 'partname');
    data_set($col3, 'divsion.name', 'stockgrp');

    $fields = ['refresh'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.action', 'history');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 'default' as print,
      adddate(left(now(),10),-30) as dateid,
      left(now(),10) as due,
      left(now(),10) as datereq,
      0 as partid, 
      '' as part, 
      '' as partname,
      '' as wh, 
      0 as whid, 
      '' as whname, 
      '' as dwhname,
      0 as groupid, 
      '' as stockgrp, 
      '' as divsion,
      '0' as typeofdrsi,
      '0' as reporttype,
      '0' as posttype");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $result = $this->default_query($config);
    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function default_query($filters)
  {
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['datereq']));
    $isexpiry = $this->companysetup->getisexpiry($filters['params']);

    if ($isexpiry) {
      $expiry = ",expiry";
      $tableexpiry = ",stock.expiry";
    } else {
      $expiry = "";
      $tableexpiry = "";
    }

    $partname = $filters['params']['dataparams']['partname'];
    $groupname = $filters['params']['dataparams']['stockgrp'];
    $wh = $filters['params']['dataparams']['wh'];

    $filter = '';
    if ($partname != '') {
      $partid = $filters['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($groupname != '') {
      $groupid = $filters['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($wh != '') {
      $whid = $filters['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    $query = "
    select itemid,barcode,itemname,uom,factor,balance $expiry from(
      select item.itemid,item.barcode,item.itemname,item.uom as uom,
      round((sum(stock.qty-stock.iss)/uom.factor),2) as balance,
      round(uom.factor) as factor $tableexpiry
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid = stock.itemid
      left join uom on uom.uom = item.uom and uom.itemid = item.itemid
      where stock.qty-stock.iss <> 0 and head.dateid<='$asof' $filter
      group by itemid, barcode, itemname, uom, uom.factor $expiry

      UNION ALL

      select
      item.itemid,item.barcode,item.itemname,item.uom as uom,
      round((sum(stock.qty-stock.iss)/uom.factor),2) as balance,
      round(uom.factor) as factor $tableexpiry
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid = stock.itemid
      left join uom on uom.uom = item.uom and uom.itemid = item.itemid
      where stock.qty-stock.iss <> 0 and head.dateid<='$asof' $filter
      group by itemid, barcode, itemname, uom, uom.factor $expiry
    ) as tbl
    group by itemid,barcode,itemname,uom,factor,balance $expiry
    order by itemname $expiry";

    return $this->coreFunctions->opentable($query);
  }

  public function reportplotting($config, $result)
  {
    $reportdata =  $this->default_layout($config, $result);
    return $reportdata;
  }

  private function default_layout($config, $result)
  {
    $font_size = '10';
    
    switch ($config['params']['companyid']) {
      case 14: //majesty
        $font = 'Helvetica';
        break;

      default:
        $font = $this->companysetup->getrptfont($config['params']);
        break;
    }

    $str = '';

    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['due']));
    $asof = date("Y-m-d", strtotime($config['params']['dataparams']['datereq']));

    $part = $config['params']['dataparams']['part'];
    if ($part == '') {
      $part = 'ALL';
    } else {
      $part = $config['params']['dataparams']['partname'];
    }
    $division = $config['params']['dataparams']['groupid'];
    if ($division == '') {
      $division = 'ALL';
    } else {
      $division = $config['params']['dataparams']['stockgrp'];
    }
    $wh = $config['params']['dataparams']['wh'];
    if ($wh == '') {
      $wh = 'ALL';
    } else {
      $wh = $config['params']['dataparams']['whname'];
    }

    $date1 = date_create($startdate);
    $date2 = date_create($enddate);
    $diff = date_diff($date1, $date2);
    $nomonth =  $diff->format("%a"); //14
    $nomonths = floatval($nomonth) / 30; //.4667

    //355.688

    $str .= $this->reporter->beginreport('1000');

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->letterhead($config['params']['center'], $config['params']['user'], $config);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMPARATIVE REPORT - SALES QTY vs QTY ON HAND', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BALANCE AS OF', 165, null, false, '', '', 'C', $font, '13', 'B', '', '');
    $str .= $this->reporter->col($asof, 175, null, false, '1px solid', 'B', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->col('TRANSACTION DATE FROM', 215, null, false, '', '', 'C', $font, '13', 'B', '', '');
    $str .= $this->reporter->col($startdate, 175, null, false, '1px solid', 'B', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->col('TO', 95, null, false, '', '', 'C', $font, '13', 'B', '', '');
    $str .= $this->reporter->col($enddate, 175, null, false, '1px solid', 'B', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse: ' . $wh, null, null, false, '', '', 'L', $font, '15', 'B', '', '6px');
    $str .= $this->reporter->col('Principal: ' . $part, null, null, false, '', '', 'L', $font, '15', 'B', '', '6px');
    $str .= $this->reporter->col('Division: ' . $division, null, null, false, '', '', 'L', $font, '15', 'B', '', '6px');
    $str .= $this->reporter->pagenumber('Page', null, null, false, '1px solid ', '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '150', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '6px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '250', null, false, '1px solid ', 'TB', 'L', $font, '17', 'B', '', '6px');
    $str .= $this->reporter->col('QoH', '50', null, false, '1px solid ', 'TB', 'R', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('UNIT', '50', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('MONTHS TO GO', '75', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('QTY', '50', null, false, '1px solid ', 'TB', 'R', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('SOLD OUT QTY', '75', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('MOVEMENT/MONTH', '50', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('REQ QTY', '100', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->col('SUPP PRICE', '50', null, false, '1px solid ', 'TB', 'C', $font, '17', 'B', '', '');
    $str .= $this->reporter->endrow();

    $rowbarcode = '';
    foreach ($result as $key => $data) {

      if ($rowbarcode == $data->barcode) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp', '150', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '50', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

        if (strlen($data->expiry) != 0 && strlen($data->expiry) == 10) {
          $expiry = date_create($data->expiry);
          if ($expiry) {
            $expiry = date_format($expiry, "m/Y");
          } else {
            $expiry = $data->expiry;
          }
        } else {
          $expiry = $data->expiry;
        }

        $str .= $this->reporter->col('&nbsp', '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');

        if ($data->balance == 0) {
          $balance = '---';
          $expiry = "";
        } else {
          $balance = $data->balance;
        } //end if

        $str .= $this->reporter->col($balance, '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($expiry, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('&nbsp', '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $rowbarcode = $data->barcode;

        $soldQry = "
          select sum(s.iss) as value from (
            select ifnull(sum(soldqtystock.iss/soldqtyuom.factor),0) as iss
            from lahead as soldqtyhead
            left join lastock as soldqtystock on soldqtystock.trno=soldqtyhead.trno
            left join item as soldqtyitem on soldqtyitem.itemid = soldqtystock.itemid
            left join uom as soldqtyuom on soldqtyuom.itemid = soldqtyitem.itemid and soldqtyuom.uom = soldqtyitem.uom
            where soldqtyhead.doc = 'SJ' and soldqtyhead.dateid between '$startdate' and '$enddate'
            and soldqtystock.itemid=$data->itemid
            union all
            select
            ifnull(sum(soldqtystock.iss/soldqtyuom.factor),0) as iss
            from glhead as soldqtyhead
            left join glstock as soldqtystock on soldqtystock.trno=soldqtyhead.trno
            left join item as soldqtyitem on soldqtyitem.itemid = soldqtystock.itemid
            left join uom as soldqtyuom on soldqtyuom.itemid = soldqtyitem.itemid and soldqtyuom.uom = soldqtyitem.uom
            where soldqtyhead.doc = 'SJ' and soldqtyhead.dateid between '$startdate' and '$enddate'
            and soldqtystock.itemid=$data->itemid
          ) as s
        ";
        $soldqty = $this->coreFunctions->datareader($soldQry);

        $balsQry = "
        select sum(bals.b) as value from (
          select ifnull(sum(s.qty-s.iss),0) as b from lastock as s where s.itemid=$data->itemid
          union all
          select ifnull(sum(s.qty-s.iss),0) as b from glstock as s where s.itemid=$data->itemid
        ) as bals
        ";

        $bals = $this->coreFunctions->datareader($balsQry);


        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '150', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->itemname . '&nbsp-' . $data->factor . '\'s', '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        if ($bals == 0) {
          $str .= $this->reporter->col('---', '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($bals, 2), '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        }
        $str .= $this->reporter->col($data->uom, '50', null, false, '1px solid ', '', 'c', $font, $font_size, '', '', '');


        if ($nomonths != 0) {
          if ($soldqty <= 0) {
            $movemonth = 0;
            $months_to_go = 0;
          } else {
            $movemonth = $soldqty / $nomonths; //denominator needs to be month factor (parameters)


            $months_to_go = $bals / $movemonth; //denominator needs to be # of months (parameters)
          } //end if
        } else {
          $movemonth = 0;
          $months_to_go = 0;
        } //end if

        if (strlen($data->expiry) != 0 && strlen($data->expiry) == 10) {
          $expiry = date_create($data->expiry);
          $expiry = date_format($expiry, "m/Y");
        } else {
          $expiry = $data->expiry;
        }

        if ($months_to_go  == 0) {
          $months_to_go = '';
          $str .= $this->reporter->col($months_to_go, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($months_to_go, 2), '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        }

        if ($data->balance == 0) {
          $balance = '---';
          $expiry = "";
        } else {
          $balance = $data->balance;
          $balance = number_format($balance, 2);
        } //end if

        $str .= $this->reporter->col($balance, '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($expiry, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col(number_format($soldqty, 2), '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');

        if ($movemonth  == 0) {
          $movemonth = '';
          $str .= $this->reporter->col($movemonth, '50', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($movemonth, 2), '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        }
        $str .= $this->reporter->col(number_format($soldqty - $bals, 2), '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      } //end if

      // --> Header
    } //end for each

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn
}//end class