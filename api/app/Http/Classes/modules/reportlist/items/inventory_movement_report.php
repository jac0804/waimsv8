<?php

namespace App\Http\Classes\modules\reportlist\items;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use DateTime;
use DatePeriod;
use DateInterval;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class inventory_movement_report
{
  public $modulename = 'Inventory Movement Report';
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
    $fields = ['radioprint', 'start', 'end', 'ditemname', 'divsion', 'brand', 'part', 'dwhname'];

    switch ($companyid) {
      case 32: //3m
        array_push($fields, 'luom', 'radioreporttype', 'radiorepamountformat');
        break;
      case 28: //xcomp
        array_push($fields, 'categoryname');
        break;
      case 27: //NTE
      case 36: //rozlab
        $fields = ['radioprint', 'start', 'end', 'ditemname', 'divsion', 'brand', 'classname', 'dwhname'];
        break;
      case 47:
        array_push($fields,'radiolayoutformat');
        break;
      default:
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'luom.action', 'replookupuom');
    if ($companyid == 32) { //3m
      data_set($col1, 'radiorepamountformat.options', [
        ['label' => 'Cost', 'value' => 'rrcost', 'color' => 'orange'],
        ['label' => 'Selling Price', 'value' => 'isamt', 'color' => 'orange'],
        ['label' => 'None', 'value' => 'none', 'color' => 'orange']
      ]);

      data_set($col1, 'radioreporttype.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'pink'],
        ['label' => 'Daily', 'value' => 'daily', 'color' => 'pink'],
        ['label' => 'Monthly', 'value' => 'monthly', 'color' => 'pink']
      ]);
    }

    if ($companyid == 47) { //kitchenstar
      data_set($col1, 'radiolayoutformat.label', 'Excluded Warehouse');
      data_set(
        $col1,
        'radiolayoutformat.options',
        [
          ['label' => 'Exclude Dummy Warehouse', 'value' => '1', 'color' => 'orange'],
          ['label' => 'None', 'value' => '0', 'color' => 'orange']
        ]
      );
    }

    if ($companyid == 28) { //xcomp
      data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    }

    unset($col1['divsion']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['brand']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['part']);
    unset($col1['labeldata']['brand']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'part.name', 'partname');
    data_set($col1, 'brand.name', 'brandname');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-30) as start,
    left(now(),10) as end,
    0 as groupid,
    '' as stockgrp,
    0 as whid,
    '' as wh,
    '' as whname,
    '' as ditemname,
    '' as itemname,
    '' as barcode,
    0 as itemid,
    '' as divsion,
    '' as dwhname,
    0 as brandid,
    '' as brandname,
    '' as brand,
    '' as part,
    0 as partid,
    '' as partname,
    '' as uom,
    '' as class,
    '' as classname,
    'none' as amountformat, 
    'default' as reporttype,
    '' as categoryname, 
    '0' as category,
    '1' as layoutformat";

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
  // LAYOUT OF REPORT
  public function reportplotting($config)
  {
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $amountformat = "";
    switch ($companyid) {
      case 32: // 3m 
        $amountformat   = $config['params']['dataparams']['amountformat'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
          case 'daily':
            $result = $this->mmm_daily_layout($config);
            break;
          case 'monthly':
            break;

          default:
            switch ($amountformat) {
              case 'isamt':
                $result = $this->mmm_layout_SELLING_PRICE($config);
                break;
              case 'rrcost':
                $result = $this->mmm_layout_COST($config);
                break;
              default:
                $result = $this->mmm_layout($config);
                break;
            }
            break;
        }
        break; // end 3m - 32

      default:
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  // RESULT QUERY
  public function reportDefault($config)
  {
    $amountformat = isset($config['params']['dataparams']['amountformat']) ? $config['params']['dataparams']['amountformat'] : "";
    switch ($amountformat) {
      case 'isamt':
        $query = $this->report_DEFAULT_QUERY_SRP($config);
        break;
      default:
        $query = $this->report_DEFAULT_QUERY($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function report_DEFAULT_QUERY_SRP($config)
  {
    // QUERY
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh        = $config['params']['dataparams']['wh'];
    $stockgrp  = $config['params']['dataparams']['stockgrp'];
    $brandname = $config['params']['dataparams']['brandname'];
    $partname  = $config['params']['dataparams']['partname'];
    $barcode  = $config['params']['dataparams']['barcode'];
    
    $filter = "";
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }

    $query = "
    select category,barcode,itemname,uom,sum(begbal) as begbal,
    sum(totin) as inqty,sum(totout) as outqty, amt, disc
    from (
    select itemid,category,barcode,itemname,uom,(sum(qty)-sum(iss)) as begbal,0 as totin,0 as totout,0 as cost , amt, disc
    from(
    select item.itemid,cat.name as category,item.barcode,item.itemname,item.uom,stock.qty,stock.iss, item.amt, stock.disc
    from lastock as stock
    left join item on item.itemid = stock.itemid
    left join client as wh on wh.clientid = stock.whid
    left join lahead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where left(head.dateid,10) < '" . $start . "' " . $filter . "
    
    UNION ALL

    select item.itemid,cat.name as category,item.barcode,
    item.itemname,item.uom, stock.qty,stock.iss, item.amt, stock.disc
    from glstock as stock
    left join item on item.itemid = stock.itemid
    left join client as wh on wh.clientid = stock.whid
    left join glhead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where left(head.dateid,10) < '" . $start . "' " . $filter . ") as tblbal 
    group by itemid, category, barcode, itemname, uom, amt, disc
    
    UNION ALL

    select itemid,category,barcode,itemname,uom,0 as begbal,sum(qty) as totin,sum(iss) as totout,
    0 as cost, amt, disc
    from(
    select item.itemid,cat.name as category,item.barcode,item.itemname,item.uom, stock.qty,stock.iss, item.amt, stock.disc
    from lastock as stock
    left join item on item.itemid = stock.itemid
    left join client as wh on wh.clientid = stock.whid
    left join lahead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
    
    UNION ALL

    select item.itemid,cat.name as category,item.barcode,item.itemname,item.uom, stock.qty,stock.iss, item.amt, stock.disc
    from glstock as stock
    left join item on item.itemid = stock.itemid
    left join client as wh on wh.clientid = stock.whid
    left join glhead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . ") as tblin 
    group by itemid,category, barcode, itemname, uom, amt, disc
    ) as runningcoder 
    where barcode <> '' 
    group by itemid, category, barcode, itemname, uom, amt, disc
    order by category,itemname";
    return $query;
  }

  public function report_DEFAULT_QUERY($config)
  {
    // QUERY
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];
    $wh        = $config['params']['dataparams']['wh'];
    $stockgrp  = $config['params']['dataparams']['stockgrp'];
    $brandname = $config['params']['dataparams']['brandname'];
    $partname  = $config['params']['dataparams']['partname'];
    $class     = $config['params']['dataparams']['classname'];
    $barcode   = $config['params']['dataparams']['barcode'];
    $excwh = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '';

    $filter = "";
    $costfilter = "";
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $costfilter = " and stock.whid=" . $whid;
      $filter .= " and stock.whid=" . $whid;
    } else {
      $costfilter = "";
    } //end if

    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($stockgrp != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }

    if ($companyid == 28) { //xcomp
      $categoryname     = $config['params']['dataparams']['categoryname'];
      if ($categoryname != "") {
        $categoryid     = $config['params']['dataparams']['category'];
        $filter .= " and item.category=" . $categoryid;
      }
    }

    $itemfield = "item.itemname";

    
    if($companyid == 47){//kstar
      if ($excwh) {
        $filter .= " and stock.whid <> 1014";
        $itemfield = "concat(item.itemname,' ',item.color,' ',item.sizeid)";
      }
    }


    $addjoinclass = '';
    $classname = '';
    $addclassname = '';
    $conditonclass = "";
    if ($companyid == 36) { //rozlab
      $addjoinclass = "left join item_class as cls on cls.cl_id=item.class";
      $classname = ",classname";
      $addclassname = ",cls.cl_name as classname";
      if ($class != "") {
        $conditonclass = "and classname = '$class'";
      }
    }

    $query = "
    select category,barcode,itemname,uom,sum(begbal) as begbal,
    sum(totin) as inqty,sum(totout) as outqty,
    ifnull((
    select cost 
    from rrstatus as stock
    left join client as wh on wh.clientid = stock.whid 
    where stock.itemid=runningcoder.itemid " . $costfilter . " 
    order by dateid desc limit 1),0) as cost $classname
    from (
    select itemid,category,barcode,itemname,uom,(sum(qty)-sum(iss)) as begbal,0 as totin,0 as totout,0 as cost $classname
    from(
    select item.itemid,cat.name as category,item.barcode,".$itemfield." as itemname,item.uom,stock.qty,stock.iss $addclassname
    from lastock as stock
    left join item on item.itemid = stock.itemid
    $addjoinclass
    left join client as wh on wh.clientid = stock.whid
    left join lahead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where date(head.dateid) < '" . $start . "' " . $filter . "
    
    UNION ALL

    select item.itemid,cat.name as category,item.barcode,
    ".$itemfield." as itemname,item.uom, stock.qty,stock.iss $addclassname
    from glstock as stock
    left join item on item.itemid = stock.itemid
    $addjoinclass
    left join client as wh on wh.clientid = stock.whid
    left join glhead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where date(head.dateid) < '" . $start . "' " . $filter . ") as tblbal 
    group by itemid, category, barcode, itemname, uom $classname
    
    UNION ALL

    select itemid,category,barcode,itemname,uom,0 as begbal,sum(qty) as totin,sum(iss) as totout,0 as cost $classname
    from(
    select item.itemid,cat.name as category,item.barcode,".$itemfield." as itemname,item.uom, stock.qty,stock.iss $addclassname
    from lastock as stock
    left join item on item.itemid = stock.itemid
    $addjoinclass
    left join client as wh on wh.clientid = stock.whid
    left join lahead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
    
    UNION ALL

    select item.itemid,cat.name as category,item.barcode,".$itemfield." as itemname,item.uom, stock.qty,stock.iss $addclassname
    from glstock as stock
    left join item on item.itemid = stock.itemid
    $addjoinclass
    left join client as wh on wh.clientid = stock.whid
    left join glhead as head on head.trno=stock.trno
    left join itemcategory as cat on cat.line = item.category
    where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . ") as tblin 
    group by itemid,category, barcode, itemname, uom $classname
    ) as runningcoder 
    where barcode <> '' $conditonclass
    group by itemid, category, barcode, itemname, uom $classname
    order by category,itemname";
    
    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $brandid     = $config['params']['dataparams']['brandid'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $partid     = $config['params']['dataparams']['partid'];
    $partname     = $config['params']['dataparams']['partname'];
    $class     = isset($config['params']['dataparams']['classname']) ? $config['params']['dataparams']['classname'] : '';
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    // $categoryid     = 0;
    $categoryname     = '';

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

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY MOVEMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br /><br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow('1000', null, '', $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($companyid == 36) { //rozlab
      if ($class == '') {
        $str .= $this->reporter->col('Class : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('Class : ' . $class, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      }
    } else {
      if ($partid == '') {
        $str .= $this->reporter->col('Part : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('Part : ' . $partname, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      }
    }
    if ($groupid == '') {
      $str .= $this->reporter->col('Group : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $stockgrp, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($brandid == '') {
      $str .= $this->reporter->col('Brand : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Brand :' . $brandname, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('WH : ' . $wh, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($companyid == 28) { //xcomp
      // $categoryid     = $config['params']['dataparams']['category'];
      $categoryname     = $config['params']['dataparams']['categoryname'];
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      }
    }
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    if ($companyid == 47) { //kitchenstar
      if (!$viewcost) { //no access
        $str .= $this->reporter->col('BEG. QTY', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('IN QTY', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('OUT QTY', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      } else { //may access
        $str .= $this->reporter->col('BEG. QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('IN QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('OUT QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      }
    } else { //default
      $str .= $this->reporter->col('BEG. QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('IN QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('OUT QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }
    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result  = $this->reportDefault($config);
    $companyid     = $config['params']['companyid'];
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->default_displayHeader($config);
    $decimal = 2;
    if ($companyid == 36) { //rozlab
      $decimal = 4;
    }

    $category = "";
    $totalext = 0;
    $costgtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if (strtoupper($category) == strtoupper($data->category)) {
        $category = "";
      } else {
        $category = strtoupper($data->category);
      }

      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($category, '1000', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


      if ($companyid == 47) { //kitchenstar
        if (!$viewcost) { //no access
          $str .= $this->reporter->col(number_format($data->begbal, $decimal), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->inqty, $decimal), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->outqty, $decimal), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          $balance = (floatval($data->begbal) + floatval($data->inqty)) - floatval($data->outqty);

          $str .= $this->reporter->col(number_format(abs($balance), $decimal), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        } else { //may access
          $str .= $this->reporter->col(number_format($data->begbal, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->inqty, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->outqty, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          $balance = (floatval($data->begbal) + floatval($data->inqty)) - floatval($data->outqty);

          $str .= $this->reporter->col(number_format(abs($balance), $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          $str .= $this->reporter->col(number_format($data->cost, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          $totalext = number_format(floatval($data->cost) * floatval($balance), 2);
          $costgtotal = $costgtotal + floatval(floatval($data->cost) * floatval($balance));

          $str .= $this->reporter->col($totalext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }
      } else { //default

        $str .= $this->reporter->col(number_format($data->begbal, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->inqty, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->outqty, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $balance = (floatval($data->begbal) + floatval($data->inqty)) - floatval($data->outqty);

        $str .= $this->reporter->col(number_format(abs($balance), $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col(number_format($data->cost, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $totalext = number_format(floatval($data->cost) * floatval($balance), 2);
        $costgtotal = $costgtotal + floatval(floatval($data->cost) * floatval($balance));

        $str .= $this->reporter->col($totalext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();

      $category = strtoupper($data->category);
    }

    $str .= $this->reporter->begintable('1000');
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    if ($companyid == 47) { //kitchenstar
      if (!$viewcost) { //no access
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
        $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
      } else { //may access
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
        $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Grand Total: ', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($costgtotal, 2), '75', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
      }
    } else {
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Grand Total: ', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($costgtotal, 2), '75', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function mmm_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh     = $config['params']['dataparams']['wh'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $partname     = $config['params']['dataparams']['partname'];
    $uom     = $config['params']['dataparams']['uom'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY MOVEMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br /><br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part : ' . ($partname != '' ? $partname : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Group : ' . ($stockgrp != '' ? $stockgrp : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Brand : ' . ($brandname != '' ? $brandname : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('WH : ' . ($wh != '' ? $wh : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BEG. QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('IN QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('OUT QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    if ($uom != '') {
      $str .= $this->reporter->col('BEG. ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('IN ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('OUT ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('BALANCE ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    return $str;
  }

  public function mmm_header_costsrp($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh     = $config['params']['dataparams']['wh'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $partname     = $config['params']['dataparams']['partname'];
    $uom     = $config['params']['dataparams']['uom'];
    $amountformat = $config['params']['dataparams']['amountformat'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY MOVEMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br /><br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part : ' . ($partname != '' ? $partname : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Group : ' . ($stockgrp != '' ? $stockgrp : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Brand : ' . ($brandname != '' ? $brandname : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('WH : ' . ($wh != '' ? $wh : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    if ($amountformat == 'isamt') {
      $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('DISCOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('BEG. QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('IN QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('OUT QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      if ($uom != '') {
        $str .= $this->reporter->col('BEG. ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('IN ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('OUT ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      }
      $str .= $this->reporter->col('SRP', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('BEG. QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('IN QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('OUT QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      if ($uom != '') {
        $str .= $this->reporter->col('BEG. ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('IN ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('OUT ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('BALANCE ' . $uom, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      }
      $str .= $this->reporter->col('SRP', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    return $str;
  }

  public function mmm_layout($config)
  {
    $result  = $this->reportDefault($config);
    $uom     = $config['params']['dataparams']['uom'];

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1200');
    $str .= $this->mmm_header($config);

    $part = "";
    $category = "";
    $totalext = 0;
    $costgtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if (strtoupper($category) == strtoupper($data->category)) {
        $category = "";
      } else {
        $category = strtoupper($data->category);
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($category, '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->begbal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->inqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->outqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $balance = (floatval($data->begbal) + floatval($data->inqty)) - floatval($data->outqty);

      $str .= $this->reporter->col(abs($balance), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      if ($uom != "") {

        $qry = "select ifnull(factor,0) as value from uom 
        left join item on item.itemid = uom.itemid
        where item.barcode = ? and uom.uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->barcode, $uom]);
        $str .= $this->reporter->col((($data->begbal != 0 && $uombal != 0) ? number_format($data->begbal / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($data->inqty != 0 && $uombal != 0) ? number_format($data->inqty / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($data->outqty != 0 && $uombal != 0) ? number_format($data->outqty / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($balance != 0 && $uombal != 0) ? number_format(abs($balance) / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
      }

      $totalext = number_format(floatval($data->cost) * floatval($balance), 2);
      $costgtotal = $costgtotal + floatval(floatval($data->cost) * floatval($balance));

      $str .= $this->reporter->col($totalext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $category = strtoupper($data->category);
    }

    $str .= $this->reporter->begintable('1200');
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function mmm_layout_COST($config)
  {
    $result  = $this->reportDefault($config);
    $uom     = $config['params']['dataparams']['uom'];

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1200');
    $str .= $this->mmm_header_costsrp($config);

    $category = "";
    $totalext = 0;
    $costgtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if (strtoupper($category) == strtoupper($data->category)) {
        $category = "";
      } else {
        $category = strtoupper($data->category);
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($category, '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->begbal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->inqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->outqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $balance = (floatval($data->begbal) + floatval($data->inqty)) - floatval($data->outqty);

      $str .= $this->reporter->col(abs($balance), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      if ($uom != "") {

        $qry = "select ifnull(factor,0) as value from uom 
        left join item on item.itemid = uom.itemid
        where item.barcode = ? and uom.uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->barcode, $uom]);
        $str .= $this->reporter->col((($data->begbal != 0 && $uombal != 0) ? number_format($data->begbal / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($data->inqty != 0 && $uombal != 0) ? number_format($data->inqty / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($data->outqty != 0 && $uombal != 0) ? number_format($data->outqty / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($balance != 0 && $uombal != 0) ? number_format(abs($balance) / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col(number_format($data->cost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $totalext = number_format(floatval($data->cost) * floatval($balance), 2);
      $costgtotal = $costgtotal + floatval(floatval($data->cost) * floatval($balance));

      $str .= $this->reporter->col($totalext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $category = strtoupper($data->category);
    }

    $str .= $this->reporter->begintable('1200');
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($costgtotal, 2), '75', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function mmm_layout_SELLING_PRICE($config)
  {
    $result  = $this->reportDefault($config);
    $uom     = $config['params']['dataparams']['uom'];

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1200');
    $str .= $this->mmm_header_costsrp($config);

    $category = "";
    $totalext = 0;
    $costgtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if (strtoupper($category) == strtoupper($data->category)) {
        $category = "";
      } else {
        $category = strtoupper($data->category);
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($category, '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->disc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->begbal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->inqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->outqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $balance = (floatval($data->begbal) + floatval($data->inqty)) - floatval($data->outqty);

      $str .= $this->reporter->col(abs($balance), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      if ($uom != "") {

        $qry = "select ifnull(factor,0) as value from uom 
        left join item on item.itemid = uom.itemid
        where item.barcode = ? and uom.uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->barcode, $uom]);
        $str .= $this->reporter->col((($data->begbal != 0 && $uombal != 0) ? number_format($data->begbal / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($data->inqty != 0 && $uombal != 0) ? number_format($data->inqty / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($data->outqty != 0 && $uombal != 0) ? number_format($data->outqty / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((($balance != 0 && $uombal != 0) ? number_format(abs($balance) / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $totalext = number_format(floatval($data->amt) * floatval($balance), 2);
      $costgtotal = $costgtotal + floatval(floatval($data->amt) * floatval($balance));

      $str .= $this->reporter->col($totalext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $category = strtoupper($data->category);
    }

    $str .= $this->reporter->begintable('1200');
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($costgtotal, 2), '75', null, false, $border, '', 'R', $font, $fontsize, 'Bi', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function mmm_daily_layout($config)
  {
    $result  = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh     = $config['params']['dataparams']['wh'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $partname     = $config['params']['dataparams']['partname'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];

    $filter = "";
    if ($itemname) {
      $filter .= " and itemid = $itemid ";
    }

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh     = $config['params']['dataparams']['wh'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $partname     = $config['params']['dataparams']['partname'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY MOVEMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br /><br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From : ' . $start . ' To : ' . $end, null, null, '', $border, '', 'l', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part : ' . ($partname != '' ? $partname : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Group : ' . ($stockgrp != '' ? $stockgrp : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Brand : ' . ($brandname != '' ? $brandname : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('WH : ' . ($wh != '' ? $wh : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $xdates = $this->getBetweenDates($start, $end);

    $sd = new DateTime($start);
    $ed = new DateTime($end);
    $sd->modify('+1 day');
    $ed->modify('+1 day');

    $fdates = $this->getBetweenDates($sd->format('Y-m-d'), $ed->format('Y-m-d'));

    $lsize = (count($xdates) * 100) + 400;

    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => $lsize];

    $str .= $this->reporter->begintable($lsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SKU', '200', null, '', $border, 'T', 'l', $font, '14', 'B', '', '5px 0px 5px 0px');
    $str .= $this->reporter->col('Paramenters', '200', null, '', $border, 'T', 'l', $font, '14', 'B', '', '5px 0px 5px 0px');
    foreach ($xdates as $key => $value) {
      $str .= $this->reporter->col($value, '100', null, '', $border, 'T', 'r', $font, '12', 'B', '', '5px 0px 5px 0px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $itemdata = $this->coreFunctions->opentable("select itemname, itemid from item where 1=1 $filter order by itemname");



    $str .= $this->reporter->begintable($lsize);
    $str .= $this->reporter->startrow();

    foreach ($itemdata as $keyx => $valuex) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($valuex->itemname, '200', null, '', $border, 'T', 'l', $font, '14', 'B', '', '');

      $str .= $this->reporter->col('', '200', null, '', $border, 'T', 'l', $font, '14', 'B', '', '');
      foreach ($xdates as $key => $value) {
        $str .= $this->reporter->col('', '100', null, '', $border, 'T', 'l', $font, '14', '', '', '');
      }
      $str .= $this->reporter->endrow();

      // begining
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      $str .= $this->reporter->col('Beginning Stock', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      foreach ($xdates as $key => $value) {
        $str .= $this->reporter->col(round($this->getbegeningqty($valuex->itemid, $value)), '100', null, '', $border, '', 'r', $font, '14', '', '', '');
      }
      $str .= $this->reporter->endrow();

      // recieve
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      $str .= $this->reporter->col('Stock Received', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      foreach ($xdates as $key => $value) {
        $str .= $this->reporter->col(round($this->getreceiveqty($valuex->itemid, $value)), '100', null, '', $border, '', 'r', $font, '14', '', '', '');
      }
      $str .= $this->reporter->endrow();

      // primary loss
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      $str .= $this->reporter->col('Primary Loss', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      foreach ($xdates as $key => $value) {
        $str .= $this->reporter->col(round($this->getprimaryloss($valuex->itemid, $value)), '100', null, '', $border, '', 'r', $font, '14', '', '', '');
      }
      $str .= $this->reporter->endrow();

      // Average Sales
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      $str .= $this->reporter->col('Average Sales', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      foreach ($xdates as $key => $value) {
        $str .= $this->reporter->col(round($this->getsalesqty($valuex->itemid, $value)), '100', null, '', $border, '', 'r', $font, '14', '', '', '');
      }
      $str .= $this->reporter->endrow();

      // Ending Stock
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      $str .= $this->reporter->col('Ending Stock', '200', null, '', $border, '', 'l', $font, '14', 'B', '', '');
      foreach ($fdates as $key => $value) {
        $str .= $this->reporter->col(round($this->getbegeningqty($valuex->itemid, $value)), '100', null, '', $border, '', 'r', $font, '14', '', '', '');
      }
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function getbegeningqty($itemid, $date)
  {

    $begbal_qry = "select sum(qty - iss) as value from (
  select head.doc, item.itemname, item.itemid, uom.uom,
  (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
  (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
  head.dateid, wh.clientid as whid
  from lastock as stock
  left join lahead as head on stock.trno = head.trno
  left join item on item.itemid=stock.itemid
  left join client as wh on wh.clientid=stock.whid
  left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
  union all
  select head.doc, item.itemname, item.itemid, uom.uom,
  (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
  (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
  head.dateid, wh.clientid as whid
  from glstock as stock
  left join glhead as head on stock.trno = head.trno
  left join item on item.itemid=stock.itemid
  left join client as wh on wh.clientid=stock.whid
  left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
  ) as x
  where 1=1 and itemid = $itemid and date(dateid) < '$date' and whid not in (2926,4209) ";

    return  $this->coreFunctions->datareader($begbal_qry);
  }

  private function getreceiveqty($itemid, $date)
  {
    $begbal_qry = "select sum(qty) as value from (
    select head.doc, item.itemname, item.itemid, uom.uom,
    (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    head.dateid, wh.clientid as whid
    from lastock as stock
    left join lahead as head on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
    union all
    select head.doc, item.itemname, item.itemid, uom.uom,
    (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    head.dateid, wh.clientid as whid
    from glstock as stock
    left join glhead as head on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
    ) as x
    where 1=1 and itemid = $itemid and date(dateid) = '$date' and whid not in (2926,4209) ";

    return  $this->coreFunctions->datareader($begbal_qry);
  }

  private function getprimaryloss($itemid, $date)
  {

    $begbal_qry = "select sum(iss) as value from (
    select head.doc, item.itemname, item.itemid, uom.uom,
    (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    head.dateid, wh.clientid as whid
    from lastock as stock
    left join lahead as head on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
    union all
    select head.doc, item.itemname, item.itemid, uom.uom,
    (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    head.dateid,  wh.clientid as whid
    from glstock as stock
    left join glhead as head on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
    ) as x
    where doc in ('AJ','TS','DM') and  itemid = $itemid and date(dateid) = '$date' and whid not in (2926,4209) ";

    return  $this->coreFunctions->datareader($begbal_qry);
  }

  private function getsalesqty($itemid, $date)
  {

    $begbal_qry = "select sum(iss) as value from (
    select head.doc, item.itemname, item.itemid, uom.uom,
    (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    head.dateid,  wh.clientid as whid
    from lastock as stock
    left join lahead as head on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
    union all
    select head.doc, item.itemname, item.itemid, uom.uom,
    (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    head.dateid,  wh.clientid as whid
    from glstock as stock
    left join glhead as head on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom 
    ) as x
    where doc = 'SJ' and  itemid = $itemid and date(dateid) = '$date' and whid not in (2926,4209) ";

    return  $this->coreFunctions->datareader($begbal_qry);
  }

  private function getBetweenDates($startDate, $endDate)
  {

    $array = array();
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($endDate);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($startDate), $interval, $realEnd);

    foreach ($period as $date) {
      $array[] = $date->format('Y-m-d');
    }

    return $array;
  }
}//end class