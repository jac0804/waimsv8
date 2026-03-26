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

class physical_inventory_sheet
{
  public $modulename = 'Physical Inventory Sheet';
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
    $fields = ['radioprint', 'part', 'class', 'divsion', 'category'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'part.label', 'Principal');
    data_set($col1, 'divsion.label', 'Division');
    data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'category.name', 'categoryname');

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'part.name', 'partname');

    $fields = ['radioreportitemstatus'];
    $col2 = $this->fieldClass->create($fields);
    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      0 as partid,
      '' as part,
      '' as partname,
      '' as class,
      0 as classid,
      '' as classic,
      0 as groupid,
      '' as stockgrp,
      '' as divsion,
      '' as category,
      '' as categoryname,
      '(0,1)' as itemstatus  
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
    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY

    $query = $this->default_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {

    $partid    = $config['params']['dataparams']['partid'];
    $partname  = $config['params']['dataparams']['partname'];
    $classid     = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $groupid  = $config['params']['dataparams']['groupid'];
    $stockgrp  = $config['params']['dataparams']['stockgrp'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $itemstat  = $config['params']['dataparams']['itemstatus'];

    $filter = "";
    if ($partname != "") {
      $filter .= " and i.part = $partid";
    }
    if ($classname != "") {
      $filter .= " and i.class = $classid";
    }
    if ($stockgrp != "") {
      $filter .= " and i.groupid = $groupid";
    }
    if ($categoryname != "") {
      $filter .= " and i.category = '$category' ";
    }
    if ($itemstat != "") {
      $filter .= " and i.isinactive in $itemstat";
    }

    $query = "select i.barcode,i.itemname,i.uom,i.isinactive
            from item as i
            where 1=1 $filter
            order by i.barcode";

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $principal     = $config['params']['dataparams']['partname'];
    $class     = $config['params']['dataparams']['classid'];
    $division     = $config['params']['dataparams']['stockgrp'];
    $category     = $config['params']['dataparams']['categoryname'];
    $itemstat     = $config['params']['dataparams']['itemstatus'];

    if ($principal != "") {
      $principal = $config['params']['dataparams']['partname'];
    } else {
      $principal = "ALL";
    }
    if ($class != "") {
      $class = $config['params']['dataparams']['classic'];
    } else {
      $class = "ALL";
    }
    if ($division != "") {
      $division = $config['params']['dataparams']['stockgrp'];
    } else {
      $division = "ALL";
    }
    if ($category != "") {
      $category = $config['params']['dataparams']['categoryname'];
    } else {
      $category = "ALL";
    }

    switch ($itemstat) {
      case '(0)':
        $itemstat = 'INACTIVE';
        break;
      case '(1)':
        $itemstat = 'ACTIVE';
        break;
      default:
        $itemstat = 'BOTH';
        break;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Principal: ' . $principal, '600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Status: ' . $itemstat, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Division: ' . $division, '600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category: ' . $category, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('D E S C R I P T I O N', '300', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '150', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '20', null, false, $border, 'BT', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('QTY', '130', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '20', null, false, $border, 'BT', 'C', $font, $fontsize - 1, '', '', '');
    $str .= $this->reporter->col('REMARKS', '130', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];


    // $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    // $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    // $count = 38;
    // $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $border = "1px solid ";
    $fontsize = "11";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'L', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, '', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, '', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $fontsize - 1, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class