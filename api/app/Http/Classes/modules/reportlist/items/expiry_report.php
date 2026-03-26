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

class expiry_report
{
  public $modulename = 'Expiry Report';
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

    $fields = ['radioprint', 'start', 'end', 'part', 'dwhname', 'divsion'];
    if ($companyid == 14) array_push($fields, 'radioarrangeby'); //majesty
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divsion.label', 'Division');

    if ($companyid == 14) { //majesty
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'part.label', 'Principal');
    }

    unset($col1['part']['labeldata']);
    unset($col1['divsion']['labeldata']);
    unset($col1['labeldata']['part']);
    unset($col1['labeldata']['divsion']);
    data_set($col1, 'part.name', 'partname');
    data_set($col1, 'divsion.name', 'stockgrp');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      left(adddate(now(),-30),10) as start,
      left(now(),10) as end,
      0 as groupid,
      '' as stockgrp,
      0 as partid,
      '' as part,
      '' as partname,
      '' as divsion,
      0 as arrangeby
    ";

    if ($companyid == 14) { //majesty
      $center = $config['params']['center'];
      $whid = '';
      $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
      $whname = '';
      $dwhname = '';
      if ($wh != '') {
        $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);
        $whname = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$wh]);
        $dwhname = $wh . '~' . $whname;
      }
    }

    $paramstr .= ", '" . $wh . "' as wh, '" . $whid . "' as whid, '" . $whname . "' as whname, '" . $dwhname . "' as dwhname";
    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function default_query($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $partname  = $config['params']['dataparams']['partname'];
    $stockgrp    = $config['params']['dataparams']['stockgrp'];
    $wh  = $config['params']['dataparams']['wh'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    if ($partname != "") {
      $partid  = $config['params']['dataparams']['partid'];
      $filter .= $filter . " and item.part=" . $partid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and rrstatus.whid=" . $whid;
    }

    $order = 'item.itemname';
    if ($companyid == 14) { //majesty
      if ($config['params']['dataparams']['arrangeby'] == 0) {
        $order = 'item.itemname';
      } else {
        $order = 'rrstatus.expiry';
      }
    }

    $query = "select rrstatus.expiry, item.barcode, item.itemname,
      item.uom as uom, rrstatus.loc,
      sum(rrstatus.bal / case when item.uom = '' then 1 else uom.factor end) as qty from rrstatus
      left join item on rrstatus.itemid=item.itemid
      left join uom on uom.uom = item.uom and uom.itemid = item.itemid
      where rrstatus.bal <> 0
      and date(rrstatus.expiry) between '" . $start . "' and '" . $end . "' $filter 
      group by rrstatus.itemid, rrstatus.loc, rrstatus.expiry, item.barcode, item.itemname,
      item.uom
      order by " . $order;

    return $query;
  }

  public function reportDefault($config)
  {
    $query = $this->default_query($config);
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $data = $this->reportDefault($config);
    $result = $this->reportDefaultLayout($config, $data);
    return $result;
  }

  public function reportDefaultLayout($config, $data2)
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

    $str = "";
    $count = 38;
    $page = 40;

    $str .= $this->reporter->beginreport('800');
    $str .= $this->report_header($config, $data2);
    $totalitems = 0;

    foreach ($data2 as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->barcode, '125', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '275', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, '1px solid', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, '1px solid', '', 'R', $font, $font_size, '', '', '');

      $totalitems = $totalitems + $data->qty;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        //
        $str .= $this->reporter->page_break();
        $str .= $this->report_header($config, $data2);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    if (!empty($data2)) {
      $c = count($data2);
    } else {
      $c = 0;
    }

    $str .= $this->reporter->col('NO. OF ITEMS : ' . $c, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalitems, 2), '100', null, false, '1px solid ', '', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_header($config, $data2)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $partname  = $config['params']['dataparams']['partname'];
    $part    = $config['params']['dataparams']['part'];
    $whname    = $config['params']['dataparams']['whname'];
    $divsion     = $config['params']['dataparams']['divsion'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = "";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->startrow();
    $str .= '<br/>';
    $str .= $this->reporter->col('EXPIRY REPORT', null, null, false, '1px solid', '', 'C', $font, '18', 'B', '', '') . '<br/>';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    if ($start == "" and $end == "") {
      $str .= $this->reporter->col('EXPIRY : --', '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('EXPIRY ' . $start . '-' . $end, '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($part == "") {
      $str .= $this->reporter->col('PRINCIPAL : ALL', '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('PRINCIPAL :' . $partname, '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->col('WAREHOUSE : ' . $whname, '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($divsion == "") {
      $str .= $this->reporter->col('DIVISION : ALL', '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('DIVISION :' . $divsion, '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->col('', '350', null, false, '1px solid', '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('LOCATION', '100', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('BARCODE', '125', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '275', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('UNIT', '100', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('QTY', '100', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    return $str;
  }
}//end class