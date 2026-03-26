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

class reorder_report
{
  public $modulename = 'Reorder Report';
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

    $fields = ['radioprint', 'start', 'ditemname', 'divsion', 'brand', 'model', 'categoryname', 'subcatname', 'class', 'dwhname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      case 14: //majesty
        array_splice($fields, 7, 1, 'part');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Division');
        data_set($col1, 'model.label', 'Generic');
        data_set($col1, 'class.label', 'Classification');
        data_set($col1, 'part.label', 'Principal');
        break;
      case 39: //cbbsi
        $fields = ['radioprint', 'start', 'itemname', 'divsion', 'brand', 'model', 'categoryname', 'subcatname', 'class', 'dwhname'];
        array_push($fields, 'wh2', 'dclientname');
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'divsion.type', 'coldel');
        data_set($col1, 'brand.type', 'coldel');
        data_set($col1, 'model.type', 'coldel');
        data_set($col1, 'categoryname.type', 'coldel');
        data_set($col1, 'subcatname.type', 'coldel');
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

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['brand']['labeldata']);
    unset($col1['model']['labeldata']);
    unset($col1['part']['labeldata']);

    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['brand']);
    unset($col1['labeldata']['model']);
    unset($col1['labeldata']['part']);

    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'brand.name', 'brandname');
    data_set($col1, 'model.name', 'modelname');
    data_set($col1, 'part.name', 'partname');

    data_set($col1, 'project.label', 'Item Group/Project');
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
        '' as ditemname,
        '' as shortcode,
        '' as barcode,
        0 as itemid,
        '' as itemname,
        0 as classid,
        '' as classic,
        '' as wh,
        '' as whname,
        '' as dwhname,
        '' as wh2,
        '' as wh2name,
        '' as whid2,
        '' as client,
        '' as clientname";
        break;

      default:
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        '' as end,
        0 as itemid,
        '' as ditemname,
        '' as barcode,
        0 as groupid,
        '' as stockgrp,
        0 as brandid,
        '' as brandname,
        0 as modelid,
        '' as modelname,
        0 as categoryid,
        '' as categoryname,
        '' as subcatname,
        0 as classid,
        '' as classic,
        0 as whid,
        '' as wh,
        '' as whname,
        '' as divsion,
        '' as brand,
        '' as model,
        '' as category,
        '' as subcat,
        '' as class,
        '' as part,
        0 as partid,
        '' as partname,
        '' as dwhname,
        '' as project, 
        0 as projectid, 
        '' as projectname,
        0 as deptid,
        '' as ddeptname, 
        '' as dept, 
        '' as deptname";

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
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function CBBSI_PENDING_PO_QRY($config, $wh)
  {
    $qry = "
    ifnull((
      select sum(ppo.poqty) as poqty from(
        select po.wh,po.itemid,sum(po.poqty) as poqty
        from (
          select head.wh,postock.itemid,qty-qa as poqty from postock as postock
          left join pohead as head on head.trno=postock.trno
          union all
          select head.wh,postock.itemid,qty-qa as poqty from hpostock as postock
          left join hpohead as head on head.trno=postock.trno
        ) as po group by po.wh,po.itemid
      ) as ppo
      where ppo.itemid=item.itemid and ppo.wh='$wh'
      ),0)";

    return $qry;
  }

  public function CBBSI_MAIN_QRY($config, $asof, $filter, $wh, $num, $whcode)
  {
    $qry = "select
    cntnum.center, item.critical,
    item.disc, 
    case when $num=1 then il.min else 0 end as minimum1,
    case when $num=1 then il.max else 0 end as maximum1,
    case when $num=2 then il.min else 0 end as minimum2,
    case when $num=2 then il.max else 0 end as maximum2,
    item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
    item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    ifnull(item.amt9,0) as cost,item.amt,
    cat.name as category1, subcat.name as subcatname,
    case when $num=1 then " . $this->CBBSI_PENDING_PO_QRY($config, $whcode) . " else 0 end as pendingpo1,
    case when $num=2 then " . $this->CBBSI_PENDING_PO_QRY($config, $whcode) . " else 0 end as pendingpo2

    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid          
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid
    left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat

    where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $wh 
    
    UNION ALL
    
    select cntnum.center, item.critical,
    item.disc, 
    case when $num=1 then il.min else 0 end as minimum1,
    case when $num=1 then il.max else 0 end as maximum1,
    case when $num=2 then il.min else 0 end as minimum2,
    case when $num=2 then il.max else 0 end as maximum2,
    item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
    item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    ifnull(item.amt9,0) as cost,item.amt,
    cat.name as category1,subcat.name as subcatname,
    case when $num=1 then " . $this->CBBSI_PENDING_PO_QRY($config, $whcode) . " else 0 end as pendingpo1,
    case when $num=2 then " . $this->CBBSI_PENDING_PO_QRY($config, $whcode) . " else 0 end as pendingpo2

    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid
    left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat

    where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $wh 
    ";
    return $qry;
  }

  public function CBBSI_QRY($config)
  {
    // QUERY
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $itemname    = $config['params']['dataparams']['itemname'];
    $wh         = $config['params']['dataparams']['wh'];
    $wh2         = $config['params']['dataparams']['wh2'];
    $class = $config['params']['dataparams']['classic'];

    $filter = " and item.isimport in (0,1)";
    $whfilter = "";
    $whfilter2 = "";

    if ($itemname != "") {
      $filter = $filter . " and left(item.barcode," . strlen($itemname) . ") like '%" . $itemname . "%'";
    }
    if ($class != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=" . $classid;
    }
    if ($wh != "") {
      $whfilter = $whfilter . " and wh.client='$wh'";
    }
    if ($wh2 != "") {
      $whfilter2 = $whfilter2 . " and wh.client='$wh2'";
    }

    $query = "
    select barcode, itemname,a.uom,cost,sum(bal1) as bal1,sum(bal2) as bal2,a.critical,sum(a.minimum1) as min1,sum(a.maximum1) as max1,sum(a.minimum2) as min2,sum(a.maximum2) as max2,sum(a.pendingpo1) as pendingpo1,sum(a.pendingpo2) as pendingpo2,
    ifnull(
      (select rr.clientid from rrstatus as rr
      left join cntnum as num on num.trno=rr.trno
      left join item as i on i.itemid=rr.itemid
      where num.doc='RR' and i.barcode=a.barcode and rr.clientid<>0
      order by rr.dateid desc
      limit 1)
    ,0) as lastsupplier 
      from (
        select barcode, itemname,ib.uom,cost,
        

        case when swh='$wh' then sum(qty-iss) else 0 end as bal1,
        case when swh='$wh2' then sum(qty-iss) else 0 end as bal2,

        ib.critical,ib.minimum1,ib.maximum1,ib.minimum2,ib.maximum2,ib.pendingpo1,ib.pendingpo2
        from (
          " . $this->CBBSI_MAIN_QRY($config, $asof, $filter, $whfilter, 1, $wh) . "
          union all
          " . $this->CBBSI_MAIN_QRY($config, $asof, $filter, $whfilter2, 2, $wh2) . "
        ) as ib
        left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
        group by barcode, itemname,ib.uom,cost,ib.swh,ib.critical,ib.minimum1,ib.maximum1,ib.minimum2,ib.maximum2,ib.pendingpo1,ib.pendingpo2
        
        order by itemname

      ) as a
      group by barcode,itemname,a.uom,cost,a.critical";

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
    $barcode    = $config['params']['dataparams']['barcode'];
    $classname  = $config['params']['dataparams']['classic'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $clientname  = $config['params']['dataparams']['clientname'];

    if ($clientname == "") {
      $clientname = 'ALL';
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
    $str .= $this->reporter->col('REORDER REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $class    = !empty($classname) ? $classname : "ALL";
    $item     = !empty($barcode) ? $barcode : "ALL";
    $whcode   = !empty($wh) ? $wh : "ALL";
    $asof     = !empty($asof) ? $asof : "";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code: ' . $item, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Supplier : ' . $clientname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Class : ' . $class, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse : ' . $whname . ' ~ ' . $whcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('WH1', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('WH2', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '45', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '45', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '45', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BAL', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MIN', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PENDING PO', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('BAL', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MIN', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PENDING PO', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('LAST PUR DATE', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LAST PUR QTY', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LAST SUPP', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

    $str .= $this->reporter->col('PO', '45', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('STR', '45', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('PO', '45', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('REMARKS', '50', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');

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
    $barcode    = $config['params']['dataparams']['barcode'];

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
      $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '$barcode'");
      $lastsup = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid = '$data->lastsupplier'");

      $query1 = "
      select rr.trno as rrtrno,rr.line as rrline,num.svnum from rrstatus as rr
      left join item as i on i.itemid=rr.itemid
      left join cntnum as num on num.trno=rr.trno
      where rr.itemid=$itemid and num.svnum<>0
      order by rr.dateid desc,rr.trno desc, rr.line desc
      limit 1";
      $result2 = json_decode(json_encode($this->coreFunctions->opentable($query1)), true);

      if (!empty($result2)) {
        $query2 = "
        select head.dateid,stock.rrqty
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        where stock.trno = " . $result2[0]['rrtrno'] . " and stock.line = " . $result2[0]['rrline'] . "
        union all
        select head.dateid,stock.rrqty
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        where stock.trno = " . $result2[0]['rrtrno'] . " and stock.line = " . $result2[0]['rrline'] . "
        ";
        $result3 = json_decode(json_encode($this->coreFunctions->opentable($query2)), true);
      }
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->uom, '40', null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, $this->companysetup->getdecimal('price', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->bal1, $this->companysetup->getdecimal('price', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->min1, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->max1, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->pendingpo1, $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->bal2, $this->companysetup->getdecimal('price', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->min2, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col($data->max2, '40', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($data->pendingpo2, $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '', '');

      if (!empty($result3)) {
        $str .= $this->reporter->col($result3[0]['dateid'], '50', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col(number_format($result3[0]['rrqty'], $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      } else {
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      }
      $str .= $this->reporter->col($lastsup, '50', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');


      $str .= $this->reporter->col('', '45', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '45', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '45', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '');

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

  public function reportDefault($config)
  {
    // QUERY
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcatname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $companyid = $config['params']['companyid'];

    $filter = " and item.isimport in (0,1)";
    $filter1 = "";
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .=  " and item.groupid=" . $groupid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category = '$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat = '$subcat'";
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
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

    if ($companyid == 14) { //majesty
      $partname      = $config['params']['dataparams']['partname'];
      if ($partname != "") {
        $partid = $config['params']['dataparams']['partid'];
        $filter .= " and item.part=" . $partid;
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd   
      $project = $config['params']['dataparams']['project'];
      $deptname = $config['params']['dataparams']['ddeptname'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid=" . $projectid;
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    } else {
      $filter1 .= "";
    }

    $filter3 = 'critical';
    if ($companyid == 14 || $companyid == 47) { //majesty,kitchenstar
      $filter3 = 'minimum';
    }

    if ($companyid == 47) { //kitchenstar
      $query = "select barcode, itemname,ib.uom,cost,sum(qty-iss) as balance,ib.critical,ib.minimum,ib.maximum
      from (select cntnum.center, item.critical,
      item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
      item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
      wh.clientname as whname, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost, item.amt,
      cat.name as category1, subcat.name as subcatname
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid          
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part
      left join client as wh on wh.clientid=stock.whid
      left join itemlevel as il on il.itemid = item.itemid 
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat         
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1  and item.isofficesupplies=0
      
      UNION ALL

      select cntnum.center, item.critical,
      item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
      item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
      wh.clientname as whname, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,item.amt,
      cat.name as category1,subcat.name as subcatname
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part
      left join client as wh on wh.clientid=stock.whid
      left join itemlevel as il on il.itemid = item.itemid 
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
      ) as ib
      left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
      group by barcode, itemname,ib.uom,cost,ib.critical,ib.minimum,ib.maximum
      having (case when sum(qty-iss) <= " . $filter3 . " then 1 else 0 end) in (1)
      order by itemname";
    } else {
      $query = "select barcode, itemname,ib.uom,cost,sum(qty-iss) as balance,ib.critical,ib.minimum,ib.maximum
      from (select cntnum.center, item.critical,
      item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
      item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
      wh.clientname as whname, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost, item.amt,
      cat.name as category1, subcat.name as subcatname
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid          
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part
      left join client as wh on wh.clientid=stock.whid
      left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1  and item.isofficesupplies=0
      
      UNION ALL

      select cntnum.center, item.critical,
      item.disc, il.min as minimum,il.max as maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode, item.itemname,item.model,
      item.part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
      wh.clientname as whname, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,item.amt,
      cat.name as category1,subcat.name as subcatname
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part
      left join client as wh on wh.clientid=stock.whid
      left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
      ) as ib
      left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
      group by barcode, itemname,ib.uom,cost,ib.critical,ib.minimum,ib.maximum
      having (case when sum(qty-iss) <= " . $filter3 . " then 1 else 0 end) in (1)
      order by itemname";
    }
    
    return $this->coreFunctions->opentable($query);
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
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];

    if ($companyid == 10 || $companyid == 12) { //afti,afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
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
    $str .= $this->reporter->col('REORDER REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $division = !empty($groupname) ? $groupname : "ALL";
    $brand    = !empty($brandname) ? $brandname : "ALL";
    $class    = !empty($classname) ? $classname : "ALL";
    $model    = !empty($modelname) ? $modelname : "ALL";
    $item     = !empty($barcode) ? $barcode : "ALL";
    $whcode   = !empty($wh) ? $wh : "ALL";
    $asof     = !empty($asof) ? $asof : "";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code: ' . $item, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Division : ' . $division, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Brand : ' . $brand, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('Generic : ' . $model, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Model : ' . $model, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->col('Class : ' . $class, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse : ' . $whname . ' ~ ' . $whcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As Of : ' . $asof, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
    }

    if ($companyid == 14) { //majesty
      if ($subcatname == '') {
        $str .= $this->reporter->col('Principal: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Principal : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
    } else {
      if ($subcatname == '') {
        $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      } else {
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', $padding, $margin);
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEMNAME', '200', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');

    if ($companyid == 47) { //kitchenstar
      if (!$viewcost) { //walang access
        $str .= $this->reporter->col('UNIT', '88', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('QTY', '87', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
      } else { //may access
        $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('COST', '75', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
      }
    } else { //default
      $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('COST', '75', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    }

    $str .= $this->reporter->col('LQTY', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    if ($companyid != 14) { //not majesty
      $str .= $this->reporter->col('CRITICAL', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    }
    $str .= $this->reporter->col('MIN', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('MAX', '50', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PENDING ORDER', '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('LAST PO DATE', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $barcode    = $config['params']['dataparams']['barcode'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    foreach ($result as $key => $data) {
      $barcode = $data->barcode;
      $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '$barcode'");
      $query = "select sum(qty - qa) as pending, sum(qa) as served from hpostock where itemid = $itemid";
      $result1 = $this->coreFunctions->opentable($query);;
      $data->pending = $result1[0]->pending;
      $data->served = $result1[0]->served;

      $query1 = "
      select head.trno, head.dateid as lpodate, stock.qty as lpoqty
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      where itemid = $itemid and head.doc = 'RR'
      order by head.dateid desc, docno
      limit 1
    ";
      $result2 = $this->coreFunctions->opentable($query1);

      if ($data->balance <= $data->minimum || $data->balance == 0) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');

        if ($companyid == 47) { //kitchenstar
          if (!$viewcost) { //walang access
            $str .= $this->reporter->col($data->uom, '88', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->balance, 2), '87', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          } else { //may access
            $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($data->balance, 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          }
        } else { //default
          $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col(number_format($data->balance, 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        }

        foreach ($result2 as $key => $data1) {
          $data->trno = $data1->trno;

          if (isset($data1->lpodate)) {
            $lpodate = $data1->lpodate;
          } else {
            $lpodate = '';
          }

          if (isset($data1->lpoqty)) {
            $lpoqty = $data1->lpoqty;
          } else {
            $lpoqty = 0;
          }
        }

        if (empty($result2)) {
          $lpodate = '';
          $lpoqty = 0;
        }

        $str .= $this->reporter->col(number_format($lpoqty, 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        if ($companyid != 14) { //not majesty
          $str .= $this->reporter->col(number_format(intval($data->critical), 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        }

        $str .= $this->reporter->col(number_format($data->minimum, 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data->maximum, 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data->pending, 2), '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        if ($lpodate != '') {
          $str .= $this->reporter->col(date('m/d/Y', strtotime($lpodate)), '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col($lpodate, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        }


        $str .= $this->reporter->endrow();
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->addline();
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class