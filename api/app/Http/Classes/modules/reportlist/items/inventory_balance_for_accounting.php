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

class inventory_balance_for_accounting
{
  public $modulename = 'Inventory Balance For Accounting';
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
    $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'dwhname'];

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

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'start.label', 'Balance as of');
    // data_set($col1, 'categoryname.action', 'lookupcategoryitem');
    // data_set($col1, 'categoryname.name', 'category');
    data_set($col1, 'luom.action', 'replookupuom');

    unset($col1['divsion']['labeldata']);
    unset($col1['model']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['model']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'model.name', 'modelname');
    data_set($col1, 'class.name', 'classic');

    $fields = ['radioreportitemtype', 'radiorepitemstock', 'radiorepamountformat'];
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
    '(0)' as itemtype,
    '(0,1)' as itemstock,
    'none' as amountformat,
    '' as ditemname,
    '' as divsion,
    '' as brand,
    '' as model,
    '' as class,
    '' as category,
    '' as dwhname,
    '' as uom,
    0 as projectid,
    '' as project,
    '' as projectname
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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $amountformat   = $config['params']['dataparams']['amountformat'];

    switch ($amountformat) {
      case 'isamt':
        $result = $this->reportDefaultLayout_SELLING_PRICE($config);
        break;
      case 'rrcost':
        $result = $this->reportDefaultLayout_LATEST_COST($config);
        break;
      case 'none':
        $result = $this->reportDefaultLayout_NONE($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    // QUERY
    switch ($companyid) {
      case '1': //vitaline
      case '23': //labsol cebu
        $query = $this->VITALINE_QUERY($config);
        break;
      case '10': //afti
      case '12': //afti usd
        $query = $this->afti_query($config);
        break;
      default:
        $query = $this->DEFAULT_QUERY($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY($config)
  {
    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    // $client     = $config['params']['dataparams']['client'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $class  = $config['params']['dataparams']['classic'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $group  = $config['params']['dataparams']['stockgrp'];
    $brand    = $config['params']['dataparams']['brand'];
    $model  = $config['params']['dataparams']['model'];
    $wh         = $config['params']['dataparams']['wh'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';

    $order = " order by category,itemname";
    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
    if ($brand != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($model != "") {
      $modelid = $config['params']['dataparams']['modelid'];
      $filter .= " and item.model=" . $modelid;
    }
    if ($class != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($group != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    } else {
      $filter .= " and wh.clientname not like '%DEMO%'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj = ', projname';
      $proj1 = ', proj.name as projname';
      $proj2 = 'left join projectmasterfile as proj on proj.line=item.projectid';
      $proj3 = 'left join frontend_ebrands as brand on brand.brandid=item.brand left join iteminfo as i on i.itemid = item.itemid ';
    } else {
      $proj = '';
      $proj1 = '';
      $proj2 = '';
      $proj3 = '';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and head.projectid = $projectid";
      }
      $repitemcol = "concat(ifnull(item.itemname,''),' ',ifnull(modelgrp.model_name,''),' ',ifnull(brand.brand_desc,''),' ',left(ifnull(i.itemdescription,''),50)) as itemname,";
    } else {
      $filter1 .= "";
      $repitemcol = "item.itemname as itemname,";
    }

    // [JIKS] [01.23.2021] -- REVISED QUERIES
    $query = "select ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname,ifnull(brandname,'') as brand, partname,
    modelname,model, part,brand,sizeid,body, class, ib.uom,
    sum(qty-iss) as balance,
    cost,ib.amt, loc, expiry, serialno $proj
    from (
    select item.disc, item.minimum,item.maximum,cat.name as category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode," . $repitemcol . " item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,
    item.amt, stock.loc, stock.expiry, iinfo.serialno $proj1
    from (((lahead as head
    left join lastock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join iteminfo as iinfo on iinfo.itemid = item.itemid
    $proj2
    $proj3
    where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and item.islabor =0 $filter $filter1
    
    UNION ALL

    select item.disc, item.minimum,item.maximum,cat.name as category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.barcode, " . $repitemcol . " item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,
    item.amt, stock.loc, stock.expiry, iinfo.serialno $proj1
    from (((glhead as head
    left join glstock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join iteminfo as iinfo on iinfo.itemid = item.itemid
    $proj2
    $proj3
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and item.islabor =0  $filter $filter1) as ib
    group by ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname, partname,
    modelname,model, part,ib.brand,sizeid,body, class, ib.uom, loc, expiry, serialno $proj,
    ib.cost,ib.amt having (case when sum(qty-iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

    return $query;
  }

  private function afti_query($config)
  {
    $asof         = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $barcode      = $config['params']['dataparams']['barcode'];
    $class        = $config['params']['dataparams']['classic'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $category     = $config['params']['dataparams']['category'];
    $group        = $config['params']['dataparams']['stockgrp'];
    $brand        = $config['params']['dataparams']['brandname'];
    $model        = $config['params']['dataparams']['model'];
    $wh           = $config['params']['dataparams']['wh'];
    $itemstock    = $config['params']['dataparams']['itemstock'];
    $itemtype     = $config['params']['dataparams']['itemtype'];
    $uom          = $config['params']['dataparams']['uom'];
    $companyid    = $config['params']['companyid'];

    $order = " order by projname";
    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
    if ($uom != "") {
      $filter .= " and stock.uom='$uom'";
    }
    if ($brand != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($model != "") {
      $modelid = $config['params']['dataparams']['modelid'];
      $filter .= " and item.model=" . $modelid;
    }
    if ($class != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($group != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }

    //afti, afti usd
    if ($companyid == 10 || $companyid == 12) {
      $project = $config['params']['dataparams']['project'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and head.projectid=" . $projectid;
      }
    } else {
      $filter1 .= "";
    }

    // [JIKS] [01.23.2021] -- REVISED QUERIES
    $query = "select ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname,ifnull(brandname,'') as brand, partname,
    modelname,model, part,brand,sizeid,body, class, ib.uom,
    sum(qty-iss) as balance,
    sum(costin-costout)/sum(qty-iss) as cost,ib.amt, loc, expiry, serialno ,projname
    from (
    select stock.trno, stock.line, item.disc, item.minimum,item.maximum,cat.name as category,
    fbrand.brand_desc as brandname,
    ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.partno as barcode,left(ifnull(i.itemdescription,''),50) as itemname, item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
    wh.clientname as whname, (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,(case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,case when stock.qty > 0 then (stock.cost*(case when uom.factor>1 then stock.qty/uom.factor else stock.qty end))*uom.factor else 0 end as costin,
    case when stock.iss > 0 then (stock.cost*(case when uom.factor>1 then stock.iss/uom.factor else stock.iss end))*uom.factor else 0 end as costout,
    item.amt, stock.loc, stock.expiry, iinfo.serialno , proj.name as projname
    from (((lahead as head
    left join lastock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join iteminfo as iinfo on iinfo.itemid = item.itemid
    left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
    left join uom on uom.itemid = item.itemid and  uom.isdefault =1 
    left join projectmasterfile as proj on proj.line=stock.projectid
    left join frontend_ebrands as brand on brand.brandid=item.brand left join iteminfo as i on i.itemid = item.itemid 
    where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and stock.iss<>0 and item.islabor =0 $filter $filter1
    
    UNION ALL

    select stock.trno, stock.line, item.disc, item.minimum,item.maximum,cat.name as category,
    fbrand.brand_desc as brandname,
    ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.partno as barcode, left(ifnull(i.itemdescription,''),50) as itemname,  item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
    wh.clientname as whname, (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,(case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,case when stock.qty > 0 then (stock.cost*(case when uom.factor>1 then stock.qty/uom.factor else stock.qty end))*uom.factor else 0 end as costin,
    case when stock.iss > 0 then (stock.cost*(case when uom.factor>1 then stock.iss/uom.factor else stock.iss end))*uom.factor else 0 end as costout,
    item.amt, stock.loc, stock.expiry, iinfo.serialno , proj.name as projname
    from (((glhead as head
    left join glstock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join cntnum on cntnum.trno=head.trno
    left join itemcategory as cat on cat.line = item.category
    left join iteminfo as iinfo on iinfo.itemid = item.itemid
    left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
    left join uom on uom.itemid = item.itemid and  uom.isdefault =1 
    left join projectmasterfile as proj on proj.line=stock.projectid
    left join frontend_ebrands as brand on brand.brandid=item.brand left join iteminfo as i on i.itemid = item.itemid 
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and item.islabor =0 $filter $filter1
    ) as ib
    group by ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname, partname,
    modelname,model, part,ib.brand,sizeid,body, class, ib.uom, loc, expiry, serialno, projname,
    ib.amt having (case when sum(qty-iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;
//uom.uom = item.uom
    return $query;
  }

  public function VITALINE_QUERY($config)
  {
    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    // $client     = $config['params']['dataparams']['client'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $uom        = $config['params']['dataparams']['uom'];
    $class  = $config['params']['dataparams']['classic'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $group  = $config['params']['dataparams']['stockgrp'];
    $brand    = $config['params']['dataparams']['brand'];
    $brand  = $config['params']['dataparams']['brandname'];
    $model  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $format = '';

    $order = " order by category,itemname";
    $filter = " and item.isimport in $itemtype";
    if ($brand != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($model != "") {
      $modelid = $config['params']['dataparams']['modelid'];
      $filter .= " and item.model=" . $modelid;
    }
    if ($class != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class='$classid'";
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter .= " and stock.itemid=" . $itemid;
    }
    if ($group != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid='$groupid'";
    }
    if ($wh != "") {
      $whid = $config['params']['dataparams']['whid'];
      $filter .= " and stock.whid=" . $whid;
    }
    if ($uom != "") {
      $filter .= " and stock.uom='$uom'";
    }

    switch ($amountformat) {
      case 'isamt':
        $format = ', ib.amt ';
        break;
      case 'rrcost':
        $format = ',cost ';
        break;
      default:
        $format = '';
        break;
    }

    $query = "select ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname,ifnull(brandname,'') as brand, partname,
    modelname,model, part,brand,sizeid,body, class, ib.uom,
    sum(qty-iss)/(case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) as balance
    " . $format . " ,loc, expiry
    from (
    select item.disc, item.minimum,item.maximum,item.category,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.barcode, item.itemname,item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    stock.cost,
    item.amt, stock.loc, stock.expiry
    from (((glhead as head
    left join glstock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join uom as uom on uom.itemid=item.itemid and uom.uom = stock.uom
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join cntnum on cntnum.trno=head.trno
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter) as ib
    left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
    group by ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname, partname,
    modelname,model, part,ib.brand,sizeid,body, class, ib.uom, loc, expiry,
    uom.factor " . $format . " having (case when sum(qty-iss)/(case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

    return $query;
  }

  private function default_displayHeader_SELLING_PRICE($config)
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
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $whname     = $config['params']['dataparams']['whname'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    if ($brandname == '') {
      $brandname = "ALL";
    }
    if ($modelname == '') {
      $modelname = "ALL";
    }
    if ($whname == '') {
      $whname = "ALL";
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
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY BALANCE FOR ACCOUNTING', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

    switch ($itemtype) {
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

    switch ($itemstock) {
      case '(1)':
        $itemstock = 'With Balance';
        break;
      case '(0)':
        $itemstock = 'Without Balance';
        break;
      case '(0,1)':
        $itemstock = 'None';
        break;
    }
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM CODE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('LOT', '100', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('SRP', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COUNT', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout_SELLING_PRICE($config)
  {
    $result = $this->reportDefault($config);

    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $companyid = $config['params']['companyid'];

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_SELLING_PRICE($config);

    $totalbalqty = 0;
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {

      if (strtoupper($scatgrp) == strtoupper($data->category)) {
        $scatgrp = "";
      } else {
        $scatgrp = strtoupper($data->category);
      }

      $balance = number_format($data->balance, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $isamt = number_format($data->amt, 2);
      if ($isamt == 0) {
        $isamt = '-';
      }

      $discounted = $this->othersClass->Discount($data->amt, $data->disc);
      $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($scatgrp, '150', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
      $str .= $this->reporter->col('', '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $totalext = $data->balance * $discounted;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
      }

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col($data->serialno, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          break;
      }

      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($discounted, 2), '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $scatgrp = strtoupper($data->category);
      $part = $data->part;
      $grandtotal = $grandtotal + $totalext;
      $totalbalqty = $totalbalqty + $data->balance;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader_SELLING_PRICE($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'r', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function default_displayHeader_LATEST_COST($config)
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
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $whname     = $config['params']['dataparams']['whname'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    if ($brandname == '') {
      $brandname = "ALL";
    }

    if ($modelname == '') {
      $modelname = "ALL";
    }

    if ($whname == '') {
      $whname = "ALL";
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
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY BALANCE FOR ACCOUNTING', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

    switch ($itemtype) {
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

    switch ($itemstock) {
      case '(1)':
        $itemstock = 'With Balance';
        break;
      case '(0)':
        $itemstock = 'Without Balance';
        break;
      case '(0,1)':
        $itemstock = 'None';
        break;
    }
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM CODE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('LOT', '100', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        break;
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('COST', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    return $str;
  }

  public function reportDefaultLayout_LATEST_COST($config)
  {
    $result = $this->reportDefault($config);

    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $companyid = $config['params']['companyid'];
    $wh         = $config['params']['dataparams']['wh'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_LATEST_COST($config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if (strtoupper($scatgrp) == strtoupper($data->category)) {
        $scatgrp = "";
      } else {
        $scatgrp = strtoupper($data->category);
      }
      $balance = number_format($data->balance, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $cost = number_format($data->cost, 2);
      if ($cost == 0) {
        $cost = '-';
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

      $totalext = $data->balance * $data->cost;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col($data->serialno, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
      }

      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($cost, '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->projname, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');

      $scatgrp = strtoupper($data->category);
      $part = $data->part;
      $totalbalqty = $totalbalqty + $data->balance;
      $grandtotal = $grandtotal + $totalext;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }
    $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'r', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
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
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $whname     = $config['params']['dataparams']['whname'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    if ($brandname == '') {
      $brandname = "ALL";
    }

    if ($modelname == '') {
      $modelname = "ALL";
    }

    if ($whname == '') {
      $whname = "ALL";
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    // }
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('INVENTORY BALANCE FOR ACCOUNTING', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

    switch ($itemtype) {
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');

    switch ($itemstock) {
      case '(1)':
        $itemstock = 'With Balance';
        break;
      case '(0)':
        $itemstock = 'Without Balance';
        break;
      case '(0,1)':
        $itemstock = 'None';
        break;
    }
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ITEM CODE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('LOT', '100', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    if ($itemstock != 'None') {
      $str .= $this->reporter->col('SRP', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    }
    $str .= $this->reporter->col('COUNT', '75', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');

    return $str;
  }

  public function reportDefaultLayout_NONE($config)
  {
    $result = $this->reportDefault($config);
    
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];

    $count = 46;
    $page = 45;
    $this->reporter->linecounter = 0;

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
      if ($balance == 0) {
        $balance = '-';
      }
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
      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
      }

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col($data->serialno, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
      }

      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      if ($itemstock != '(0,1)') {
        $str .= $this->reporter->col($isamt, '75', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      }
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
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }
    $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'r', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class