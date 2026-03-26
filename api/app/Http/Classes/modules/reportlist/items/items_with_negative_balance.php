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

class items_with_negative_balance
{
  public $modulename = 'Items With Negative Balance';
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
    $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'start.label', 'Balance as of');
    data_set($col1, 'categoryname.action', 'lookupcategoryitem');
    data_set($col1, 'categoryname.name', 'category');
    data_set($col1, 'luom.action', 'replookupuom');

    unset($col1['model']['labeldata']);
    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['model']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'model.name', 'modelname');
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');

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
      0 as clientid,
      '' as client,
      '' as clientname,
      0 as itemid,
      '' as itemname,
      '' as barcode,
      0 as groupid,
      '' as stockgrp,
      0 as brandid,
      '' as brandname,
      0 as classid,
      '' as classic,
      '' as categoryname,
      0 as modelid,
      '' as modelname,
      0 as whid,
      '' as wh,
      '' as whname,
      '' as ditemname,
      '' as divsion,
      '' as brand,
      '' as model,
      '' as class,
      '' as category,
      '' as dwhname,
      '' as uom");
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
    $result = $this->reportDefaultLayout_NONE($config);
    return $result;
  }

  public function reportDefault($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY($config)
  {
    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $barcode    = $config['params']['dataparams']['barcode'];
    $classname  = $config['params']['dataparams']['classic'];
    $category  = $config['params']['dataparams']['category'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];

    $order = " order by category,itemname";
    $filter = " and item.isimport in (0,1)";
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($modelname != "") {
      $modelid = $config['params']['dataparams']['modelid'];
      $filter .= " and item.model=" . $modelid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($category != "") {
      $filter .= " and item.category='" . $category . "'";
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    $query = "
    select category, barcode, itemname, part, uom.uom,uom.factor, 
    sum(qty-iss)/(case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) as balance, ib.amt
    from (
      select item.itemid, item.barcode, item.itemname, item.category, item.part, uom.uom, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,
      item.amt
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter
      
      UNION ALL

      select item.itemid, item.barcode, item.itemname, item.category, item.part, uom.uom, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,
      item.amt
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter
    ) as ib
    left join uom on uom.itemid=ib.itemid
    group by category, barcode, itemname, part, uom.uom, uom.factor, ib.amt
    HAVING SUM(qty-iss) < 0" . $order;

    return $query;
  }

  private function default_displayHeader_NONE($config)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $wh         = $config['params']['dataparams']['wh'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $str = '';
    $layoutsize = '1000';
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEMS WITH NEGATIVE BALANCE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $wh, null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('COUNT', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '');

    return $str;
  }

  public function reportDefaultLayout_NONE($config)
  {
    $result = $this->reportDefault($config);
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 38;
    $page = 40;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_NONE($config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if ($data->part != 0 || $data->part != null) {
        if (strtoupper($part) == strtoupper($data->part)) {
          $part = "";
        } else {
          $part = strtoupper($data->part);
        }
      } else {
        $part = "";
      }

      if ($data->category != 0 || $data->category != null) {
        if (strtoupper($scatgrp) == strtoupper($data->category)) {
          $scatgrp = "";
        } else {
          $scatgrp = strtoupper($data->category);
        }
      } else {
        $scatgrp = "";
      }

      $balance = number_format($data->balance, 2);
      if (isset($data->amt)) {
        $isamt = number_format($data->amt, 2);
        if ($isamt == 0) {
          $isamt = '-';
        }
      } else {
        $isamt = '-';
        $data->amt = 0;
      }

      $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($scatgrp, '100', null, false, '1px solid ', '', 'R', $font, $font_size, 'Bi', '', '');
      $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $totalext = $data->balance * $data->amt;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, '', '', '');
      $scatgrp = strtoupper($data->category);
      $part = strtoupper($data->part);
      $grandtotal = $grandtotal + $totalext;
      $totalbalqty = $totalbalqty + $data->balance;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader_NONE($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'r', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class