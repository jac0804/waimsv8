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


class current_inventory_aging
{
  public $modulename = 'Current Inventory Aging';
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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'dwhname', 'categoryname', 'subcatname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);

    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'project.label', 'Item Group/Project');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['radioreportitemtype'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr =
      "select 'default' as print,
    0 as itemid,
    '' as itemname,
    '' as barcode,
    0 as groupid,
    '' as stockgrp,
    0 as brandid,
    '' as brandname,
    '' as brand,
    0 as classid,
    '' as classic,
    '' as categoryname,
    0 as whid,
    '' as wh,
    '' as whname,
    '(0,1)' as itemtype,
    '' as ditemname,
    '' as divsion,
    '' as class,
    '' as category,
    '' as subcatname,
    '' as subcat,
    '' as dwhname,
    '' as project, 
    0 as projectid, 
    '' as projectname";

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

    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_Layout($config);
        break;

      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    // QUERY
    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY($config)
  {
    $companyid    = $config['params']['companyid'];
    $itemtype     = $config['params']['dataparams']['itemtype'];
    $barcode      = $config['params']['dataparams']['barcode'];
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $wh           = $config['params']['dataparams']['wh'];
    $classname    = $config['params']['dataparams']['classic'];

    $filter = "";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and rrstatus.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category = '$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat = '$subcat'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and rrstatus.whid=" . $whid;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and item.projectid=" . $projectid;
      }
    }

    $loc = $itemtype;
    $query = "select item.sizeid as size, mm.model_name as model,item.barcode, item.itemname, item. model, item.brand, 
            frontend_ebrands.brand_desc, mm.model_name, 
            (case cntnum.doc when 'TS' then date(rrstatus.receiveddate) else date(rrstatus.dateid) end) as dateid, rrstatus.docno, 
            rrstatus.qty, rrstatus.bal, item.uom, (rrstatus.qty-rrstatus.bal) as sold, datediff(now(),rrstatus.receiveddate) as elapse 
            from rrstatus 
            left join item on item.itemid=rrstatus.itemid 
            left join model_masterfile as mm on mm.model_id = item.model
            left join frontend_ebrands on frontend_ebrands.brandid = item.brand
            left join cntnum on cntnum.trno=rrstatus.trno 
            where item.isinactive=0 and rrstatus.bal>0 and item.isimport in $loc $filter $filter1 and item.isofficesupplies=0
            order by item.barcode, item.itemname, rrstatus.dateid, rrstatus.docno";

    return $query;
  }

  private function MAJESTY_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($barcode == "") {
      $barcode = "ALL";
    }
    if ($groupname == "") {
      $groupname = "ALL";
    }
    if ($brandname == "") {
      $brandname = "ALL";
    }
    if ($classname == "") {
      $classname = "ALL";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'All';
    }

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

    $str .= $this->reporter->col('CURRENT INVENTORY AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('WH : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow('100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $wh, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function MAJESTY_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    // $companyid = $config['params']['companyid'];
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ELAPSE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PURCH.QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('REMAIN QTY', '75', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SOLD QTY', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('%AGE', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function MAJESTY_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;

    $result = $this->reportDefault($config);

    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];

    $count = 18;
    $page = 17;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);
    $str .= $this->MAJESTY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $amt = null;
    $itemname = "";
    $subtotal = 0;
    $ordtotal = 0;
    $remtotal = 0;
    // $cus = "";

    foreach ($result as $key => $data) {
      $display = $data->itemname;

      if ($data->brand != "") {
        $display = $data->brand_desc . " " . $display;
      } //end if

      if ($data->model != "") {
        $display = $display . " " . $data->model_name;
      } //end if

      if ($data->size != "") {
        $display = $display . " " . $data->size;
      } //end if

      $date = $data->dateid;
      $uom = $data->uom;
      $order = $data->qty;
      $served = $data->sold;
      $remain = $data->bal;
      $docno = $data->docno;
      $age = ($served / $order) * 100;
      $barcode = $data->barcode;
      $tage = number_format($age);
      if ($tage == 0) {
        $tage = '-';
      }
      $torder = number_format($order, 2);
      if ($torder == 0) {
        $torder = '-';
      }
      $sserved = number_format($served, 2);
      if ($sserved == 0) {
        $sserved = '-';
      }
      $tremain = number_format($remain, 2);
      if ($tremain == 0) {
        $tremain = '-';
      }
      $tremtotal = number_format($remtotal, 2);
      if ($tremtotal == 0) {
        $tremtotal = '-';
      }

      if ($itemname != $data->itemname) {
        if ($itemname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
          $str .= $this->reporter->col(number_format($ordtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
          $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'C', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->col(number_format($remtotal, 2), '75', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
          $str .= $this->reporter->col(number_format($subtotal, 2), '75', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '5px');
          $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'L', $font, $font_size, '', '', '5px');
          $str .= $this->reporter->endrow();

          if ($this->reporter->linecounter >= $page) {
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $page = $page + $count;
          }
        }
        $subtotal = 0;
        $ordtotal = 0;
        $remtotal = 0;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($display, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($data->elapse . 'Day(s)', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($torder, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($tremain, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($sserved, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($tage . '%', '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($data->elapse . 'Day(s)', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($torder, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($tremain, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($sserved, '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($tage . '%', '75', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
      }

      $subtotal = $subtotal + $served;
      $ordtotal = $ordtotal + $order;
      $remtotal = $remtotal + $remain;

      $itemname = $data->itemname;
      $amt = $amt + $data->bal;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    } // end for loop

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $whname     = $config['params']['dataparams']['whname'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($barcode == "") {
      $barcode = "ALL";
    }
    if ($groupname == "") {
      $groupname = "ALL";
    }
    if ($brandname == "") {
      $brandname = "ALL";
    }
    if ($classname == "") {
      $classname = "ALL";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'All';
    }

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

    $str .= $this->reporter->col('CURRENT INVENTORY AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('WH : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow('100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $whname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class :' . $classname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      } else {
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
      }

      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    // $companyid = $config['params']['companyid'];
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ELAPSE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PURCH.QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('REMAIN QTY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SOLD QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('%AGE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
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
    $itemname   = $config['params']['dataparams']['itemname'];

    $count = 18;
    $page = 17;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);


    $amt = null;
    $itemname = "";
    $subtotal = 0;
    $ordtotal = 0;
    $remtotal = 0;
    // $cus = "";

    foreach ($result as $key => $data) {
      $display = $data->itemname;

      if ($data->brand != "") {
        $display = $data->brand_desc . " " . $display;
      } //end if

      if ($data->model != "") {
        $display = $display . " " . $data->model_name;
      } //end if

      if ($data->size != "") {
        $display = $display . " " . $data->size;
      } //end if

      $date = $data->dateid;
      $uom = $data->uom;
      $order = $data->qty;
      $served = $data->sold;
      $remain = $data->bal;
      $docno = $data->docno;
      $age = ($served / $order) * 100;
      $tage = number_format($age);
      if ($tage == 0) {
        $tage = '-';
      }
      $torder = number_format($order, 2);
      if ($torder == 0) {
        $torder = '-';
      }
      $sserved = number_format($served, 2);
      if ($sserved == 0) {
        $sserved = '-';
      }
      $tremain = number_format($remain, 2);
      if ($tremain == 0) {
        $tremain = '-';
      }
      $tremtotal = number_format($remtotal, 2);
      if ($tremtotal == 0) {
        $tremtotal = '-';
      }

      if ($itemname != $data->itemname) {
        if ($itemname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '150', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->col(number_format($ordtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col(number_format($remtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();

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
        }
        $subtotal = 0;
        $ordtotal = 0;
        $remtotal = 0;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($display, '250', null, false, '1px dotted ', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
      }

      $subtotal = $subtotal + $served;
      $ordtotal = $ordtotal + $order;
      $remtotal = $remtotal + $remain;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($date, '250', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($docno, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->elapse . 'Day(s)', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($torder, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($uom, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($tremain, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($sserved, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($tage . '%', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $itemname = $data->itemname;
      $amt = $amt + $data->bal;

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

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class