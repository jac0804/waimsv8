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

class item_min_max_listing
{
  public $modulename = 'Item Min Max Listing Report';
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

    $fields = ['radioprint', 'start', 'itemname', 'divsion', 'class', 'dwhname'];
    switch ($companyid) {
      case 39: //cbbsi
        array_push($fields, 'wh2');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'itemname.label', 'Item Code: A first few letters of the item');
        data_set($col1, 'itemname.readonly', false);

        data_set($col1, 'dwhname.label', 'Warehouse 1');
        data_set($col1, 'wh2.label', 'Warehouse 2');

        data_set($col1, 'dwhname.required', true);
        data_set($col1, 'wh2.required', true);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'start.label', 'Balance as of');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    unset($col1['class']['labeldata']);
    unset($col1['divsion']['labeldata']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['divsion']);
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'divsion.name', 'stockgrp');
    
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 39: //cbbsi
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        '' as end,

        '' as itemname,
        0 as classid,
        '' as classic,

        0 as groupid,
        '' as stockgrp,
        
        0 as whid,
        '' as wh,
        '' as whname,
        '' as dwhname,
        
        '' as wh2,
        '' as wh2name,
        0 as whid2,

        '' as client,
        '' as clientname";
        break;
    }


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
    $company = $config['params']['companyid'];

    switch ($company) {
      case 39: //cbbsi
        $result = $this->CBBSI_Layout($config);
        break;
    }

    return $result;
  }

  public function CBBSI_MAIN_QRY($config, $asof, $filter, $wh, $num, $whcode)
  {
    $qry = "select
    case when $num=1 then il.min else 0 end as minimum1,
    case when $num=1 then il.max else 0 end as maximum1,
    case when $num=2 then il.min else 0 end as minimum2,
    case when $num=2 then il.max else 0 end as maximum2,
    item.itemid,item.barcode, item.itemname,
    item.part,item.groupid, item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    ifnull(item.amt9,0) as cost,item.amt

    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid 
    left join client as wh on wh.clientid=stock.whid
    left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
    where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $wh 
    
    UNION ALL
    
    select 
    case when $num=1 then il.min else 0 end as minimum1,
    case when $num=1 then il.max else 0 end as maximum1,
    case when $num=2 then il.min else 0 end as minimum2,
    case when $num=2 then il.max else 0 end as maximum2,
    item.itemid,item.barcode, item.itemname,
    item.part,item.groupid, item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    ifnull(item.amt9,0) as cost,item.amt

    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid
    left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
    where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $wh 
    ";
    return $qry;
  }

  public function CBBSI_QRY($config)
  {
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $itemname    = $config['params']['dataparams']['itemname'];
    $classname  = $config['params']['dataparams']['classic'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $wh         = $config['params']['dataparams']['wh'];
    $wh2         = $config['params']['dataparams']['wh2'];

    $filter = " and item.isimport in (0,1)";
    $whfilter = "";
    $whfilter2 = "";
    if ($itemname != "") {
      $filter .= " and left(item.barcode," . strlen($itemname) . ") like '%" . $itemname . "%'";
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $whfilter .= " and wh.clientid=" . $whid;
    }
    if ($wh2 != "") {
      $whid2 = $config['params']['dataparams']['whid2'];
      $whfilter2 .= " and wh.clientid=" . $whid2;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }

    $query = "
    select barcode, itemname,a.uom,cost,sum(bal1) as bal1,sum(bal2) as bal2,sum(a.minimum1) as min1,sum(a.maximum1) as max1,sum(a.minimum2) as min2,sum(a.maximum2) as max2
      from (
        select barcode, itemname,ib.uom,cost,
        case when swh='$wh' then sum(qty-iss) else 0 end as bal1,
        case when swh='$wh2' then sum(qty-iss) else 0 end as bal2,
        ib.minimum1,ib.maximum1,ib.minimum2,ib.maximum2
        from (
          " . $this->CBBSI_MAIN_QRY($config, $asof, $filter, $whfilter, 1, $wh) . "
          union all
          " . $this->CBBSI_MAIN_QRY($config, $asof, $filter, $whfilter2, 2, $wh2) . "
        ) as ib
        left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
        group by barcode, itemname,ib.uom,cost,ib.swh,ib.minimum1,ib.maximum1,ib.minimum2,ib.maximum2
        order by itemname
    ) as a
    group by barcode,itemname,a.uom,cost";

    return $this->coreFunctions->opentable($query);
  }

  private function CBBSI_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $center    = $config['params']['center'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

    $itemname    = $config['params']['dataparams']['itemname'];
    $classname  = $config['params']['dataparams']['classic'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $clientname  = $config['params']['dataparams']['clientname'];

    if ($clientname == "") {
      $clientname = 'ALL';
    }
    if ($itemname == "") {
      $itemname = 'ALL';
    }
    if ($groupname == "") {
      $groupname = 'ALL';
    }
    if ($classname == "") {
      $classname = 'ALL';
    }

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM MIN MAX LISTING REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $whcode   = !empty($wh) ? $wh : "ALL";
    $asof     = !empty($asof) ? $asof : "";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of: ' . $asof, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item : ' . $itemname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Group : ' . $groupname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse: ' . $whname . ' ~ ' . $whcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class: ' . $classname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('WH1', '40', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('WH2', '40', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('BAL', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MIN', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('BAL', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MIN', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function CBBSI_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';

    $result = $this->CBBSI_QRY($config);
    $companyid  = $config['params']['companyid'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->CBBSI_displayHeader($config);

    foreach ($result as $key => $data) {
      $barcode = $data->barcode;

      $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->uom, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->bal1, $this->companysetup->getdecimal('price', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->min1, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->max1, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->bal2, $this->companysetup->getdecimal('price', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->min2, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->max2, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->CBBSI_displayHeader($config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class