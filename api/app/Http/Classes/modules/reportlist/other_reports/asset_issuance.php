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

class asset_issuance
{
  public $modulename = 'Asset Issuance';
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

    $fields = [
      'radioprint', 'start', 'end', 'clientname', 'ddeptname', 'ditemname', 'divsion', 'brandname',
      'model', 'class', 'radioreporttype'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'clientname.type', 'lookup');
    data_set($col1, 'clientname.lookupclass', 'employee');
    data_set($col1, 'clientname.action', 'lookupclient');
    data_set($col1, 'clientname.label', 'Employee');
    data_set($col1, 'clientname.name', 'empname');
    data_set($col1, 'ddeptname.label', 'Department');
    data_set($col1, 'ditemname.lookupclass', 'lookupitemfi');
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'orange'],
      ['label' => 'No Return', 'value' => '1', 'color' => 'orange']
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 'default' as print,adddate(left(now(),10), -365) as start,
                  adddate(left(now(),10), 0) as end,'' as empcode,'' as empname,'0' as empid,
                  '' as ddeptname,'' as dept,'' as deptname,'' as deptid,'' as ditemname,
                  '' as divsion,'' as brandname,'' as model,'' as class,'0' as itemid,
                  '' as itemname,'' as barcode,'' as groupid,'' as stockgrp,'' as brandid,
                  '' as brandname,'' as classid,'' as classic,'' as modelid,'' as modelname,
                  '0' as reporttype
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
    $empname     = $config['params']['dataparams']['empname'];
    $empid     = $config['params']['dataparams']['empid'];
    $deptname     = $config['params']['dataparams']['ddeptname'];
    $dept     = $config['params']['dataparams']['deptid'];
    $date1 = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $date2 = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $reporttype  = $config['params']['dataparams']['reporttype'];
    $filter   = "";

    if ($empname != '') {
      $filter .= " and issue.clientid = '$empid'";
    }
    if ($deptname != '') {
      $filter .= "  and issue.locid = '$dept'";
    }
    if ($itemname != '') {
      $filter .= " and item.itemid = '$itemid'";
    }
    if ($brandname != '') {
      $filter .= " and item.brand = '$brandid'";
    }
    if ($modelid != '') {
      $filter .= " and item.model = '$modelid'";
    }
    if ($groupid != '') {
      $filter .= " and item.groupid = '$groupid'";
    }
    if ($classid != '') {
      $filter .= " and item.class = '$classid'";
    }

    if ($reporttype == 1) {
      $filter .= " and iss.returndate is null ";
    }

    $query = "select  issue.docno,item.barcode,item.itemname,
              iteminfo.serialno,date(issue.dateid) as datetransfer, 
      emp.clientname as empname,loc.clientname as locname,iss.rem
      from issueitem as issue
      left join issueitemstock as iss on iss.trno=issue.trno
      left join transnum as num on num.trno=issue.trno
      left join client as loc on loc.clientid = issue.locid
      left join client as emp on emp.clientid = issue.clientid
      left join item as item on item.itemid=iss.itemid
      left join iteminfo as iteminfo  on iteminfo.itemid=item.itemid 
      where item.isfa = 1 and item.isinactive = 0 and date_format(issue.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
      and date_format('$date2','%Y-%m-%d')
      " . $filter . " ";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $start   = date("M d, Y", strtotime($config['params']['dataparams']['start']));
    $end     = date("M d, Y", strtotime($config['params']['dataparams']['end']));
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
    $layoutsize = 1400;

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ASSET ISSUANCE', 1000, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("FROM $start TO $end", 1000, null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', 120, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ITEM TAG', 200, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESCRIPTION', 250, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SERIAL NO', 130, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE TRANSFERED', 100, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE', 200, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('LOCATION', 200, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', 200, null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
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
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);
    $totalext = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, 120, null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->barcode, 200, null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, 250, null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->serialno, 130, null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->datetransfer, 100, null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, 200, null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->locname, 200, null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->rem, 200, null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    } //end foreach
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class