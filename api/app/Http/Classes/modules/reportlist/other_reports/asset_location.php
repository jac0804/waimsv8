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

class asset_location
{
  public $modulename = 'Asset Location';
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

    $fields = ['radioprint', 'dwhname', 'clientname', 'ditemname', 'divsion', 'brandname', 'model', 'class', 'sizeid', 'company'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'clientname.type', 'lookup');
    data_set($col1, 'clientname.lookupclass', 'employee');
    data_set($col1, 'clientname.action', 'lookupclient');
    data_set($col1, 'clientname.label', 'Employee');
    data_set($col1, 'clientname.name', 'empname');

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
    '' as dwhname,
    '' as wh,
    '' as whname,
    '0' as whid,
    '' as dclientname,
    '' as empcode,
    '' as empname,
    '0' as empid,
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
    '' as company");
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
    $whname     = $config['params']['dataparams']['whname'];
    $whid     = $config['params']['dataparams']['whid'];
    $empname     = $config['params']['dataparams']['empname'];
    $empid     = $config['params']['dataparams']['empid'];

    $filter   = "";
    if ($empname != '') {
      $filter .= " and cl.clientid = '$empid'";
    }
    if ($whname != '') {
      $filter .= " and wh.clientid = '$whid'"; 
    }
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
    select item.barcode, item.itemname, item.sizeid, 
    brand.brand_desc as brandname, groups.stockgrp_name as groupname, model.model_name as modelname,
    item.subcode, iteminfo.serialno, iteminfo.plateno,
    iteminfo.depreyrs, item.depre, item.saleprice, left(iteminfo.podate, 10) as podate,
    cl.clientid, cl.client, ifnull(cl.clientname, 'NO EMPLOYEE') as clientname, 
    ifnull(wh.clientname, 'NO LOCATION') as locname,
    left(iteminfo.issuedate, 10) as receiveddate
    from item as item
    left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
    left join client as cl on cl.clientid = iteminfo.empid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join client as wh on wh.clientid = iteminfo.locid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = item.groupid
    left join model_masterfile as model on model.model_id = item.model
    where item.isfa = 1 " . $filter . "
    order by cl.clientname ";
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
    $str .= $this->reporter->col('ASSET LOCATION', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->col('DATE RECEIVED', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
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

    $clientname = "";
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($clientname != $data->clientname) {

        if ($clientname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->locname, '200', null, false, $border, $border_line, '', $font, 14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '200', null, false, $border, $border_line, '', $font, 12, '', '', '');
        $str .= $this->reporter->endrow();
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->subcode, '200', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->brandname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->modelname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->groupname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->serialno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->plateno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->receiveddate, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $clientname = $data->clientname;
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
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class