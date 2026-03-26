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


class non_moving_items
{
  public $modulename = 'Non Moving Items';
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
    $fields = ['radioprint', 'start', 'end', 'ditemname', 'divsion', 'brand', 'class', 'dwhname', 'year'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'year.label', 'Top # of Items');
    data_set($col1, 'year.required', false);

    unset($col1['divsion']['labeldata']);
    unset($col1['brand']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['brand']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'brand.name', 'brandname');
    data_set($col1, 'class.name', 'classic');

    $fields = ['radioposttype', 'radioreportitemtype'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-30) as start,
    left(now(),10) as end,
    0 as itemid,
    '' as ditemname,
    '' as barcode,
    0 as groupid,
    '' as stockgrp,
    0 as brandid,
    '' as brandname,
    0 as classid,
    '' as classic,
    0 as whid,
    '' as wh,
    '' as whname,
    '0' as posttype,
    '(0,1)' as itemtype,
    '' as year,
    '' as divsion,
    '' as brand,
    '' as class,
    '' as dwhname
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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 40: // CDO
        $this->reportParams['orientation'] = 'p';
        $result = $this->CDO_Layout($config);
        break;

      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($companyid) {
      case 40: //CDO
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->CDO_QUERY_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->CDO_QUERY_UNPOSTED($config);
            break;
        }
        break;

      default:
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->default_QUERY_POSTED($config);
            break;
          case '1': // UNPOSTED
            $query = $this->default_QUERY_UNPOSTED($config);
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function CDO_QUERY_POSTED($config)
  {
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname = $config['params']['dataparams']['classic'];
    $brandname = $config['params']['dataparams']['brandname'];
    $groupname = $config['params']['dataparams']['stockgrp'];
    $barcode   = $config['params']['dataparams']['barcode'];
    $wh        = $config['params']['dataparams']['wh'];
    $loc       = $config['params']['dataparams']['itemtype'];
    $top       = $config['params']['dataparams']['year'];

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid    = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid    = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }
    if ($top != "") {
      $top = " limit " . $top . "";
    }

    // stock#=item.barcode
    // partnumber=item.partno
    // superceeding=item.body
    // part name=item.itemname
    // category=itemcategory.name
    // cost=la/glstock.cost
    // srp=la/glstock.amt

    $query = "select item.itemid, item.barcode as stockno, item.partno as partno, item.body as superceeding, item.itemname as partname, 
    ig.name as category, (select cost from rrstatus where itemid = stock.itemid order by dateid desc limit 1) as cost, 
    item.amt as srp, sum(stock.iss) as sold, sum(stock.ext) as totalext
    from glhead as head 
    left join glstock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid 
    left join itemcategory as ig on ig.line = item.category
    where head.dateid between '" . $start . "' and '" . $end . "' and ifnull(item.itemid, '') <> '' $filter
    group by item.itemid, item.barcode, item.partno, item.body, item.itemname, ig.name, stock.itemid, item.amt
    having sum(stock.iss) = 0
    order by stockno, sold asc" . $top;

