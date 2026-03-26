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


class stock_transfer_summary_per_item
{
  public $modulename = 'Stock Transfer Summary Per Item';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'start', 'end', 'ddeptname', 'wh', 'wh2'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'ddeptname.label', 'Department');
    data_set($col1, 'wh.label', 'Source Warehouse');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $paramstr = "select
      'default' as print,
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      0 as deptid,
      '' as ddeptname,
      '' as dept,
      '' as deptname,
      0 as whid,
      '' as wh,
      '' as whname,
      0 as whid2,
      '' as wh2,
      '' as wh2name";
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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];

    switch ($companyid) {
      // case 14: //majesty
      //   $result = $this->MAJESTY_Layout($config);
      //   break;
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY($config)
  {
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $deptname = $config['params']['dataparams']['ddeptname'];
    $wh = $config['params']['dataparams']['wh'];
    $wh2 = $config['params']['dataparams']['wh2'];

    $filter = '';
    $filter1 = '';
    $filter2 = '';
    if ($deptname != '') {
      $deptid = $config['params']['dataparams']['deptid'];
      $filter .= " and head.deptid=" . $deptid;
    }
    if ($wh != '') {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and head.wh='" . $wh . "'";
      $filter2 .= " and head.whid=" . $whid;
    }
    if ($wh2 != '') {
      $whid2 = $config['params']['dataparams']['whid2'];
      $filter1 .= " and head.client='" . $wh2 . "'";
      $filter2 .= " and head.clientid=" . $whid2;
    }

    $query = "select barcode, itemname, sum(qty) as qty, uom, avg(cost) as cost, sum(total) as total, fromwh, towh from (
      select item.barcode, item.itemname, stock.isqty as qty, item.uom, stock.cost, stock.ext as total,
      wh.clientname as fromwh, wh2.clientname as towh
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join client as wh on wh.client=head.wh
      left join client as wh2 on wh2.client=head.client
      where head.doc='ST' and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . $filter1 . "
      
      UNION ALL

      select item.barcode, item.itemname, stock.isqty as qty, item.uom, stock.cost, stock.ext as total,
      wh.clientname as fromwh, wh2.clientname as towh
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join client as wh on wh.clientid=head.whid
      left join client as wh2 on wh2.clientid=head.clientid
      where head.doc='ST' and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . $filter2 . "
    ) as t where barcode is not null group by barcode, itemname, uom, fromwh, towh";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $whname = $config['params']['dataparams']['whname'];
    $wh2name = $config['params']['dataparams']['wh2name'];
    $start = date('F j, Y', strtotime($config['params']['dataparams']['start']));
    $end = date('F j, Y', strtotime($config['params']['dataparams']['end']));
    $deptname = $config['params']['dataparams']['deptname'];

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
    $str .= $this->reporter->col('STOCK TRANSFER SUMMARY PER ITEM', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . ' - ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department: ' . ($deptname == '' ? 'ALL' : $deptname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From: ' . ($whname == '' ? 'ALL' : $whname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('To: ' . ($wh2name == '' ? 'ALL' : $wh2name), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Itemname', '450', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Qty', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Unit', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Avg Cost', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $result = $this->reportDefault($config);
    $count = 18;
    $page = 17;
    $this->reporter->linecounter = 0;
    if (empty($result)) return $this->othersClass->emptydata($config);

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
    $amt = null;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->itemname, '450', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->cost, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->total, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->endrow();
      $amt = $amt + $data->total;

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
    } // end for loop
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total:', '900', null, false, $border, '', 'R', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class