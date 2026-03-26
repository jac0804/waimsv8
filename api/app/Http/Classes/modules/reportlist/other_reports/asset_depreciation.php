<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class asset_depreciation
{
  public $modulename = 'Asset Depreciation';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];

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

    $fields = ['radioprint', 'start', 'ditemname', 'divsion', 'brandname', 'model', 'class', 'sizeid', 'company'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'As of Date');
    data_set($col1, 'ditemname.lookupclass', 'genitemlist');

    data_set($col1, 'company.action', 'lookupcompany_fams');
    data_set($col1, 'company.lookupclass', 'lookupcompany_fams');
    data_set($col1, 'company.type', 'lookup');
    data_set($col1, 'company.readonly', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10), 0) as start,
    '' as ditemname,
    '' as divsion,
    '' as brandname,
    '' as model,
    '' as class,
    '0' as itemid,
    '' as itemname,
    '' as barcode,
    '' as groupid,
    '' as stockgrp,
    '' as brandid,
    '' as brandname,
    '' as classid,
    '' as classic,
    '' as modelid,
    '' as modelname,
    '' as sizeid,
    '' as company
    ");
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY

    $itemid     = $config['params']['dataparams']['itemid'];
    $itemname     = $config['params']['dataparams']['itemname'];
    $brandid     = $config['params']['dataparams']['brandid'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $modelid     = $config['params']['dataparams']['modelid'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $classid     = $config['params']['dataparams']['classid'];
    $sizeid     = $config['params']['dataparams']['sizeid'];
    $company     = $config['params']['dataparams']['company'];
    $asof     = $config['params']['dataparams']['start'];

    $filter   = "";

    if ($itemname != '') {
      $filter .= " and item.itemid = '$itemid'";
    }
    if ($brandname != '') {
      $filter .= " and item.brandid = '$brandid'";
    }
    if ($modelid != '') {
      $filter .= " and item.model = '$modelid'";
    }
    if ($groupid != '') {
      $filter .= " and item.groupid = '$groupid'";
    }
    if ($classid != '') {
      $filter .= " and item.classid = '$classid'";
    }
    if ($sizeid != '') {
      $filter .= " and item.sizeid = '$sizeid'";
    }
    if ($company != '') {
      $filter .= " and iteminfo.company = '$company'";
    }

    $query = "
    select item.itemid, item.barcode, item.itemname, item.groupid,
    item.model, item.class, item.brand, item.sizeid, 
    brand.brand_desc as brandname, groups.stockgrp_name as groupname, model.model_name as modelname,
    classi.cl_name as classname, item.subcode, iteminfo.serialno, iteminfo.plateno,
    iteminfo.depreyrs, item.depre, item.saleprice, left(iteminfo.podate, 10) as podate
    from item as item
    left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join stockgrp_masterfile as groups on groups.stockgrp_id = item.groupid
    left join model_masterfile as model on model.model_id = item.model
    left join item_class as classi on classi.cl_id = item.class
    where item.isfa = 1 and item.depre <> 0 " . $filter . "
  ";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ASSET DEPRECIATION', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM TAG', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('MODEL', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('GROUP', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SERIAL #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PLATE #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SALVAGE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('YEARS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('RUNNING BAL.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $asof     = $config['params']['dataparams']['start'];
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $this->reporter->linecounter = 0;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);
    $totalext = 0;


    foreach ($result as $key => $data) {
      $date1 = $data->podate;
      $date2 = $config['params']['dataparams']['start'];

      $ts1 = strtotime($date1);
      $ts2 = strtotime($date2);

      $year1 = date('Y', $ts1);
      $year2 = date('Y', $ts2);

      $month1 = date('m', $ts1);
      $month2 = date('m', $ts2);

      $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

      $grand_total = 0;
      if ($diff > 0) {
        $amt = $data->depre;
        $salvage = $data->saleprice;
        $depreyrs = $diff;
        $sub_total = $amt - $salvage;
        $grand_total = $sub_total / $depreyrs;
      }


      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->subcode, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->brandname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->modelname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->groupname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->serialno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->plateno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->depre, $decimal_currency), '100', null, false, $border, $border_line, 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->saleprice, $decimal_currency), '100', null, false, $border, $border_line, 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->depreyrs, '100', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($grand_total, $decimal_currency), '100', null, false, $border, $border_line, 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $totalext += $grand_total;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('TOTAL:', '100', null, false, $border, $border_line, 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal_currency), '100', null, false, $border, $border_line, 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class