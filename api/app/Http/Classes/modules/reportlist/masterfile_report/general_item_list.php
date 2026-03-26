<?php

namespace App\Http\Classes\modules\reportlist\masterfile_report;

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

class general_item_list
{
  public $modulename = 'General Item List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '900'];

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

    $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'model', 'class', 'sizeid', 'company'];
    $col1 = $this->fieldClass->create($fields);
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
    $filter   = "";

    if ($itemname != '') {
      $filter .= " and genitem.line = '$itemid'";
    }
    if ($brandname != '') {
      $filter .= " and genitem.brandid = '$brandid'";
    }
    if ($modelid != '') {
      $filter .= " and genitem.modelid = '$modelid'";
    }
    if ($groupid != '') {
      $filter .= " and genitem.groupid = '$groupid'";
    }
    if ($classid != '') {
      $filter .= " and genitem.classid = '$classid'";
    }
    if ($sizeid != '') {
      $filter .= " and genitem.sizeid = '$sizeid'";
    }
    if ($company != '') {
      $filter .= " and genitem.company = '$company'";
    }

    $query = "
     select genitem.line, genitem.barcode, genitem.uom, genitem.itemname, genitem.groupid, '' as subgroup, 
    genitem.model as modelid, genitem.class as classid, '' as company, genitem.type, genitem.brand as brandid, genitem.sizeid, 
    brand.brand_desc as brandname, groups.stockgrp_name as groupname, model.model_name as modelname,
    classi.cl_name as classname
    from item as genitem
    left join frontend_ebrands as brand on brand.brandid = genitem.brand
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join model_masterfile as model on model.model_id = genitem.model
    left join item_class as classi on classi.cl_id = genitem.class
    where genitem.isgeneric=1  " . $filter . "
  ";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
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
    $str .= $this->reporter->col('GENERAL ITEM LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '200', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('MODEL', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('GROUP', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('COMPANY', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SIZE', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CLASSIFICATION', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
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


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->brandname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->modelname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->groupname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->company, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sizeid, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->classname, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
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