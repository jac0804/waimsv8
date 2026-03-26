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

class item_master_list
{
  public $modulename = 'Item Master List';
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'categoryname', 'subcatname', 'dclientname', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'brandid.name', 'brandid');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');

    $fields = ['radioreportitemtype', 'radioreportitemstatus'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      0 as itemid,
      '' as itemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as categoryname,
      0 as brandid,
      '' as brandname,
      '' as brand,
      0 as classid,
      '' as classic,
      '' as ditemname,
      '' as divsion,
      '' as category,
      '' as subcatname,
      '' as subcat,
      '' as whname,
      '' as dwhname,
      0 as whid,
      '' as wh,
      0 as clientid,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '(0,1)' as itemtype,
      '(0,1)' as itemstatus,
      '' as class,
      '0' as sortby,
      'amt' as itemsort ";
    return $this->coreFunctions->opentable($paramstr);
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

  public function reportplotting($config, $result)
  {
    $result = $this->reportDefaultLayout($config, $result);
    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  // QUERY
  public function DEFAULT_QUERY($config)
  {
    $barcode   = $config['params']['dataparams']['barcode'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $brandname  = $config['params']['dataparams']['brandname'];   
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $client = $config['params']['dataparams']['client'];
    $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$client]);

    $filter = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and item.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='" . $category . "'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='" . $subcat . "'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($client != "") {
      $filter .= " and item.supplier=" . $clientid;
    }

    $query = "select item.sizeid, current_timestamp as print_date, 0 as sort, item.barcode, item.itemname, 
    frontend_ebrands.brand_desc as brand, item.itemid, ifnull(parts.part_name,'') as part, uom,
    body,class,supplier,cost, amt as price, item.isinactive, item.category,amt9 as currcost, amt8 as prevcost
    from item 
    left join part_masterfile as parts on parts.part_id = item.part
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    where item.barcode <> '' and item.isinactive in $itemstatus and item.isimport in $itemtype $filter and item.isofficesupplies=0";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $classname  = $config['params']['dataparams']['classic'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $itemstatus = $config['params']['dataparams']['itemstatus'];
    $client = $config['params']['dataparams']['clientname'];
    $wh = $config['params']['dataparams']['whname'];
    $layoutsize = '1400';

    if ($itemtype == '(0)') {
      $itemtype = 'Local';
    } elseif ($itemtype == '(1)') {
      $itemtype = 'Import';
    } else {
      $itemtype = 'Both';
    }

    if ($itemstatus == '(0)') {
      $itemstatus = 'Active';
    } elseif ($itemstatus == '(1)') {
      $itemstatus = 'Inactive';
    } else {
      $itemstatus = 'Both';
    }

    // $supp = '';
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM MASTER LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse: ' . ($wh != '' ? $wh : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item:' . ($barcode != '' ? $barcode : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group:' . ($groupname != '' ? $groupname : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand:' . ($brandname != '' ? $brandname : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class:' . ($classname != '' ? $classname : 'ALL'), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Category: ' . ($categoryname != '' ? $categoryname : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Sub-Category: ' . ($subcatname != '' ? $subcatname : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier: ' . ($client != '' ? $client : 'ALL'), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1400');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UNIT', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MIN', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LAST TRNX', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LAST PUR. DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LAST SUPP. INV. DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LAST SUPP.', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('LAST PUR. QTY', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PENDING ORDER', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CURR. COST', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PREV. COST', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();


    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $this->reporter->linecounter = 0;

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1400';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

    $part = "";
    $brand = "";
    foreach ($result as $key => $data) {
      if (strtoupper($part) == strtoupper($data->part)) {
        $part = "";
      } else {
        $part = $data->part;
      } //end if

      if (strtoupper($brand) == strtoupper($data->brand)) {
        $brand = "";
      } else {
        $brand = strtoupper($data->brand);
      } //end if

      $price = number_format($data->price, 2);
      if ($price == 0) {
        $price = '-';
      } //end if

      if ($part != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($part, '110', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }
      if ($brand != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brand, '110', null, false, $border, '', 'R', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, 'Bi', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $minmax = $this->getMinMax($config, $data);
      $bal = $this->getBal($config, $data);
      $lastsales = $this->getLastSales($data);
      $lastpurchase = $this->getLastPurchase($data);
      $pendingorder = $this->getPendingOrder($data);
      $lastsuppinv = $this->getLastSuppInvoice($data);
      $str .= $this->reporter->col($data->barcode, '110', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($bal[0]->bal == 0 ? '-' : number_format($bal[0]->bal, 2)), '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->col($data->groupid, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->price == 0 ? '-' : number_format($data->price, 2)), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($minmax[0]->min == 0 ? '-' : $minmax[0]->min), '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($minmax[0]->max == 0 ? '-' : $minmax[0]->max), '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($lastsales[0]->dateid, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($lastpurchase[0]->dateid, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($lastsuppinv[0]->docno . '-' . $lastsuppinv[0]->dateid, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($lastsuppinv[0]->client, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($lastpurchase[0]->qty == 0 ? '-' : $lastpurchase[0]->qty), '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($pendingorder[0]->pending, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->currcost == 0 ? '-' : number_format($data->currcost, 2)), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(($data->prevcost == 0 ? '-' : number_format($data->prevcost, 2)), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $brand = strtoupper($data->brand);
      $part = $data->part;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->default_displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function getMinMax($config, $data)
  {
    $wh = $config['params']['dataparams']['wh'];
    if ($wh != '') {
      $qry = "select min, max from itemlevel where itemid='" . $data->itemid . "' and center='" . $wh . "'";
    } else {
      $qry = "select 0 as min, 0 as max";
    }
    $minmax = $this->coreFunctions->opentable($qry);
    if (empty($minmax)) $minmax = $this->coreFunctions->opentable("select 0 as min, 0 as max");
    return $minmax;
  }

  public function getBal($config, $data)
  {
    $wh = $config['params']['dataparams']['wh'];
    $whid = '';
    if ($wh != '') $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $whfilter = "";
    $whfilter2 = "";
    if ($whid != '') {
      $whfilter = " and head.wh='" . $wh . "'";
      $whfilter2 = " and head.whid='" . $whid . "'";
    }
    $qry = "select sum(qty-iss) as bal from (
      select stock.qty, stock.iss from lahead as head left join lastock as stock on stock.trno=head.trno where stock.itemid=" . $data->itemid . " " . $whfilter . "
      union all
      select stock.qty, stock.iss from glhead as head left join glstock as stock on stock.trno=head.trno where stock.itemid=" . $data->itemid . " " . $whfilter2 . "
    ) as t";
    $bal = $this->coreFunctions->opentable($qry);
    if (empty($bal)) $bal = $this->coreFunctions->opentable("select 0.00 as bal");
    return $bal;
  }

  public function getLastSales($data)
  {
    $qry = "select head.dateid from lahead as head left join lastock as stock on stock.trno=head.trno where stock.itemid=" . $data->itemid . " and head.doc='SK'
      union all
      select head.dateid from glhead as head left join glstock as stock on stock.trno=head.trno where stock.itemid=" . $data->itemid . " and head.doc='SK'
      order by dateid desc limit 1";
    $lastsales = $this->coreFunctions->opentable($qry);
    if (empty($lastsales)) $lastsales = $this->coreFunctions->opentable("select '' as dateid");
    return $lastsales;
  }

  public function getLastPurchase($data)
  {
    $qry = "select head.dateid, format(stock.qty,2) as qty from lahead as head left join lastock as stock on stock.trno=head.trno where stock.itemid=" . $data->itemid . " and head.doc='RR'
      union all
      select head.dateid, format(stock.qty,2) as qty from glhead as head left join glstock as stock on stock.trno=head.trno where stock.itemid=" . $data->itemid . " and head.doc='RR'
      order by dateid desc limit 1";
    $lastpurchase = $this->coreFunctions->opentable($qry);
    if (empty($lastpurchase)) $lastpurchase = $this->coreFunctions->opentable("select '' as dateid, 0 as qty");
    return $lastpurchase;
  }

  public function getPendingOrder($data)
  {
    $qry = "select format(sum(qty-qa),2) as pending from (
      select stock.qty, stock.qa from pohead as head left join postock as stock on stock.trno=head.trno where stock.void=0 and stock.itemid=" . $data->itemid . "
      union all
      select stock.qty, stock.qa from hpohead as head left join hpostock as stock on stock.trno=head.trno where stock.void=0 and stock.itemid=" . $data->itemid . "
    ) as t";
    $pendingorder = $this->coreFunctions->opentable($qry);
    if (empty($pendingorder)) $pendingorder = $this->coreFunctions->opentable("select 0 as pending");
    return $pendingorder;
  }

  public function getLastSuppInvoice($data)
  {
    $qry = "select trno, docno, dateid, client, rrdocno from (
      select head.trno, head.docno, head.dateid, head.client, rrnum.docno as rrdocno
        from lahead as head
        left join cntnum as rrnum on rrnum.svnum=head.trno
        left join glstock as rrstock on rrstock.trno=rrnum.trno
        where rrstock.itemid=" . $data->itemid . " and head.doc='SN'
      union all
      select head.trno, head.docno, head.dateid, client.client, rrnum.docno as rrdocno
        from glhead as head
        left join cntnum as rrnum on rrnum.svnum=head.trno
        left join glstock as rrstock on rrstock.trno=rrnum.trno
        left join client on client.clientid=head.clientid
        where rrstock.itemid=" . $data->itemid . " and head.doc='SN'
    ) as t order by docno desc limit 1";
    $lastsuppinv = $this->coreFunctions->opentable($qry);
    if (empty($lastsuppinv)) $lastsuppinv = $this->coreFunctions->opentable("select '' as docno, '' as dateid, '' as client");
    return $lastsuppinv;
  }

  public function mobileLayout($config, $result)
  {
    $str = [];
    $printerLen = 32;

    // text sample
    array_push($str, $this->reporter->mrow(['Tenant:', '', 'C'], ['Jad Oelzon Parnaso', '', 'R']));
    array_push($str, $this->reporter->mrow(['Stall No.:'], ['1', '', 'R']));
    array_push($str, $this->reporter->mrow(['Location Code:'], ['7', '', 'R']));
    array_push($str, $this->reporter->mrow(['Date:'], ['2023-03-17', '', 'R']));
    array_push($str, $this->reporter->mrow(['Collector:'], ['Lorene Parnaso']));
    array_push($str, $this->reporter->mrow(['Ticket No.:'], ['123', '', 'R']));
    array_push($str, $this->reporter->mrow(['Outstanding Balance:'], ['15,000.00', '', 'C']));
    array_push($str, $this->reporter->mrow(['Rent:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Payment:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Net Balance:'], ['14,900.00']));
    array_push($str, $this->reporter->mrow([$this->othersClass->repeatstring('-', $printerLen)]));
    array_push($str, $this->reporter->mrow(['Payment:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Vat:'], ['12']));
    array_push($str, $this->reporter->mrow([$this->othersClass->repeatstring('-', $printerLen)]));
    array_push($str, $this->reporter->mrow(['TOTAL:'], ['100.00']));
    array_push($str, $this->reporter->mrow(['Thank you for your payment', '', 'C']));
    array_push($str, $this->reporter->mrow(['2023-03-17 22:07:00', '', 'C']));

    $string = $this->reporter->generatemreport($str, $printerLen);
    return ['view' => $string['view'], 'print' => $string['print'], 'printerLen' => $printerLen];
  }
}//end class