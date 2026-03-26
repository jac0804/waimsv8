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

class physical_count_sheet_per_batch_report
{
  public $modulename = 'Physical Count Sheet Per Batch Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1000px;max-width:1000px;';
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

    $fields = ['radioprint', 'sizeid', 'part', 'divsion', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'part.label', 'Principal');
    data_set($col1, 'divsion.label', 'Division');
    data_set($col1, 'sizeid.label', 'Bin');

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
    // NAME NG INPUT YUNG NAKA ALIAS
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

    return $this->coreFunctions->opentable("select 
    'default' as print,
    
    '' as sizeid,

    '' as part,
    '' as partname,
    0 as partid,

    '' as divsion,
    0 as groupid,
    '' as stockgrp,

    '" . $wh . "' as wh, '" . $whid . "' as whid, '" . $whname . "' as whname, '" . $dwhname . "' as dwhname
    ");
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

  public function reportDefault($config)
  {
    $query = $this->default_query($config);
    $data = $this->coreFunctions->opentable($query);
    return json_decode(json_encode($data), true);
  }

  public function reportplotting($config, $result)
  {
    $result = $this->reportDefaultLayout($config, $result);
    return $result;
  }

  public function default_query($config)
  {
    $partname   = $config['params']['dataparams']['partname'];
    $divisionname   = $config['params']['dataparams']['stockgrp'];
    $bin   = $config['params']['dataparams']['sizeid'];
    $wh     = $config['params']['dataparams']['whid'];

    $filter = "";
    $filterwh = '';
    if ($partname != "") {
      $partid     = $config['params']['dataparams']['partid'];
      $filter .= " and i.part=" . $partid;
    }
    if ($divisionname != "") {
      $divisionid     = $config['params']['dataparams']['groupid'];
      $filter .= " and i.groupid = " . $divisionid;
    }
    if ($bin != "") {
      $filter .= " and i.sizeid='" . $bin . "' ";
    }
    if ($wh != '') {
      $whid = $config['params']['dataparams']['whid'];
      $filterwh = " and rr.whid=" . $whid;
    }

    $query = "select i.barcode,i.itemname,u.uom,i.sizeid as bin,rrbal.loc,rrbal.expiry,ifnull(rrbal.balance,0) as bal, wh.clientname as whname
    from item as i
    left join uom as u on u.itemid=i.itemid and u.uom=i.uom
    left join (
    select rr.itemid,rr.loc,rr.expiry,sum(rr.bal) as balance, rr.whid from rrstatus as rr where rr.bal<>0 " . $filterwh . "
    group by itemid,loc,expiry,whid
    ) as rrbal on rrbal.itemid=i.itemid
    left join client as wh on wh.clientid=rrbal.whid
    where rrbal.balance>0 " . $filter;

    return $query;
  }

  private function report_default_header($params, $data)
  {
    $str = '';
    $font_size = '10';

    $center     = $params['params']['center'];
    $username   = $params['params']['user'];
    $font = $this->companysetup->getrptfont($params['params']);
    $partname   = $params['params']['dataparams']['partname'];
    $divisionname   = $params['params']['dataparams']['stockgrp'];

    $bin   = $params['params']['dataparams']['sizeid'];
    $whname   = $params['params']['dataparams']['whname'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Physical Count Sheet Per Batch', null, null, false, '1px solid', '', 'C', $font, '18', 'B', '', '') . '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRINCIPAL : ' . ($partname != '' ? $partname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('PRICE TYPE : ' . ($divisionname != '' ? $divisionname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BIN : ' . ($bin != '' ? $bin : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('WAREHOUSE: ' . ($whname != '' ? $whname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '280', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('BIN', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('LOCATION', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('EXPIRY', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('QTY', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('REMARKS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function reportDefaultLayout($params, $data)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $border = '1px solid';
    $font_size = 11;
    $fontsize12 = 12;
    $layoutsize = $this->reportParams['layoutSize'];

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->report_default_header($params, $data);
    $str .= $this->default_table_cols($layoutsize, $border, $font, $fontsize12);

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '280', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['bin'], '50', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['loc'], '60', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['expiry'], '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  } // end fn
}//end class