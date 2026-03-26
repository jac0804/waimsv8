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


class inventory_aging_per_site
{
  public $modulename = 'Inventory Aging per Site';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '250'];
  public $itemdata = [];

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

    $fields = ['radioprint', 'month', 'byear', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dwhname.required', true);

    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');

    data_set($col1, 'byear.readonly', false);
    data_set($col1, 'byear.name', 'byear');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
    'default' as print,
    date_format(now(), '%M') as month,
            month(now()) as bmonth,
            year(now()) as byear,
    '' as wh,
    '' as whname,
    '' as dwhname ";
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

    $result = $this->reportDefaultLayout($config);

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
    $companyid = $config['params']['companyid'];
    $mnth = $config['params']['dataparams']['bmonth'];
    $byear = $config['params']['dataparams']['byear'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];

    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['byear'] . '-' . $config['params']['dataparams']['bmonth'] . '-1'));

    $filter = "";
    $filter1 = "";

    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }

    $query = "select ib.itemid,barcode, itemname,ib.uom,
    sum(qty-iss) as balance,dateid
    from (
    select '" . $start . "' as dateid,item.itemid,item.itemname,item.barcode,item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    where head.dateid < '$start' and ifnull(item.barcode,'')<>'' $filter
    union all
    select '" . $start . "' as dateid,item.itemid,item.itemname,item.barcode,item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    where head.dateid < '$start' and ifnull(item.barcode,'')<>'' $filter
    union all
    select head.dateid,item.itemid,item.itemname,item.barcode,item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    where month(head.dateid) = $mnth and year(head.dateid) = $byear and ifnull(item.barcode,'')<>'' $filter
    union all
    select head.dateid,item.itemid,item.itemname,item.barcode,item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    where month(head.dateid) = $mnth and year(head.dateid) = $byear and ifnull(item.barcode,'')<>'' $filter
    ) as ib
    group by ib.itemid,barcode, itemname, ib.uom,ib.dateid order by itemid,dateid";
    return $query;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $byear = $config['params']['dataparams']['byear'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];

    if ($whname == "") {
      $whname = "ALL";
    }

    $str = '';
    $layoutsize = '250';

    $mo = $config['params']['dataparams']['bmonth'];
    $yr = $config['params']['dataparams']['byear'];

    $daysinmo = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);

    $layoutsize = $layoutsize + ($daysinmo * 75);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('INVENTORY AGING PER SITE', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the month of :' . date('F', strtotime($config['params']['dataparams']['byear'] . '-' . $config['params']['dataparams']['bmonth'] . '-1')) . ', ' . $byear, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Location : ' . $wh, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $mo = $config['params']['dataparams']['bmonth'];
    $yr = $config['params']['dataparams']['byear'];

    $daysinmo = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);

    $layoutsize = $layoutsize + ($daysinmo * 75);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();



    $str .= $this->reporter->col('Item Description', '250', null, false, $border, 'B', 'L', $font, '12', 'B', '', '');

    for ($i = 1; $i <= $daysinmo; $i++) {
      $str .= $this->reporter->col($mo . '/' . $i, '75', null, false, $border, 'B', 'R', $font, '12', 'B', '', '');
    }
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->default_displayHeader($config);

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Detailed', null, null, false, $border, '', 'L', $font, '12', 'B', '', '5px');
    $str .= $this->reporter->endtable();

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $mo = $config['params']['dataparams']['bmonth'];
    $yr = $config['params']['dataparams']['byear'];

    $daysinmo = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);
    $arrdays = [];

    for ($i = 1; $i <= $daysinmo; $i++) {
      array_push($arrdays, $i);
    }

    $itemname = "";
    $barcode = '';
    $bal = 0;
    $m = 0;
    $item = [];
    $this->itemdata = [];

    foreach ($result as $key => $data) {
      if ($itemname != $data->itemname) {
        if ($itemname != "" && $key != 0) {
          for ($y = $m; $y <= count($arrdays) - 1; $y++) {
            if ($y != count($arrdays)) {
              $item['barcode'] = $barcode;
              $item['bal'] = $bal;
              $item['itemname'] = $itemname;
              $str .= $this->reporter->col(number_format($bal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
              if ($bal <> 0) {
                array_push($this->itemdata, $item);
              }
            }
          }
          $m = 0;
          $str .= $this->reporter->endrow();
        }
        $bal = 0;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');

        for ($y = 0; $y <= count($arrdays) - 1; $y++) {
          if ($arrdays[$y] == date('j', strtotime($data->dateid))) {
            $bal = $bal + $data->balance;
            $item['barcode'] = $data->barcode;
            $item['bal'] = $bal;
            $item['itemname'] = $data->itemname;
            $str .= $this->reporter->col(number_format($bal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
            if ($bal <> 0) {
              array_push($this->itemdata, $item);
            }
            $m = $y + 1;
            goto next;
          } else {
            $item['barcode'] = $data->barcode;
            $item['bal'] = $bal;
            $item['itemname'] = $data->itemname;
            if ($bal <> 0) {
              array_push($this->itemdata, $item);
            }
            $str .= $this->reporter->col(number_format($bal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
            $m = $y + 1;
          }
        }
      } else {
        for ($y = $m; $y <= count($arrdays) - 1; $y++) {
          if ($arrdays[$y] == floatval(date('j', strtotime($data->dateid)))) {
            $bal = $bal + $data->balance;
            $item['barcode'] = $data->barcode;
            $item['bal'] = $bal;
            $item['itemname'] = $data->itemname;
            $str .= $this->reporter->col(number_format($bal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
            if ($bal <> 0) {
              array_push($this->itemdata, $item);
            }
            $m = $y + 1;
            goto next;
          } else {
            $item['barcode'] = $data->barcode;
            $item['bal'] = $bal;
            $item['itemname'] = $data->itemname;
            if ($bal <> 0) {
              array_push($this->itemdata, $item);
            }
            $str .= $this->reporter->col(number_format($bal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
            $m = $y + 1;
          }
        }
      }
      next:
      $itemname = $data->itemname;
      $barcode = $data->barcode;
    }

    //for last item row
    //display remaining days bal
    for ($y = $m; $y <= count($arrdays) - 1; $y++) {
      if ($y != count($arrdays)) {
        $item['barcode'] = $barcode;
        $item['bal'] = $bal;
        $item['itemname'] = $itemname;
        $str .= $this->reporter->col(number_format($bal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        if ($bal <> 0) {
          array_push($this->itemdata, $item);
        }
      }
    }
    $m = 0;
    $str .= $this->reporter->endrow();

    $qty = 0;
    $counter = 0;

    $hash = array();
    $array_out = array();

    foreach ($this->itemdata as $item => $v) {
      $hash_key = $v['barcode'] . '|' . $v['bal'];
      if (!array_key_exists($hash_key, $hash)) {
        $hash[$hash_key] = sizeof($array_out);
        array_push($array_out, array(
          'barcode' => $v['barcode'],
          'bal' => $v['bal'],
          'itemname' => $v['itemname'],
          'count' => 0,
        ));
      }
      $array_out[$hash[$hash_key]]['count'] += 1;
    }


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('475');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, '50', false, $border, '', 'L', $font, '12', 'B', '', '5px');


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Summary', null, null, false, $border, '', 'L', $font, '12', 'B', '', '5px');

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('475');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '75', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col('Item Description', '250', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col('Balance', '75', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col('No. Of Days', '75', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '5px');
    // function col($txt = '', $w = null, $h = null, $bg = false,  $b = false, $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $isamount = 0, $colspan = 0)
    $summary_barcode = '';
    $summary_itemname = '';
    $summary_bal = 0;
    $summary_count = 0;
    $is_more_than_one = 0;
    foreach ($array_out as $key => $x) {

      if ($summary_itemname != '' && $summary_itemname == $x['itemname']) {
        $summary_bal = $x['bal'];
        $summary_count = $x['count'];
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_bal, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_count, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $is_more_than_one = 1;
      }


      if ($summary_itemname == '') {
        $summary_barcode = $x['barcode'];
        $summary_itemname = $x['itemname'];
        $summary_bal = $x['bal'];
        $summary_count = $x['count'];


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($summary_barcode, '75', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_bal, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_count, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      }

      if ($summary_itemname != '' && $summary_itemname != $x['itemname']) {
        $summary_barcode = $x['barcode'];
        $summary_itemname = $x['itemname'];
        $summary_bal = $x['bal'];
        $summary_count = $x['count'];
        if ($is_more_than_one) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('', '75', '20', false, $border, '', 'L', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col('', '250', '20', false, $border, '', 'L', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col('', '75', '20', false, $border, '', 'R', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col('', '75', '20', false, $border, '', 'R', $font, $font_size, '', '', '5px');
          $is_more_than_one = 0;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($summary_barcode, '75', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_bal, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($summary_count, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      }

      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $font_size, 'B', '', '5px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $font_size, 'B', '', '5px');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class