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

class ksi_reorder_report
{
  public $modulename = 'KSI Reorder Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField()
  {
    $fields = ['radioprint', 'start', 'ditemname', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'As of Date');

    $fields = ['radiolayoutformat'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'radiolayoutformat.label', 'Excluded Warehouse');
    data_set($col2, 'radiolayoutformat.options', array(
      ['label' => 'Exclude Dummy Warehouse', 'value' => '0', 'color' => 'orange'],
      ['label' => 'None', 'value' => '1', 'color' => 'orange']
    ));

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata()
  {
    $paramstr = "select 
    'default' as print,
    left(now(),10) as start,
    '' as itemname,
    '' as barcode,
    '' as ditemname,
    '0' as layoutformat,
    '' as whid,
    '' as wh,
    '' as whname,
    '' as dwhname";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function getloaddata()
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
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $result = $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->defaultQuery($config);

    return $this->coreFunctions->opentable($query);
  }

  public function defaultQuery($config)
  {
    $asof = $config['params']['dataparams']['start'];
    $barcode = $config['params']['dataparams']['barcode'];
    $whid = $config['params']['dataparams']['whid'];
    $wh = $config['params']['dataparams']['wh'];
    $excludeWh = $config['params']['dataparams']['layoutformat'];
    $filter = '';
    $filter1 = '';

    if ($barcode != '') {
      $filter .= " and i.barcode = '" . $barcode . "'";
    }

    if ($excludeWh == 0) {
      $filter1 .= " and stock.whid <> 1014";
    }

    if ($wh != '') {
      $filter1 .= " and stock.whid  = " . $whid . "";
    }

    $query = "select i.itemid, i.barcode, i.itemname, i.packaging, i.tqty, i.amt as sprice, ifnull(sum(ksi.qty-ksi.iss), 0) as stocksonhand, 
              ifnull(sum(ksi.begqty - ksi.begiss), 0) as begqty, i.amt2 as dprice, i.foramt as fprice, i.uom as unit, ifnull(il.min, 0) as min, ifnull(il.max, 0) as max, 
              (select rr.cost from rrstatus as rr where rr.itemid = i.itemid and rr.dateid <= '$asof' order by dateid desc limit 1) as latestcost,
              ifnull((select sum(stock.qty - stock.qa) as incoming from hpohead as head left join hpostock as stock on head.trno = stock.trno 
              where head.dateid <= '$asof' and stock.itemid = i.itemid and stock.qa <> stock.qty $filter1), 0) as incomingpo,
              ifnull((select sum(stock.iss - stock.qa) as pending from hsohead as head left join hsostock as stock on stock.trno = head.trno 
              where head.dateid <= '$asof' and stock.itemid = i.itemid and stock.qa <> stock.iss $filter1), 0) as pendingso
              from item as i
              left join (select head.dateid, stock.itemid, stock.qa, stock.void, stock.qty, stock.iss, 0 as begqty, 0 as begiss, stock.whid
              from lahead as head
              left join lastock as stock on head.trno = stock.trno where head.dateid <= '$asof' $filter1  
              union all
              select head.dateid, stock.itemid, stock.qa, stock.void, stock.qty, stock.iss, 0 as begqty, 0 as begiss, stock.whid
              from glhead as head
              left join glstock as stock on head.trno = stock.trno where head.dateid <= '$asof' $filter1  
              union all 
              select head.dateid, stock.itemid, stock.qa, stock.void, 0 as qty, 0 as iss, stock.qty as begqty, stock.iss as begiss, stock.whid
              from lahead as head
              left join lastock as stock on head.trno = stock.trno where head.dateid < '$asof' $filter1
              union all
              select head.dateid, stock.itemid, stock.qa, stock.void, 0 as qty, 0 as iss, stock.qty as begqty, stock.iss as begiss, stock.whid
              from glhead as head
              left join glstock as stock on head.trno = stock.trno where head.dateid < '$asof' $filter1) as ksi on ksi.itemid = i.itemid
              left join itemlevel as il on il.itemid = i.itemid
              where '' = '' $filter
              group by i.itemid, i.barcode, i.packaging, i.tqty,i.amt,i.amt2, i.itemname, i.uom, il.min, il.max,i.foramt";

    return $query;
  }

  private function defaultHeader($config)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $asof       = $config['params']['dataparams']['start'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whname     = $config['params']['dataparams']['whname'];

    if ($whname == '') {
      $whname = "ALL";
    }

    $str = '';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('KSI REORDER REPORT', '200', null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of Date: ' . $asof, '500', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

    if ($barcode == '') {
      $str .= $this->reporter->col('Items: ALL', '500', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items: ' . $barcode, '500', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }

    $str .= $this->reporter->col('WH : ' . $whname, '500', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function defaultTableCols($layoutSize, $border, $font, $fontsize)
  {
    $layoutSize = $this->reportParams['layoutSize'];
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutSize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '220', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PACKAGING', '70', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY PER CASE', '60', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STANDARD PRICE', '70', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DISTRIBUTOR PRICE', '70', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FLOOR PRICE', '70', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LAST COST', '70', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UNIT', '60', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL SOLD PAST QUARTER', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL SOLD FOR THE QUARTER', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL PENDING SO', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BEGINNING INVENTORY', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STOCKS ON HAND', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INCOMING STOCKS', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MINIMUM', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAXIMUM', '90', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $layoutSize = $this->reportParams['layoutSize'];
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;

    $asof = $config['params']['dataparams']['start'];
    $whid = $config['params']['dataparams']['layoutformat'];
    $excludeWh = $whid == 0 ? 1014 : 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutSize);;
    $str .= $this->defaultHeader($config);
    $str .= $this->defaultTableCols($layoutSize, $border, $font, $fontsize11);

    $totaltqty = 0;
    $totalsoldpq = 0;
    $totalsoldfq = 0;
    $totalpendingso = 0;
    $totalbegqty = 0;
    $totalstocksonhand = 0;
    $totalincomingpo = 0;
    $totalmin = 0;
    $totalmax = 0;

    foreach ($result as $key => $data) {

      $totalSoldPastQuarter = $this->getTotalSoldPastQuarter($asof, $data->itemid, $excludeWh);
      $totalSoldForTheQuarter = $this->getTotalSoldForTheQuarter($asof, $data->itemid, $excludeWh);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '220', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->packaging, '70', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->tqty), '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->sprice), '70', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->dprice), '70', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->fprice), '70', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->latestcost), '70', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->unit, '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($totalSoldPastQuarter), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($totalSoldForTheQuarter), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->pendingso), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->begqty), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->stocksonhand), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->incomingpo), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->min), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($this->defaultValue($data->max), '90', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $totaltqty += $data->tqty;
      $totalsoldpq += $totalSoldPastQuarter;
      $totalsoldfq += $totalSoldForTheQuarter;
      $totalpendingso += $data->pendingso;
      $totalbegqty += $data->begqty;
      $totalstocksonhand += $data->stocksonhand;
      $totalincomingpo += $data->incomingpo;
      $totalmin += $data->min;
      $totalmax += $data->max;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL: ', '100', null, false, '1px solid ', 'TB', 'LT', $font, $font_size, 'B', '', '4px');
    $str .= $this->reporter->col('', '220', null, false, '1px solid ', 'TB', 'LT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', 'TB', 'CT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totaltqty, 2), '60', null, false, '1px solid ', 'TB', 'CT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '60', null, false, '1px solid ', 'TB', 'CT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalsoldpq, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalsoldfq, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalpendingso, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalbegqty, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalstocksonhand, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalincomingpo, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalmin, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalmax, 2), '90', null, false, '1px solid ', 'TB', 'RT', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function defaultValue($value)
  {
    return $value == 0 ? '-' : number_format($value, 2);
  }

  private function getQuarterDates($asof, $type)
  {
    $month = date('n', strtotime($asof));
    $year = date('Y', strtotime($asof));

    if ($type == 'past') {
      if ($month >= 1 && $month <= 3) {
        $start = date('Y-m-d', strtotime(($year - 1) . '-10-01'));
        $end = date('Y-m-d', strtotime(($year - 1) . '-12-31'));
      } elseif ($month >= 4 && $month <= 6) {
        $start = date('Y-m-d', strtotime($year . '-01-01'));
        $end = date('Y-m-d', strtotime($year . '-03-31'));
      } elseif ($month >= 7 && $month <= 9) {
        $start = date('Y-m-d', strtotime($year . '-04-01'));
        $end = date('Y-m-d', strtotime($year . '-06-30'));
      } else {
        $start = date('Y-m-d', strtotime($year . '-07-01'));
        $end = date('Y-m-d', strtotime($year . '-09-30'));
      }
    } else {
      if ($month >= 1 && $month <= 3) {
        $start = date('Y-m-d', strtotime($year . '-01-01'));
        $end = date('Y-m-d', strtotime($year . '-03-31'));
      } elseif ($month >= 4 && $month <= 6) {
        $start = date('Y-m-d', strtotime($year . '-04-01'));
        $end = date('Y-m-d', strtotime($year . '-06-30'));
      } elseif ($month >= 7 && $month <= 9) {
        $start = date('Y-m-d', strtotime($year . '-07-01'));
        $end = date('Y-m-d', strtotime($year . '-09-30'));
      } else {
        $start = date('Y-m-d', strtotime($year . '-10-01'));
        $end = date('Y-m-d', strtotime($year . '-12-31'));
      }
    }

    return [$start, $end];
  }

  private function getTotalSoldPastQuarter($asof, $itemid, $whid)
  {
    list($start, $end) = $this->getQuarterDates($asof, 'past');

    $qry = "select sum(iss) as value from ( select ifnull(sum(stock.iss), 0) as iss from glhead as head
            left join glstock as stock on stock.trno = head.trno
            where head.doc = 'SJ' and head.dateid <= ? and head.dateid >= ? and 
            stock.itemid = ? and stock.whid <> ? 
            union all
            select ifnull(sum(stock.iss), 0) as iss from lahead as head
            left join lastock as stock on stock.trno = head.trno
            where head.doc = 'SJ' and head.dateid <= ? and head.dateid >= ? and 
            stock.itemid = ? and stock.whid <> ? ) as a";
    $this->coreFunctions->LogConsole($qry . $start . ',' . $end);
    $result = $this->coreFunctions->datareader($qry, [$end, $start, $itemid, $whid, $end, $start, $itemid, $whid]);
    return $result;
  }

  private function getTotalSoldForTheQuarter($asof, $itemid, $whid)
  {
    list($start, $end) = $this->getQuarterDates($asof, 'current');

    $qry = "select sum(iss) as value from (select ifnull(sum(stock.iss), 0) as iss from glhead as head
            left join glstock as stock on stock.trno = head.trno
            where head.doc = 'SJ' and head.dateid >= ? and head.dateid <= ? and stock.itemid = ? and 
            stock.whid <> ?
            union all
            select ifnull(sum(stock.iss), 0) as iss from lahead as head
            left join lastock as stock on stock.trno = head.trno
            where head.doc = 'SJ' and head.dateid >= ? and head.dateid <= ? and stock.itemid = ? and 
            stock.whid <> ?) as a";
    $result = $this->coreFunctions->datareader($qry, array($start, $end, $itemid, $whid, $start, $end, $itemid, $whid));
    return $result;
  }
}//end class