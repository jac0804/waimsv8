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

class schedule_of_inventory_fifo
{
  public $modulename = 'Schedule of Inventory FIFO';
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

    switch ($companyid) {
      case 14: //majesty
        $fields = ['radioprint', 'start', 'part', 'dwhname', 'divsion'];
        break;
      case 36: //rozlab
        $fields = ['radioprint', 'start', 'divsion', 'dwhname', 'class'];
      case 27:
        $fields = ['radioprint', 'start', 'divsion', 'class', 'dwhname'];
        break;
      default:
        $fields = ['radioprint', 'start', 'divsion', 'dwhname'];
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'start.label', 'Balance as Of');
    if ($companyid == 14) { //majesty
      data_set($col1, 'divsion.label', 'Division');
      data_set($col1, 'part.label', 'Principal');
    }

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'part.name', 'partname');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    left(now(),10) as start,
    0 as groupid,
    '' as stockgrp,
    0 as whid,
    '' as wh,
    '' as whname,
    '' as divsion,
    '' as dwhname,
    0 as partid,
    '' as part,
    '' as partname,
    0 as classid, 
    '' as class,
    '' as classic
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

  // LAYOUT OF REPORT
  public function reportplotting($config)
  {
    $result = $this->reportDefaultLayout_DETAILED($config);
    return $result;
  }

  // RESULT QUERY
  public function reportDefault($config)
  {
    $query = $this->report_DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function report_DEFAULT_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $wh     = $config['params']['dataparams']['wh'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $classname  = $config['params']['dataparams']['classic'];
    $partname = $config['params']['dataparams']['partname'];

    $filter = '';
    if ($wh != '') {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and rrstatus.whid=" . $whid;
    }
    if ($stockgrp != '') {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }

    switch ($companyid) {
      case 14: //majesty
        if ($partname != "") {
          $partid = $config['params']['dataparams']['partid'];
          $filter .= " and item.partid=" . $partid;
        }
        break;
      case 36: //rozlab
      case 27: //nte
        if ($classname != "") {
          $classid = $config['params']['dataparams']['classid'];
          $filter .= " and item.class=" . $classid;
        }
        break;
    }

    $query = "
    select item.barcode,item.itemname,item.uom,
    rrstatus.docno,left(rrstatus.dateid,10) as dateid,
    rrstatus.expiry,rrstatus.loc,(rrstatus.bal / uom2.factor) as qty,uom.factor,
    rrstatus.disc,glstock.rrcost,rrstatus.cost,
    ifnull(class.cl_name,'') as classname 
    from rrstatus
    left join glstock on glstock.trno = rrstatus.trno and 
    glstock.itemid = rrstatus.itemid and 
    glstock.line = rrstatus.line
    left join item on rrstatus.itemid = item.itemid
    left join item_class as class on class.cl_id = item.class
    left join uom on uom.uom = glstock.uom and uom.itemid = rrstatus.itemid
    left join uom as uom2 on uom2.uom = item.uom and uom2.itemid = rrstatus.itemid
    where item.isinactive <> '1' and rrstatus.bal <> 0 $filter
    order by item.itemname,rrstatus.dateid";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $whname     = $config['params']['dataparams']['whname'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $classname    = $config['params']['dataparams']['classic'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->col('SCHEDULE OF INVENTORY (FIFO)', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BALANCE AS OF : ' . $start, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    if ($whname == "") {
      $str .= $this->reporter->col('WAREHOUSE : ALL', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('WAREHOUSE : ' . $whname, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($stockgrp == "") {
      $str .= $this->reporter->col('GROUP : ALL', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('GROUP :' . $stockgrp, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    if ($classname == "") {
      $str .= $this->reporter->col('CLASS : ALL', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('CLASS :' . $classname, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    if ($companyid == 14) { //majesty
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $part = $config['params']['dataparams']['part'];
      $partname = $config['params']['dataparams']['partname'];
      if ($part == '') {
        $str .= $this->reporter->col('PRINCIPAL : ALL', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('PRINCIPAL :' . $partname, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
      }
    }
    // $str .= $this->reporter->col('', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('DESCRIPTION', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('UNIT', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('DOC NO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('DATE', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('LOCATION', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('EXPIRY', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('COST', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('QUANTITY', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('INV COST', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '4px');
    } else {
      $str .= $this->reporter->col('BARCODE', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('DESCRIPTION', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('UNIT', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('DOC NO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('DATE', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('EXPIRY', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('QUANTITY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('INV COST', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '4px');
    }
    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result  = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 48;
    $page = 50;

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->default_displayHeader($config);
    $totalcost = 0;

    $name = "";
    $code = "";
    $subtotal = 0;
    $subqty = 0;
    $iitem = "";

    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    foreach ($result as $key => $data) {
      $rrcostcomputed = $data->cost;
      $invcost = $rrcostcomputed * $data->qty;
      if ($data->factor != 0) $invcost = $invcost / $data->factor;

      if ($viewcost == '0') {
        $rrcostcomputed = 0;
        $invcost = 0;
      }

      if ($name == "") {
        $str .= $this->reporter->startrow();
        if ($companyid == 14) { //majesty
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($data->uom, '75', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->barcode, '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($data->uom, '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
      } //end if

      if (strtoupper($name) == strtoupper($data->itemname)) {
        $name = "";
      } else {
        if ($name != '') {
          $str .= $this->reporter->startrow();
          if ($companyid == 14) { //majesty
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUB-TOTAL :', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subqty, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
          } else {
            $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUB-TOTAL :', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subqty, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
          }
          $str .= $this->reporter->endrow();
        } //end if    

        if ($name != '') {
          $str .= $this->reporter->startrow();
          if ($companyid == 14) { //majesty
            $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($data->uom, '75', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->barcode, '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($data->uom, '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          }
        }
        $subtotal = 0;
        $subqty = 0;
        $code = $data->itemname;

        if (strtoupper($code) == strtoupper($data->itemname)) {
          $code = "";
        } else {
          $code = strtoupper($data->itemname);
        }
      }

      if ($iitem == $data->itemname) {
        $iitem = "";
      } else {
        $iitem = $data->itemname;
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 14) { //majesty
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->expiry, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($rrcostcomputed, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($invcost, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->expiry, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($rrcostcomputed, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($invcost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $totalcost = $totalcost + $invcost;

      $subtotal = $subtotal + $invcost;
      $subqty = $subqty + $data->qty;
      $name = strtoupper($data->itemname);
      $code = $data->itemname;
      $iitem = $data->itemname;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('SUB-TOTAL :', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subqty, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
    } else {
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('SUB-TOTAL :', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subqty, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'b', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('GRAND TOTAL', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalcost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class