    return $query;
  }

  public function CDO_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.itemid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid    = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid    = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }
    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    $query = "select item.itemid, item.barcode as stockno, item.partno as partno, item.body as superceeding, item.itemname as partname, 
          ig.name as category, (select cost from rrstatus where itemid = stock.itemid order by dateid desc limit 1) as cost, 
          item.amt as srp, sum(stock.iss) as sold, sum(stock.ext) as totalext
          from lahead as head 
          left join lastock as stock on head.trno = stock.trno
          left join item on item.itemid = stock.itemid 
          left join itemcategory as ig on ig.line = item.category
          where head.doc in ('SJ','MJ','CI') and head.dateid between '" . $start . "' and '" . $end . "' and ifnull(item.itemid, '') <> '' $filter
          group by item.itemid, item.barcode, item.partno, item.body, item.itemname, ig.name, stock.itemid, item.amt
          having sum(stock.iss) = 0
          order by stockno, sold asc" . $top;
    return $query;
  }

  public function default_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid    = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid    = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }
    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    $query = "select * FROM (SELECT item.sizeid AS size,'P' AS tr, IFNULL(stockgrp.stockgrp_name,'') AS groupid, 
    IFNULL(frontend_ebrands.brand_desc,'') AS brand,IFNULL(parts.part_name,'') AS part, IFNULL(mm.model_name,'') AS model,item.body,
    IFNULL(cc.cl_name,'') AS class,wh.client AS whcode,IFNULL(wh.clientname,'') AS whname,
    IFNULL(item.itemname,'') AS itemname,item.barcode, stock.uom, SUM(stock.iss) AS qty
    FROM glhead AS head 
    LEFT JOIN glstock AS stock ON head.trno = stock.trno
    LEFT JOIN item ON item.itemid=stock.itemid 
    LEFT JOIN stockgrp_masterfile AS stockgrp ON stockgrp.stockgrp_id = item.groupid
    LEFT JOIN model_masterfile AS mm ON mm.model_id = item.model
    LEFT JOIN item_class AS cc ON cc.cl_id = item.class
    LEFT JOIN cntnum ON cntnum.trno=head.trno
    LEFT JOIN client AS wh ON wh.clientid=stock.whid
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join part_masterfile as parts on parts.part_id = item.part
    WHERE head.dateid BETWEEN '$start' and '$end' AND IFNULL(item.itemid,'')<>'' $filter
    GROUP BY item.sizeid,stockgrp.stockgrp_name, 
    frontend_ebrands.brand_desc,parts.part_name,mm.model_name,item.body,cc.cl_name,wh.client,wh.clientname,
    item.itemname,item.barcode, stock.uom) AS cute
    WHERE qty = 0
    ORDER BY part,brand,qty ASC $top";

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];
    $isqty = "stock.iss";

    $filter = " and item.isimport in $loc";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($brandname != "") {
      $brandid    = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=" . $brandid;
    }
    if ($classname != "") {
      $classid    = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter = $filter . " and stock.whid=" . $whid;
    }
    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    $query = "select size,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,model,body,size from (
    select item.sizeid as size,'U' as tr, ifnull(stockgrp.stockgrp_name,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part, ifnull(mm.model_name,'') as model,item.body,
    ifnull(cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom
    from lahead as head left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as mm on mm.model_id = item.model
    left join item_class as cc on cc.cl_id = item.class
    left join cntnum on cntnum.trno=head.trno
    left join client as wh on wh.clientid=stock.whid
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join part_masterfile as parts on parts.part_id = item.part
    where head.doc='SJ' and head.dateid between '$start' and '$end' and ifnull(item.itemid,'')<>'' $filter
    group by item.sizeid,stockgrp.stockgrp_name, 
    frontend_ebrands.brand_desc,parts.part_name,mm.model_name,item.body,cc.cl_name,wh.client,wh.clientname,
    item.itemname,item.barcode, stock.uom) as FM
    WHERE qty = 0
    order by part,brand,qty asc $top";

    return $query;
  }

  private function CDO_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('NON MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else {
      $posttype = 'Unposted';
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($barcode == '') {
      $str .= $this->reporter->col('Item : ALL', '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($brandname == '') {
      $str .= $this->reporter->col('Brand : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($classname == '') {
      $str .= $this->reporter->col('Class : ALL', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Class' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');

    switch ($itemtype) {
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }

    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    if ($whname == '') {
      $str .= $this->reporter->col('WH : ALL', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('WH : ' . $whname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('#', '30', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('STOCK #', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PART NUMBER', '130', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('SUPERCEEDING', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('PART NAME', '290', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('CATEGORY', '70', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '70', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '70', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('SRP', '70', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '70', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');

    return $str;
  }

  public function CDO_Layout($config)
  {
    $border = '1px solid';
    $font_size = '10';

    $result = $this->reportDefault($config);

    $pagecount = 30;
    $page = 30;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->CDO_displayHeader($config);
    
    $count = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {
      $count++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($count, '30', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->stockno, '100', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->partno, '130', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->superceeding, '100', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');

      $str .= $this->reporter->col($data->partname, '290', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->category, '70', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '', '');

      $str .= $this->reporter->col(number_format($this->getBal($config, $data->itemid), 2), '70', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), '70', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->srp, 2), '70', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->totalext, 2), '70', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '', '');

      $grandtotal += $data->totalext;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->CDO_displayHeader($config);
        $page = $page + $pagecount;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('', '290', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '70', null, false, $border, 'T', 'L', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '70', null, false, $border, 'T', 'R', 'Verdana', $font_size, 'B', '', '', '');

    $str .= $this->reporter->endtable();
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

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $whname         = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

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
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('SLOW MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else {
      $posttype = 'Unposted';
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($barcode == '') {
      $str .= $this->reporter->col('Item : ALL', '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($brandname == '') {
      $str .= $this->reporter->col('Brand : ALL', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    if ($classname == '') {
      $str .= $this->reporter->col('Class : ALL', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Class' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');

    switch ($itemtype) {
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }

    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PRODUCT CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PRODUCT DESCRIPTION', '500', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font_size = '10';

    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $part = "";
    $brand = "";
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($part == strtoupper($data->part)) {
        $part = "";
        if (strtoupper($brand) == strtoupper($data->brand)) {
          $brand = "";
        } else {
          $brand = strtoupper($data->brand);
        }
      } else {
        $part = $data->part;
        if (strtoupper($part) == strtoupper($data->part)) {
          $part = "";
        } else {
          $part = strtoupper($data->part);
        }
      }
      $str .= $this->reporter->col($part, '150', null, false, $border, '', 'L', 'Verdana', $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '500', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($brand, '150', null, false, $border, '', 'C', 'Verdana', $font_size, 'Bi', '', '');
      $str .= $this->reporter->col('', '500', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', 'Verdana', $font_size, 'B', '', '');

      $item_desc = $data->itemname;

      if ($data->brand != "") {
        $item_desc = $data->brand . " " . $item_desc;
      } //end if

      if ($data->model != "") {
        $item_desc = $item_desc . " " . $data->model;
      } //end if

      if ($data->size != "") {
        $item_desc = $item_desc . " " . $data->size;
      } //end if        

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($item_desc, '500', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');

      $brand = strtoupper($data->brand);
      $part = $data->part;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function getBal($config, $itemid)
  {
    $wh = $config['params']['dataparams']['wh'];
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $whid = '';
    if ($wh != '') $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $whfilter = "";
    $whfilter2 = "";
    if ($whid != '') {
      $whfilter = " and head.wh='" . $wh . "'";
      $whfilter2 = " and head.whid='" . $whid . "'";
    }
    $asof = " and head.dateid<='$end'";
    $qry = "select sum(qty-iss) as value from (
      select stock.qty, stock.iss from lahead as head left join lastock as stock on stock.trno=head.trno where stock.itemid=" . $itemid . " " . $whfilter . " $asof
      union all
      select stock.qty, stock.iss from glhead as head left join glstock as stock on stock.trno=head.trno where stock.itemid=" . $itemid . " " . $whfilter2 . " $asof
    ) as t";
    $bal = $this->coreFunctions->datareader($qry);
    if (empty($bal)) $bal = 0;
    return $bal;
  }
}//end class