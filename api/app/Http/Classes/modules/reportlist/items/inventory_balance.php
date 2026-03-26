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

use Carbon\Carbon;

use Exception;

class inventory_balance
{
  public $modulename = 'Inventory Balance';
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
      case 37: //mega crystal
        $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        break;
    }
    $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'dwhname'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'ditemname', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'dwhname'];
        array_push($fields, 'project');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        break;
      case 14: //majesty
        $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'part', 'subcatname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'model.label', 'Generic');
        data_set($col1, 'part.label', 'Principal');
        data_set($col1, 'divsion.label', 'Division');
        data_set($col1, 'class.label', 'Classification');
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $fields = ['radioprint', 'start', 'ditemname', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        break;
      case 32: //3m
        $fields = ['radioprint', 'start', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'dwhname', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        data_set($col1, 'radioreporttype.label', 'Grouping');
        data_set($col1, 'radioreporttype.options', array(
          ['label' => 'Default', 'value' => 'default', 'color' => 'orange'],
          ['label' => 'Group by Warehouse', 'value' => 'wh', 'color' => 'orange']
        ));
        break;
      case 56:
        $col1 = $this->fieldClass->create($fields);
        if ($companyid == 56) { //homework
          data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
          ]);
        }
        break;
      case 60: //transpower
        $fields = ['radioprint', 'start', 'end', 'ditemname', 'luom', 'divsion', 'brandname', 'brandid', 'model', 'class', 'categoryname', 'subcatname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divsion.label', 'Group');
        break;
    }


    data_set($col1, 'start.label', 'Balance as of');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
    data_set($col1, 'luom.action', 'replookupuom');

    switch ($companyid) {
      case 47: //kitchenstar
        $fields = ['radioreportitemtype', 'radiorepitemstock', 'radiolayoutformat', 'radiorepamountformat'];
        break;
      default:
        $fields = ['radioreportitemtype', 'radiorepitemstock', 'radiorepamountformat', 'radiorepdtagathering'];
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368); // allow view transaction cost
    switch ($companyid) {
      case 19: // housegem        

        if (!$viewcost) {
          data_set($col2, 'radiorepamountformat.options', array(
            ['label' => 'None', 'value' => 'none', 'color' => 'orange']
          ));
        }
        break;

      case 21: //kinggeorge
        data_set($col2, 'radiorepitemstock.options', [
          ['label' => 'With Balance', 'value' => '(1)', 'color' => 'orange'],
          ['label' => 'Without Balance', 'value' => '(0)', 'color' => 'orange'],
          ['label' => 'Both', 'value' => '(0,1)', 'color' => 'orange']
        ]);
        break;

      case 60: //transpower
        data_set($col1, 'start.label', 'Start Date');
        data_set($col2, 'radiorepdtagathering.options', [
          ['label' => 'Default', 'value' => 'dhistory', 'color' => 'orange']
        ]);
        break;
    }

    if ($companyid == 47) { //kitchenstar
      data_set($col2, 'radiolayoutformat.label', 'Excluded Warehouse');
      data_set(
        $col2,
        'radiolayoutformat.options',
        [
          ['label' => 'Exclude Dummy Warehouse', 'value' => '1', 'color' => 'orange'],
          ['label' => 'None', 'value' => '0', 'color' => 'orange']
        ]
      );
    }

    if (!$viewcost) {
      data_set($col2, 'radiorepamountformat.options', array(
        ['label' => 'Show Selling Price', 'value' => 'isamt', 'color' => 'orange'],
        ['label' => 'For Accounting', 'value' => 'avecost', 'color' => 'orange'],
        ['label' => 'None', 'value' => 'none', 'color' => 'orange']
      ));
    } else {
      if ($companyid == 11) { // summit
        data_set($col2, 'radiorepamountformat.options', array(
          ['label' => 'Show Selling Price', 'value' => 'isamt', 'color' => 'orange'],
          ['label' => 'None', 'value' => 'none', 'color' => 'orange'],
        ));
      } elseif (($companyid == 56)) { //homeworks
        data_set($col2, 'radiorepamountformat.options', array(
          ['label' => 'Show Selling Price', 'value' => 'isamt', 'color' => 'orange'],
          ['label' => 'Show Latest Cost', 'value' => 'rrcost', 'color' => 'orange'],
          ['label' => 'Show Srp and Cost', 'value' => 'showboth', 'color' => 'orange'],
          ['label' => 'None', 'value' => 'none', 'color' => 'orange']
        ));
      } else {
        data_set($col2, 'radiorepamountformat.options', array(
          ['label' => 'Show Selling Price', 'value' => 'isamt', 'color' => 'orange'],
          ['label' => 'Show Latest Cost', 'value' => 'rrcost', 'color' => 'orange'],
          ['label' => 'For Accounting', 'value' => 'avecost', 'color' => 'orange'],
          ['label' => 'None', 'value' => 'none', 'color' => 'orange'],
          // ['label' => 'Format Test', 'value' => 'testing', 'color' => 'orange']
        ));
      }
    }


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $dataformat = 'dcurrent';
    if ($companyid == 60) {
      $dataformat = 'dhistory';
    }
    $paramstr = "select 
    'default' as print,
    left(now(),10) as start,
    left(now(),10) as end,
    '' as client,
    '' as clientname,
    '' as itemname,
    '' as barcode,
    '' as groupid,
    '' as stockgrp,
    '' as brandid,
    '' as brandname,
    '' as classid,
    '' as classic,
    '' as categoryid,
    '' as categoryname,
    '' as subcatname,
    '' as modelid,
    '' as modelname,
    '' as wh,
    '' as whname,
    '(0,1)' as itemtype,
    '(0,1)' as itemstock,
    '1' as layoutformat,
    'none' as amountformat,
    '" . $dataformat . "' as dtagathering,
    '' as ditemname,
    '' as divsion,
    '' as brand,
    '' as model,
    '' as class,
    '' as category,
    '' as subcat,
    '' as dwhname,
    '' as uom,
    '' as partid,
    '' as part,
    '' as partname,
     '' as whid
    ";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= ", '' as project, '' as projectid, '' as projectname";
        break;
      case 32: //3m
        $paramstr .= ",'default' as reporttype";

        break;
      case 14: //majesty
        $center = $config['params']['center'];
        $whid = '';
        $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
        $whname = '';
        $dwhname = '';
        if ($wh != '') {
          $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);
          $whname = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$wh]);
          $dwhname = $wh . '~' . $whname;
        }
        $paramstr .= ", '' as partid, '' as part, '' as partname, '" . $wh . "' as wh, '" . $whid . "' as whid, '" . $whname . "' as whname, '" . $dwhname . "' as dwhname";
        break;
      case 56: //homeworks
        $center = $config['params']['center'];
        $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
        $whid = '';
        $whname = '';
        $dwhname = '';
        if ($wh != '') {
          $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);
          $whname = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$wh]);
          $dwhname = $wh . '~' . $whname;
        }
        $paramstr .= ", '" . $wh . "' as wh, '" . $whid . "' as whid, '" . $whname . "' as whname, '" . $dwhname . "' as dwhname ";
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
    $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());

    $str = $this->reportplotting($config);

    $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
    $elapsed = $start->diffInSeconds($end);

    return ['status' => true, 'msg' => 'Generating report successfully. (' . $elapsed . "s)", 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $amountformat   = $config['params']['dataparams']['amountformat'];

    switch ($amountformat) {
      case 'isamt':

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $result = $this->reportAftiLayout_SELLING_PRICE($config);
            break;

          case 32: //3m
            $result = $this->mmm_layout($config);
            break;

          case 56: //homeworks
            $result = $this->hm_SELLING_PRICE_layout($config);
            break;
          default:
            $result = $this->reportDefaultLayout_SELLING_PRICE($config);
            break;
        } // end switch company
        break;

      case 'rrcost':

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $result = $this->reportAftiLayout_LATEST_COST($config);
            break;

          case 21: //kinggeorge
            $result = $this->report_kinggeorge_cost($config);
            break;

          case 32: //3m
            $result = $this->mmm_layout($config);
            break;
          case 56: //homeworks
            $result = $this->hm_LATEST_COST_layout($config);
            break;

          default:
            $result = $this->reportDefaultLayout_LATEST_COST($config);
            break;
        } // end swtich company

        break;

      case 'avecost':
        $result = $this->report_default_avecost($config);
        break;

      case 'none':
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $result = $this->reportAftiLayout_NONE($config);
            break;

          case 32: //3m
            $result = $this->mmm_layout($config);
            break;
          case 49: //hotmix
            $result = $this->hotmixLayout_NONE($config);
            break;
          case 56: //homeworks
            $result = $this->hm_NONE_layout($config);
            break;
          case 60: //transpower
            $result = $this->transpower_NONE_Layout($config);
            break;
          default:
            $result = $this->reportDefaultLayout_NONE($config);
            break;
        } // end switch company
        break;

      case 'testing':
        $result = $this->testingformat($config);
        break;
      case 'showboth':
        $result = $this->showsrpandcost($config);
        break;
    } // end switch format

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    // QUERY
    $current = $config['params']['dataparams']['dtagathering'];
    switch ($companyid) {
      case 1: //VITALINE 
        $query = $this->VITALINE_QUERY($config);
        break;
      case 23: //lab sol
        $query = $this->LABSOL_QUERY($config);
        break;
      case 10: //afti
      case 12: //afti usd
        $query = $this->afti_query($config);
        break;
      // case 14: //majesty - move to default
      //   $query = $this->majesty_query($config);
      //   break;
      case 32: //3M
        $query = $this->mmm_QUERY($config);
        break;
      case 60: //transpower
        $query = $this->transpower_DEFAULT_QUERY($config);
        break;
      default:
        if ($current == 'dhistory') {
          $query = $this->DEFAULT_QUERY($config);
        } else {
          $query = $this->dcurrent_qry($config);
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefaultAveCost($config)
  {
    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $whid         = $config['params']['dataparams']['whid'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $partid    = isset($config['params']['dataparams']['partid']) ? $config['params']['dataparams']['partid'] : 0;
    $partname  = isset($config['params']['dataparams']['partname']) ? $config['params']['dataparams']['partname'] : '';

    $uom   = $config['params']['dataparams']['uom'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';


    $order = " order by itemname";
    $filter = " and item.isimport in $itemtype";

    $filtermain = "";
    $addfield = "";
    $addfieldgrpby = "";
    $leftjoin = "";

    if ($brand != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filter = $filter . " and item.model='$modelid'";
      $addfield .= ",  modelgrp.model_name as modelname";
      $addfieldgrpby = ", modelgrp.model_name";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($partname != "") {
      $filter = $filter . " and item.part='$partid'";
      $addfield .= ", partgrp.part_name as partname";
      $addfieldgrpby = ", partgrp.part_name";
      $leftjoin .= " left join part_masterfile as partgrp on partgrp.part_id = item.part";
    }

    if ($groupname != "") {
      $filter = $filter . " and item.groupid='$groupid'";
      $addfield .= ", stockgrp.stockgrp_name";
      $addfieldgrpby = ", stockgrp.stockgrp_name";
      $leftjoin .= " left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid";
    }

    if ($whname != "") {
      $filtermain = $filtermain . " and stock.whid=$whid";
    }

    $proj = '';
    $proj1 = '';
    $proj2 = '';
    $proj3 = '';
    $color = '';

    if ($companyid == 47) { //kitchenstar
      $repitemcol = "item.itemname as itemname, item.color,";
      $color = "color,";
    } else {
      $repitemcol = "item.itemname as itemname,";
    }

    if ($uom != '') {
      $uom = "and uom.uom = '$uom'";
    } else {
      $uom = 'and uom.uom = item.uom';
    }

    if ($companyid == 50) { //unitech
      $addfield .= ", brand.brand_desc as brandn";
      $addfieldgrpby = ", brand.brand_desc ";
      $leftjoin .= "left join frontend_ebrands as brand on brand.brandid = item.brand";
    }

    $barcode = ",item.barcode as barcode";
    /* di mag tatally sa subsidiary pag sinama unposted */

    // $query = "select ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    // groupid,brandname, partname,
    // modelname,model, part,brand, $color sizeid,body, class, ib.uom,
    // sum(qty-iss) as balance,
    // sum(round(costin-costout,2))/sum(qty-iss) as cost,ib.amt, loc, expiry, serialno $proj
    // from (

    //   select stock.trno, stock.line, item.disc, item.minimum,item.maximum,cat.name as category,
    //   fbrand.brand_desc as brandname,
    //   ifnull(partgrp.part_name,'') as partname,
    //   ifnull(modelgrp.model_name,'') as modelname, item.itemid $barcode, " . $repitemcol . " item.model,
    //   partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
    //   wh.clientname as whname,
    //   0 as cost,
    //   case when stock.qty > 0 then (stock.cost*stock.qty) else 0 end as costin,
    //   case when stock.iss > 0 then (stock.cost*stock.iss) else 0 end as costout,
    //   (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,
    //   (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
    //   item.amt, stock.loc, stock.expiry, iinfo.serialno $proj1
    //   from (((glhead as head
    //   left join glstock as stock on stock.trno=head.trno)
    //   left join item on item.itemid=stock.itemid
    //   left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    //   left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    //   left join part_masterfile as partgrp on partgrp.part_id = item.part
    //   left join client as wh on wh.clientid=stock.whid)
    //   left join cntnum on cntnum.trno=head.trno
    //   left join itemcategory as cat on cat.line = item.category
    //   left join iteminfo as iinfo on iinfo.itemid = item.itemid
    //   left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
    //   left join uom on uom.itemid = item.itemid  $uom
    //   $proj2
    //   $proj3
    //   where  date(head.dateid)<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1
    // ) as ib
    // group by ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    // groupid,brandname, partname,
    // modelname,model, part,ib.brand, $color sizeid,body, class, ib.uom, loc, expiry, serialno $proj,
    // ib.amt having (case when sum(qty-iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;


    $cutoffdate = $this->coreFunctions->datareader("select pvalue as value from profile where psection= ? limit 1", ['INVCUTOFF']);
    $invwh = "";
    if ($cutoffdate != '') {
      if ($wh != "") {
        $invwh = $invwh . " and inv.whid='$whid'";
      }

      if ($cutoffdate > $asof) {
        goto def;
      }
      $filtetdate = "head.dateid > '$cutoffdate' and head.dateid <= '$asof'";

      $unionInvbal = "
          union all
          select inv.itemid, inv.whid, sum(inv.cost) as costin, 0 as costout, sum(bal) as qty, 0 as iss, inv.loc, inv.expiry
          from invbal as inv join item on item.itemid = inv.itemid
          where inv.dateid <= '$cutoffdate' $invwh
          group by inv.itemid,item.uom,inv.whid,iss,inv.loc,inv.expiry";
    } else {
      def:
      $unionInvbal = "";
      $filtetdate = "head.dateid <= '$asof'";
    }


    $query = "select item.disc, item.minimum,item.maximum, item.itemid, item.barcode, " . $repitemcol . " cat.name as category,
        item.brand as brandname, item.model, item.part, item.brand, $color item.sizeid, item.body, item.class, item.uom, 
        ifnull(sum(ib.qty-ib.iss),0) as balance, ifnull(sum(round(ib.costin-ib.costout,2))/sum(ib.qty-ib.iss),0) as cost, item.amt, loc, expiry $addfield
        from ( 
        select item.itemid, stock.whid, sum(stock.cost*stock.qty) as costin, sum(stock.cost*stock.iss) as costout, 
        sum(stock.qty/ifnull(uom.factor,1)) as qty, sum(stock.iss/ifnull(uom.factor,1)) as iss, stock.loc, stock.expiry 
        from glhead as head left join glstock as stock on stock.trno=head.trno join item on item.itemid=stock.itemid 
        left join uom on uom.itemid = item.itemid $uom
        where $filtetdate $filtermain
        group by item.itemid, stock.whid, stock.loc, stock.expiry
        
        $unionInvbal
        
        ) as ib 
        left join item on item.itemid=ib.itemid
        left join itemcategory as cat on cat.line = item.category
        $leftjoin
        where ''='' $filter
        group by item.disc, item.minimum,item.maximum,item.itemid,barcode, itemname, groupid, brandname, model, part, cat.name,
        item.brand, $color sizeid,body, class, item.uom, loc, expiry, item.amt $addfieldgrpby
        having (case when sum(ib.qty-ib.iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;


    return $this->coreFunctions->opentable($query);
  }

  public function DEFAULT_QUERY($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $excwh = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '';
    $uom = $config['params']['dataparams']['uom'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';

    switch ($companyid) {
      case 14: //majesty
      case 17: //unihome
      case 49: //hotmix
        $order = " order by itemname,category";
        break;
      case 24: //goodfound
        $order = " order by stockgrp_name,itemname";
        break;
      default:
        $order = " order by category,itemname";
        break;
    }

    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
    $filteritem = "";

    $isallitems = true;
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $isallitems = false;
    }

    if ($brand != "") {
      $filteritem = $filteritem . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filteritem = $filteritem . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filteritem = $filteritem . " and item.class='$classid'";
    }

    if ($category != "") {
      $filteritem = $filteritem . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filteritem = $filteritem . " and item.subcat='$subcatname'";
    }

    if ($barcode != "") {
      $filteritem = $filteritem . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      if ($isallitems) {
        $filteritem = $filteritem . " and item.groupid='$groupid'";
      } else {
        $filter = $filter . " and item.groupid='$groupid'";
      }
    }

    if ($wh != "") {
      $filter = $filter . " and stock.whid='$whid'";
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

    $fielduom = ' item.uom';
    $fieldqty = ' stock.qty';
    $fieldiss = ' stock.iss';
    $fieldAmt = ' item.amt';
    $defaultljoinmain = '';
    $defaultljoin = '';
    $addfieldgrpby = '';

    switch ($companyid) {
      case 27: //NTE
      case 36: //ROZLAB
        $defaultljoinmain = ' left join uom on uom.itemid = item.itemid and uom.isdefault =1';
        $defaultljoin = ' left join uom on uom.itemid = item.itemid and uom.isdefault =1';
        $fieldqty = ' (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end)';
        $fieldiss = ' (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end)';
        $fielduom = ' uom.uom';
        $addfieldgrpby = ', uom.uom';
        break;
      case 37: //MCPC
        $fieldAmt = ' uom.amt';
        if ($uom != '') {
          $defaultljoinmain = " left join uom on uom.itemid = item.itemid and uom.uom = '$uom'";
          $defaultljoin = " left join uom as uom on uom.itemid = stock.itemid and uom.uom = '$uom'";
        } else {
          $defaultljoinmain = ' left join uom on uom.itemid = item.itemid and uom.uom =item.uom';
          $defaultljoin = ' left join uom on uom.itemid = item.itemid and uom.uom =item.uom';
        }
        $fieldqty = ' (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end)';
        $fieldiss = ' (case when uom.factor>1 then stock.iss/uom.factor else stock.iss end)';
        $fielduom = ' uom.uom';
        $addfieldgrpby = ', uom.amt';
        break;
    }
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $isallitems = false;
        $prjid = $config['params']['dataparams']['project'];
        $project = $config['params']['dataparams']['projectid'];
        if ($prjid != "") {
          $filter1 .= " and item.projectid = $project";
        }

        $repitemcol = "concat(ifnull(item.itemname,''),' ',ifnull(modelgrp.model_name,''),' ',ifnull(brand.brand_desc,''),' ',left(ifnull(i.itemdescription,''),50)) as itemname,";
        break;
      default:
        $filter1 .= "";

        if ($companyid == 47) { //kitchenstar
          $repitemcol = "item.itemname as itemname, item.color,";
        } else {
          $repitemcol = "item.itemname as itemname,";
        }

        break;
    }

    switch ($companyid) {
      case 15: //nathina
      case 14: //majesty
      case 36: //rozlab
        $exp = '';
        break;

      default:
        $exp = ", expiry";
        break;
    }

    $filterexcwh = '';
    $addfield = '';
    $addfieldhm = '';
    $hmjoin = '';
    $hmpostedwh = '';
    $hmunpostedwh = '';
    $addwh = '';
    $hmgroup = '';
    $addhgroup = '';
    switch ($companyid) {
      case 14: //majesty
      case 36: //rozlab
        $addfield = '';
        break;
      case 47: //kitchenstar
        $addfield = ', loc, color';
        if ($excwh) {
          $filterexcwh = " and stock.whid <> 1014";
        }
        break;
      default:
        $addfield = ', loc';
        break;
    }
    if ($companyid == 24) { //goodfound
      $addfield .= ',stockgrp.stockgrp_name';
    }

    if ($companyid == 56) { //homeworks
      $addfieldhm .= ', cl.client as supcode, cl.clientname as supname, iclass.cl_name as classname , warehouse, stockgrp.stockgrp_name ';
      $addwh .= ', wh.clientname as warehouse';
      $hmjoin = ' left join client as cl on cl.clientid=item.supplier
                  left join item_class as iclass on iclass.cl_id=item.class';
      $hmunpostedwh = ' left join client as wh on wh.client=head.wh';
      $hmpostedwh = ' left join client as wh on wh.clientid=head.whid';
      $hmgroup = ', wh.clientname';
      $addhgroup = ', cl.client, cl.clientname, iclass.cl_name , warehouse, stockgrp.stockgrp_name';
    }
    $brandn = ", item.brand ";
    if ($companyid == 50) { //unitech
      $brandn = ", brand.brand_desc ";
      $hmjoin .= "left join frontend_ebrands as brand on brand.brandid = item.brand";
    }

    $cost = '';
    $grpCost = '';
    $fieldCost = '';
    $cost = '0 as cost, ';

    $isallitems = false;
    if ($isallitems) {
      $query = "select item.disc, item.minimum, item.maximum, cat.name as category, subcat.name as subcatname, item.itemid, item.barcode,item.partno, item.itemname,
      item.groupid, item.brand as brandname, item.brand, ifnull(partgrp.part_name, '') as partname, iinfo.serialno,
      ifnull(modelgrp.model_name, '') as modelname, item.model, partgrp.part_name as part, item.sizeid, item.body, item.class, " . $fielduom . ",
      sum(ib.qty - ib.iss) as balance, $cost $fieldAmt " . $addfield . " $exp $proj 
      from item 
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part 
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join iteminfo as iinfo on iinfo.itemid = item.itemid
      " . $defaultljoinmain . "
      left join (
      select item.itemid, " . $repitemcol . " " . $fielduom . ", 
      " . $fieldqty . " as qty, 
      " . $fieldiss . " as iss,
      $fieldCost stock.loc, stock.expiry $proj1
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join item on item.itemid = stock.itemid
      $proj2
      $proj3
      $defaultljoin
      where head.dateid <= '$asof' and ifnull(item.barcode, '') <> '' $filter $filter1 and item.isofficesupplies = 0 " . $filteritem . " " . $filterexcwh . " 
      union all
      select item.itemid, " . $repitemcol . " " . $fielduom . ", 
      " . $fieldqty . " as qty, 
      " . $fieldiss . " as iss,
      $fieldCost stock.loc, stock.expiry $proj1  
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join item on item.itemid = stock.itemid
      $proj2
      $proj3
      $defaultljoin
      where head.dateid <= '$asof' and ifnull(item.barcode, '') <> '' $filter $filter1 and item.isofficesupplies = 0 " . $filteritem . " " . $filterexcwh . " 
      ) as ib on ib.itemid = item.itemid
      where item.isofficesupplies = 0 " . $filteritem . " 
      group by item.disc, minimum, maximum, cat.name, subcat.name, item.itemid,item.barcode,item.partno, item.itemname,
      groupid, brand, partgrp.part_name, model, modelgrp.model_name, partgrp.part_name, brand, sizeid, body, class, $grpCost item.amt, iinfo.serialno, $fielduom" . $addfield .  $exp . $proj . $addfieldgrpby . "
      having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;
    } else {
      // 2025.01.07 FMM - moved item table outside of subquery
      // $query = "select ib.disc, ib.minimum, ib.maximum, category, subcatname, ib.itemid, barcode, itemname,partno,
      // groupid, brandname, ifnull(brandname, '') as brand, partname,
      // modelname, model, part, brand, sizeid, body, class, ib.uom,
      // sum(qty - iss) as balance,
      // ib.amt $addfield $exp $proj 
      // from (
      // select item.disc, item.minimum, item.maximum, cat.name as category, subcat.name as subcatname, item.brand as brandname, ifnull(partgrp.part_name, '') as partname,
      // ifnull(modelgrp.model_name, '') as modelname, item.itemid, item.barcode,item.partno, " . $repitemcol . " item.model,
      // partgrp.part_name as part, item.groupid, item.brand, item.sizeid, item.body, item.class, " . $fielduom . ", wh.client as swh,
      // wh.clientname as whname, " . $fieldqty . ", " . $fieldiss . ",
      // item.amt, stock.loc, stock.expiry, iinfo.serialno $proj1
      // from (((lahead as head
      // left join lastock as stock on stock.trno = head.trno)
      // left join item on item.itemid = stock.itemid
      // left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      // left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      // left join part_masterfile as partgrp on partgrp.part_id = item.part
      // left join client as wh on wh.clientid = stock.whid)
      // left join cntnum on cntnum.trno = head.trno
      // left join itemcategory as cat on cat.line = item.category
      // left join itemsubcategory as subcat on subcat.line = item.subcat
      // left join iteminfo as iinfo on iinfo.itemid = item.itemid
      // $proj2
      // $proj3
      // $defaultljoin
      // where head.dateid <= '$asof' and ifnull(item.barcode, '') <> '' $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
      // union all
      // select item.disc, item.minimum, item.maximum, cat.name as category, subcat.name as subcatname, item.brand as brandname, ifnull(partgrp.part_name, '') as partname,
      // ifnull(modelgrp.model_name, '') as modelname, item.itemid,item.barcode,item.partno," . $repitemcol . " item.model,
      // partgrp.part_name as part, item.groupid, item.brand, item.sizeid, item.body, item.class, " . $fielduom . ", wh.client as swh,
      // wh.clientname as whname, " . $fieldqty . ", " . $fieldiss . ",
      // item.amt, stock.loc, stock.expiry, iinfo.serialno $proj1 
      // from (((glhead as head
      // left join glstock as stock on stock.trno = head.trno)
      // left join item on item.itemid = stock.itemid
      // left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      // left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      // left join part_masterfile as partgrp on partgrp.part_id = item.part
      // left join client as wh on wh.clientid = stock.whid)
      // left join cntnum on cntnum.trno = head.trno
      // left join itemcategory as cat on cat.line = item.category
      // left join itemsubcategory as subcat on subcat.line = item.subcat
      // left join iteminfo as iinfo on iinfo.itemid = item.itemid
      // $proj2
      // $proj3
      // $defaultljoin
      // where head.dateid <= '$asof' and ifnull(item.barcode, '') <> '' $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
      // ) as ib
      // group by ib.disc, ib.minimum, ib.maximum, category, subcatname, ib.itemid, barcode, itemname,
      // groupid, brandname, partname,partno,
      // modelname, model, part, ib.brand, sizeid, body, class,ib.amt, ib.uom $addfield $exp $proj 
      //  having (case when sum(qty - iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;


      //2025/01/9 add invbal table and get cutoffdate 
      // $query = "select item.disc, item.minimum, item.maximum, cat.name as category, subcat.name as subcatname, ib.itemid, item.barcode, " . $repitemcol . " item.partno,
      //     item.groupid, item.brand as brandname, item.brand, ifnull(partgrp.part_name, '') as partname, iinfo.serialno,
      //     ifnull(modelgrp.model_name, '') as modelname, item.model, partgrp.part_name as part, item.brand, item.sizeid, item.body, item.class, ib.uom,
      //     sum(ib.qty - ib.iss) as balance,
      //     item.amt $addfield $exp $proj 
      //     from (
      //     select stock.itemid,  " . $fielduom . ", stock.whid, sum(" . $fieldqty . ") as qty, sum(" . $fieldiss . ") as iss, stock.loc, stock.expiry $proj1
      //     from lahead as head left join lastock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
      //     $proj2
      //     $proj3
      //     $defaultljoin
      //     where head.dateid <= '$asof' $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
      //     group by stock.itemid,  " . $fielduom . ", stock.whid, stock.loc, stock.expiry $proj1
      //     union all
      //     select stock.itemid, " . $fielduom . ", stock.whid, sum(" . $fieldqty . ") as qty, sum(" . $fieldiss . ") as iss, stock.loc, stock.expiry $proj1 
      //     from glhead as head left join glstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
      //     $proj2
      //     $proj3
      //     $defaultljoin
      //     where head.dateid <= '$asof' $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
      //     group by stock.itemid,  " . $fielduom . ", stock.whid, stock.loc, stock.expiry $proj1
      //     ) as ib left join item on item.itemid = ib.itemid
      //     left join itemcategory as cat on cat.line = item.category
      //     left join itemsubcategory as subcat on subcat.line = item.subcat
      //     left join part_masterfile as partgrp on partgrp.part_id = item.part
      //     left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      //     left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
      //     left join iteminfo as iinfo on iinfo.itemid = item.itemid

      //     group by disc, minimum, maximum, cat.name, subcat.name, ib.itemid, barcode, itemname, groupid, brandname, partname,partno,
      //     ifnull(modelgrp.model_name, ''), model, partgrp.part_name, brand, sizeid, body, class, item.amt, ib.uom, iinfo.serialno $addfield $exp $proj 
      //     having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;


      //fortesting =========================================
      $cutoffdate = $this->coreFunctions->datareader("select pvalue as value from profile where psection= ? limit 1", ['INVCUTOFF']);
      $invwh = "";
      $unionInvbal = "";
      if ($cutoffdate != '') {
        if ($wh != "") {
          $invwh = $invwh . " and inv.whid='$whid'";
        }

        if ($cutoffdate > $asof) {
          goto def;
        }
        $filtetdate = "head.dateid > '$cutoffdate' and head.dateid <= '$asof'";

        $unionInvbal = "
          union all
          select inv.itemid,item.uom,inv.whid,sum(bal) as qty, 0 as iss, inv.loc,inv.expiry from invbal as inv
          left join item on item.itemid = inv.itemid
          where inv.dateid <= '$cutoffdate' $invwh $filteritem
          group by inv.itemid,item.uom,inv.whid,iss,inv.loc,inv.expiry";
      } else {
        def:
        $filtetdate = "head.dateid <= '$asof'";
      }

      $query = "select ib.itemid, item.barcode, item.disc, item.minimum, item.maximum, cat.name as category, subcat.name as subcatname, " . $repitemcol . " item.partno,
          item.groupid $brandn as brandname, item.brand, ifnull(partgrp.part_name, '') as partname, iinfo.serialno,
          ifnull(modelgrp.model_name, '') as modelname, item.model, partgrp.part_name as part, item.brand, item.sizeid, item.body, item.class, ib.uom,
          sum(ib.qty - ib.iss) as balance,
          item.amt $addfield $exp $proj $addfieldhm
          from (
          select stock.itemid,  " . $fielduom . ", stock.whid, 
          sum(" . $fieldqty . ") as qty, 
          sum(" . $fieldiss . ") as iss, 
          stock.loc, stock.expiry $proj1 $addwh
          from lahead as head left join lastock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
          $proj2
          $proj3 $hmunpostedwh
          $defaultljoin
          where $filtetdate $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
          group by stock.itemid,  " . $fielduom . ", stock.whid, stock.loc, stock.expiry $proj1 $hmgroup
          union all
          select stock.itemid, " . $fielduom . ", stock.whid, 
          sum(" . $fieldqty . ") as qty, 
          sum(" . $fieldiss . ") as iss, 
          stock.loc, stock.expiry $proj1 $addwh
          from glhead as head left join glstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
          $proj2
          $proj3  $hmpostedwh
          $defaultljoin
          where $filtetdate $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
          group by stock.itemid,  " . $fielduom . ", stock.whid, stock.loc, stock.expiry $proj1 $hmgroup

          $unionInvbal
          ) as ib left join item on item.itemid = ib.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          left join part_masterfile as partgrp on partgrp.part_id = item.part
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
          left join iteminfo as iinfo on iinfo.itemid = item.itemid
          $hmjoin
          group by disc, minimum, maximum, cat.name, subcat.name, ib.itemid, barcode, itemname, groupid, brandname, partname,partno,
          ifnull(modelgrp.model_name, ''), model, partgrp.part_name, brand, sizeid, body, class, item.amt, ib.uom, iinfo.serialno $addfield $exp $proj $addhgroup
          having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;
    }
    return $query;
  }


  public function transpower_DEFAULT_QUERY($config)
  {

    $start       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end       = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $excwh = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '';
    $uom = $config['params']['dataparams']['uom'];
    $companyid = $config['params']['companyid'];


    $order = " order by category,itemname";
    $filter = " and item.isimport in $itemtype";
    $filteritem = "";

    $isallitems = true;
    if ($brand != "") {
      $filteritem = $filteritem . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filteritem = $filteritem . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filteritem = $filteritem . " and item.class='$classid'";
    }

    if ($category != "") {
      $filteritem = $filteritem . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filteritem = $filteritem . " and item.subcat='$subcatname'";
    }

    if ($barcode != "") {
      $filteritem = $filteritem . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      if ($isallitems) {
        $filteritem = $filteritem . " and item.groupid='$groupid'";
      } else {
        $filter = $filter . " and item.groupid='$groupid'";
      }
    }

    if ($uom != '') {
      $filteritem = $filteritem . " and item.uom='$uom'";
    }

    if ($wh != "") {
      $filter = $filter . " and stock.whid='$whid'";
    }

    $query = "select ib.itemid, item.barcode, item.disc, item.minimum, item.maximum, cat.name as category, subcat.name as subcatname, item.itemname as itemname, item.partno,
          item.groupid, item.brand as brandname, item.brand, ifnull(partgrp.part_name, '') as partname,
          ifnull(modelgrp.model_name, '') as modelname, item.model, partgrp.part_name as part, item.brand, item.sizeid, item.body, item.class, ib.uom,
          sum(ib.qty - ib.iss) as balance,
          item.amt,loc, expiry
          from (
          select stock.itemid,  item.uom, stock.whid, 
          sum(stock.qty) as qty, 
          sum(stock.iss) as iss, 
          stock.loc, stock.expiry 
          from lahead as head left join lastock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
    
          where date(head.dateid) between '$start' and '$end' $filter $filteritem and item.isofficesupplies = 0
          group by stock.itemid,  item.uom, stock.whid, stock.loc, stock.expiry
          union all
          select stock.itemid, item.uom, stock.whid, 
          sum(stock.qty) as qty, 
          sum(stock.iss) as iss, 
          stock.loc, stock.expiry
          from glhead as head left join glstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid

          where date(head.dateid) between '$start' and '$end' $filter $filteritem and item.isofficesupplies = 0
          group by stock.itemid,  item.uom, stock.whid, stock.loc, stock.expiry

          ) as ib left join item on item.itemid = ib.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          left join part_masterfile as partgrp on partgrp.part_id = item.part
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
          group by disc, minimum, maximum, cat.name, subcat.name, ib.itemid, barcode, itemname, groupid, brandname, partname,partno,
          ifnull(modelgrp.model_name, ''), model, partgrp.part_name, brand, sizeid, body, class, item.amt, ib.uom,loc,expiry
          having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;;
    return $query;
  }

  public function majesty_QUERY($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];

    $partid   = $config['params']['dataparams']['partid'];
    $part   = $config['params']['dataparams']['part'];
    $partname   = $config['params']['dataparams']['partname'];
    $repitemcol = '';

    $order = " order by itemname,category";


    $filter = " and item.isimport in $itemtype";

    if ($partname != "") {
      $filter = $filter . " and item.part='$partid'";
    }

    if ($brand != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filter = $filter . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }

    $query = "select ib.disc, ib.minimum,ib.maximum,category,subcatname,ib.itemid,barcode, itemname,
    groupid,brandname,ifnull(brandname,'') as brand, partname,
    modelname,model, part,brand,sizeid,body, class, ib.uom,
    sum(qty-iss) as balance,
    cost,ib.amt 
    from (
      select item.disc, item.minimum,item.maximum,cat.name as category, subcat.name as subcatname,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.barcode,item.itemname as itemname, item.model,
      partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
      wh.clientname as whname, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,
      item.amt, stock.loc, stock.expiry, iinfo.serialno 
      from (((lahead as head
      left join lastock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part
      left join client as wh on wh.clientid=stock.whid)
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join iteminfo as iinfo on iinfo.itemid = item.itemid
      where head.dateid<='$asof'and ifnull(item.barcode,'')<>'' $filter and item.isofficesupplies=0
      union all
      select item.disc, item.minimum,item.maximum,cat.name as category,subcat.name as subcatname,item.brand as brandname,ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.barcode,item.itemname as itemname,item.model,
      partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, item.uom, wh.client as swh,
      wh.clientname as whname, stock.qty, stock.iss,
      ifnull((select cost from rrstatus where itemid=item.itemid order by dateid desc limit 1),0) as cost,
      item.amt, stock.loc, stock.expiry, iinfo.serialno 
      from (((glhead as head
      left join glstock as stock on stock.trno=head.trno)
      left join item on item.itemid=stock.itemid
      left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
      left join model_masterfile as modelgrp on modelgrp.model_id = item.model
      left join part_masterfile as partgrp on partgrp.part_id = item.part
      left join client as wh on wh.clientid=stock.whid)
      left join cntnum on cntnum.trno=head.trno
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      left join iteminfo as iinfo on iinfo.itemid = item.itemid
      where  head.dateid<='$asof'and ifnull(item.barcode,'')<>'' $filter and item.isofficesupplies=0
    ) as ib
    group by ib.disc, ib.minimum,ib.maximum,category,subcatname,ib.itemid,barcode, itemname,
    groupid,brandname, partname,
    modelname,model, part,ib.brand,sizeid,body, class, ib.uom ,
    ib.cost,ib.amt having (case when sum(qty-iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

    return $query;
  }

  private function afti_query($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';

    $order = " order by projname";
    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
    if ($brand != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filter = $filter . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
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
      $prjid = $config['params']['dataparams']['project'];
      $project = $config['params']['dataparams']['projectid'];
      if ($prjid != "") {
        $filter1 .= " and item.projectid = $project";
      }

      $repitemcol = "left(ifnull(i.itemdescription,''),50) as itemname,";
    } else {
      $filter1 .= "";
      $repitemcol = "item.itemname as itemname,";
    }

    $filter .= " and item.islabor=0 and wh.nonsaleable=0 ";

    // [JIKS] [01.23.2021] -- REVISED QUERIES
    $query = "select ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname,ifnull(brandname,'') as brand, partname,
    modelname,model, part,brand,sizeid,body, class, ib.uom,
    sum(qty-iss) as balance,
    cost,ib.amt, loc, expiry, serialno,whname $proj
    from (
      select stock.trno, stock.line, item.disc, item.minimum,item.maximum,cat.name as category,
      fbrand.brand_desc as brandname,
      ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname,item.itemid,item.partno as barcode," . $repitemcol . " item.model,
      partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
      wh.clientname as whname, (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,(case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
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
      left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
      left join uom on uom.itemid = item.itemid and uom.isdefault =1
      $proj2
      $proj3
      where head.dateid<='$asof' and ifnull(item.barcode,'')<>'' and stock.iss<>0 $filter $filter1
      union all
      select stock.trno, stock.line, item.disc, item.minimum,item.maximum,cat.name as category,
      fbrand.brand_desc as brandname,
      ifnull(partgrp.part_name,'') as partname,
      ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.partno as barcode, " . $repitemcol . " item.model,
      partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
      wh.clientname as whname, (case when uom.factor>1 then stock.qty/uom.factor else stock.qty end) as qty,(case when uom.factor>1 then stock.iss/uom.factor else stock.iss end) as iss,
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
      left join frontend_ebrands as fbrand on fbrand.brandid = item.brand
      left join uom on uom.itemid = item.itemid and uom.isdefault =1
      $proj2
      $proj3
      where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter $filter1
    ) as ib
    group by ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname, partname,
    modelname,model, part,ib.brand,sizeid,body, class, ib.uom, loc, expiry, serialno,whname $proj,
    ib.cost,ib.amt having (case when sum(qty-iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

    return $query;
  }

  public function VITALINE_QUERY($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $uom        = $config['params']['dataparams']['uom'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $format = '';

    $order = " order by category,itemname";
    $filter = " and item.isimport in $itemtype";
    if ($brand != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filter = $filter . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }

    if ($uom != "") {
      $filter = $filter . " and uom.uom='$uom'";
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

  public function LABSOL_QUERY($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $format = '';

    $order = " order by category,itemname";
    $filter = " and item.isimport in $itemtype";
    if ($brand != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filter = $filter . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
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
    sum(qty-iss) as balance
    " . $format . " ,loc, expiry
    from (
    select item.disc, item.minimum,item.maximum,c.name as category,b.brand_desc as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.barcode, item.itemname,item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    stock.cost,
    item.amt, stock.loc, stock.expiry
    from (((glhead as head
    left join glstock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join uom as uom on uom.itemid=item.itemid and uom.factor =1
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join frontend_ebrands as b on b.brandid = item.brand
    left join itemcategory as c on c.line = item.category
    left join cntnum on cntnum.trno=head.trno
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter
    union all
    select item.disc, item.minimum,item.maximum,c.name as category,b.brand_desc as brandname,ifnull(partgrp.part_name,'') as partname,
    ifnull(modelgrp.model_name,'') as modelname, item.itemid,item.barcode, item.itemname,item.model,
    partgrp.part_name as part,item.groupid, item.brand, item.sizeid,item.body, item.class, uom.uom, wh.client as swh,
    wh.clientname as whname, stock.qty, stock.iss,
    stock.cost,
    item.amt, stock.loc, stock.expiry
    from (((lahead as head
    left join lastock as stock on stock.trno=head.trno)
    left join item on item.itemid=stock.itemid
    left join uom as uom on uom.itemid=item.itemid and uom.factor =1
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid)
    left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    left join part_masterfile as partgrp on partgrp.part_id = item.part
    left join client as wh on wh.clientid=stock.whid)
    left join cntnum on cntnum.trno=head.trno
    left join frontend_ebrands as b on b.brandid = item.brand
    left join itemcategory as c on c.line = item.category
    where  head.dateid<='$asof' and ifnull(item.barcode,'')<>'' $filter) as ib
    left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
    group by ib.disc, ib.minimum,ib.maximum,category,ib.itemid,barcode, itemname,
    groupid,brandname, partname,
    modelname,model, part,ib.brand,sizeid,body, class, ib.uom, loc, expiry,
    uom.factor " . $format . " having (case when sum(qty-iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

    return $query;
  }

  public function mmm_QUERY($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';

    $order = " order by whname,category,itemname";
    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
    $filteritem = "";

    $isallitems = true;

    if ($brand != "") {
      $filteritem = $filteritem . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filteritem = $filteritem . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filteritem = $filteritem . " and item.class='$classid'";
    }

    if ($category != "") {
      $filteritem = $filteritem . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filteritem = $filteritem . " and item.subcat='$subcatname'";
    }

    if ($barcode != "") {
      $filteritem = $filteritem . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      if ($isallitems) {
        $filteritem = $filteritem . " and stockgrp.stockgrp_id='$groupid'";
      } else {
        $filter = $filter . " and stockgrp.stockgrp_id='$groupid'";
      }
    }

    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }

    $proj = '';
    $proj1 = '';
    $proj2 = '';
    $proj3 = '';

    $fielduom = ' ,item.uom';
    $fieldqty = ' stock.qty';
    $fieldiss = ' stock.iss';
    $defaultljoinmain = '';
    $defaultljoin = '';
    $addfieldgrpby = '';

    $filter1 .= "";
    $repitemcol = "item.itemname as itemname,";
    $exp = ", expiry";

    switch ($reporttype) {
      case 'wh':
        $grouping = ",ib.whname";
        break;

      default:
        $grouping = "";
        break;
    }

    $addfield = '';

    $addfield = ', loc, serialno';

    $query = "select ib.disc, item.minimum,item.maximum,cat.name as category,subcat.name as subcatname,item.itemid,item.barcode, item.itemname,
              item.groupid,item.brand as brandname,item.brand, ifnull(partgrp.part_name,'') as partname, iinfo.serialno,
              ifnull(modelgrp.model_name,'') as modelname,item.model, partgrp.part_name as part,item.sizeid,item.body, item.class, 
              sum(ib.qty-ib.iss) as balance,0 as cost,ib.amt  $addfield $exp $proj $grouping $fielduom 
              from item 
                left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
                left join model_masterfile as modelgrp on modelgrp.model_id = item.model
                left join part_masterfile as partgrp on partgrp.part_id = item.part 
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                left join iteminfo as iinfo on iinfo.itemid = item.itemid
                " . $defaultljoinmain . "
                left join (
                select item.disc,item.itemid,item.barcode," . $repitemcol . " wh.client as swh,
                wh.clientname as whname, " . $fieldqty . ", " . $fieldiss . ",
                
                item.amt, stock.loc, stock.expiry $proj1 $fielduom 
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join client as wh on wh.clientid=stock.whid
                left join cntnum on cntnum.trno=head.trno
                $proj2
                $proj3
                $defaultljoin
                where head.dateid<='$asof'and ifnull(item.barcode,'')<>'' $filter $filter1  and item.isofficesupplies=0
                union all
                select item.disc, item.itemid,item.barcode, " . $repitemcol . " wh.client as swh,
                wh.clientname as whname, " . $fieldqty . ", " . $fieldiss . ",
                
                item.amt, stock.loc, stock.expiry $proj1 $fielduom 
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join client as wh on wh.clientid=stock.whid
                left join cntnum on cntnum.trno=head.trno
                $proj2
                $proj3
                $defaultljoin
                where  head.dateid<='$asof'and ifnull(item.barcode,'')<>'' $filter $filter1 and item.isofficesupplies=0
              ) as ib on ib.itemid=item.itemid
              where item.isofficesupplies=0 " . $filteritem . " 
              group by ib.disc, minimum, maximum,cat.name,subcat.name,item.itemid,item.barcode,item.itemname,
              groupid,brand, partgrp.part_name,model, modelgrp.model_name, part, brand,sizeid,body, class, ib.amt $grouping $fielduom " . $addfield .  $exp . $proj . $addfieldgrpby . "
              having (case when sum(ib.qty-ib.iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;

    return $query;
  }

  private function default_displayHeader_SELLING_PRICE($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $end       = $config['params']['dataparams']['end'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


    $partid    = $config['params']['dataparams']['partid'];
    $partname  = $config['params']['dataparams']['partname'];

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


    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }
    $datelabel = 'Balance as of : ' . $asof;
    if ($companyid == 60) { //transpower
      $dtagathering = '';
      $asof = date('Y-m-d', strtotime($asof));
      $end = date('Y-m-d', strtotime($end));
      $datelabel = 'Date from: ' . $asof . ' ' . ' to: ' . $end;
    }

    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($datelabel, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($companyid == 14) { //majesty
      if ($groupname == '') {
        $str .= $this->reporter->col('Division : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Division : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($groupname == '') {
        $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($companyid == 14) { //majesty
      if ($partname == '') {
        $str .= $this->reporter->col('Principal : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Principal : ' . $partname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');



    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('Generic : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_selling_price_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];

    $padding = '';
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($companyid == 10) { //afti
      $str .= $this->reporter->col('SKU/PART NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '140', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', $padding, '8px');
    }

    $str .= $this->reporter->col('ITEM DESCRIPTION', '400', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', $padding, '8px');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsolparanaque
      case 52: //technolab
        $str .= $this->reporter->col('LOT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        break;
      case 17: //unihome
      case 39: //CBBSI
        $str .= $this->reporter->col('LAST REC DATE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        $str .= $this->reporter->col('LAST SOLD DATE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        break;
      case 24: //goodfound
        $str .= $this->reporter->col('LOCATION', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        break;
      case 50: //unitech
        $str .= $this->reporter->col('BRAND', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
        break;
    }

    $str .= $this->reporter->col('UOM', '60', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', $padding, '8px');
    $str .= $this->reporter->col('SRP', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', $padding, '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', $padding, '8px');
    $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', $padding, '8px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout_SELLING_PRICE($config)
  {
    $str = '';

    try {
      //oks
      $result = $this->reportDefault($config);

      $border = '1px solid';
      $border_line = '';
      $alignment = '';
      $font = $this->companysetup->getrptfont($config['params']);
      $font_size = 10;
      $padding = '';
      $margin = '8px';

      $center     = $config['params']['center'];
      $username   = $config['params']['user'];
      $companyid = $config['params']['companyid'];

      $asof       = $config['params']['dataparams']['start'];
      $client     = $config['params']['dataparams']['client'];
      $clientname = $config['params']['dataparams']['clientname'];
      $barcode    = $config['params']['dataparams']['barcode'];
      $itemname   = $config['params']['dataparams']['itemname'];
      $classid    = $config['params']['dataparams']['classid'];
      $classname  = $config['params']['dataparams']['classic'];
      $categoryid = $config['params']['dataparams']['categoryid'];
      $categoryname  = $config['params']['dataparams']['categoryname'];
      $groupid    = $config['params']['dataparams']['groupid'];
      $groupname  = $config['params']['dataparams']['stockgrp'];
      $brandid    = $config['params']['dataparams']['brandid'];
      $brandname  = $config['params']['dataparams']['brandname'];
      $modelid    = $config['params']['dataparams']['modelid'];
      $modelname  = $config['params']['dataparams']['modelname'];
      $wh         = $config['params']['dataparams']['wh'];
      $whname     = $config['params']['dataparams']['whname'];
      $amountformat   = $config['params']['dataparams']['amountformat'];
      $itemstock  = $config['params']['dataparams']['itemstock'];
      $itemtype   = $config['params']['dataparams']['itemtype'];

      $skipnegative = false;
      switch ($companyid) {
        case 49: //hotmix
          $count = 56;
          $page = 56;
          $skipnegative = true;
          $font_size = 12;
          break;
        default:
          $count = 51;
          $page = 50;
          break;
      }

      if (empty($result)) {
        return $this->othersClass->emptydata($config);
      }


      $layoutsize = '1000';
      $str .= $this->reporter->beginreport($layoutsize);
      $str .= $this->default_displayHeader_SELLING_PRICE($config);
      $str .= $this->default_selling_price_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);

      $totalbalqty = 0;
      $part = "";
      $scatgrp = "";
      $igrp = "";
      $totalext = 0;
      $grandtotal = 0;

      $multiheader = true;
      switch ($companyid) {
        case 14: //majesty
        case 21: //kinggeorge
          $multiheader = false;
          break;
      }

      foreach ($result as $key => $data) {

        $balance = number_format($data->balance, 2);
        if ($balance == 0) {
          $balance = '-';
        }
        $isamt = number_format($data->amt, 2);
        if ($isamt == 0) {
          $isamt = '-';
        }

        $discounted = $this->othersClass->Discount($data->amt, $data->disc);
        //oks
        if ($companyid != 14 && $companyid != 17 && $companyid != 24) { //not majesty,unihome,goodfound
          if ($data->part != 0 || $data->part != null) {
            if (strtoupper($part) == strtoupper($data->part)) {
              $part = "";
            } else {
              $part = strtoupper($data->part);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
              $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            $part = "";
          }

          if ($data->category != 0 || $data->category != null) {
            if (strtoupper($scatgrp) == strtoupper($data->category)) {
              $scatgrp = "";
            } else {
              $scatgrp = strtoupper($data->category);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            $scatgrp = "";
          }
        }

        if ($companyid == 24) { //goodfound

          if ($data->stockgrp_name != 0 || $data->stockgrp_name != null) {
            if (strtoupper($igrp) == strtoupper($data->stockgrp_name)) {
              $igrp = "";
            } else {
              $igrp = strtoupper($data->stockgrp_name);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($igrp, '75', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            $igrp = "";
          }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        if ($companyid == 40) { //cdo
          $str .= $this->reporter->col($data->partno, '140', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col($data->barcode, '140', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        }



        if ($companyid == 17) { //unihome
          $lastreceive = "select h.dateid from lahead as h
                  left join lastock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='RR' and s.itemid = $data->itemid
                  union all
                  select h.dateid from glhead as h
                  left join glstock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='RR' and s.itemid = $data->itemid
                  order by dateid desc limit 1";
          $lrdateresult =  $this->coreFunctions->opentable($lastreceive);

          $lastsell = "select h.dateid from lahead as h
                  left join lastock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  union all
                  select h.dateid from glhead as h
                  left join glstock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  order by dateid desc limit 1";
          $lsdateresult =  $this->coreFunctions->opentable($lastsell);

          $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

          if (empty($lrdateresult)) {
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
          } else {
            foreach ($lrdateresult as $key1 => $lrdatedata) {
              $str .= $this->reporter->col($lrdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            }
          }

          if (empty($lsdateresult)) {
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
          } else {
            foreach ($lsdateresult as $key1 => $lsdatedata) {
              $str .= $this->reporter->col($lsdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            }
          }
        } else {
          if ($companyid == 47) { //kitchenstar
            $str .= $this->reporter->col($data->itemname . ' ' . $data->color . ' ' . $data->sizeid, '400', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
          } else {
            $str .= $this->reporter->col($data->itemname, '400', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
          }
        }
        switch ($companyid) {
          case 1: //vitaline
          case 23: //labsol cebu
          case 41: //labsolparanaque
          case 52: //technolab
            $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
            break;
          case 24: //goodfound
            $str .= $this->reporter->col($data->subcatname, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            break;
          case 50: //unitech
            $str .= $this->reporter->col($data->brandname, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            break;
        }

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $itemserialno = '';

            $serialdata = $this->serialquery2($data->itemid);
            if (!empty($serialdata)) {
              foreach ($serialdata as $key => $value) {
                $itemserialno .= $value['serialno'];
              }
            }
            $itemserialno = rtrim($itemserialno, ", ");
            $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            break;
        }

        $totalext = $data->balance * $discounted;

        if ($totalext == 0) {
          $totalext = '-';
        } else {
          $totalext = number_format($totalext, 2);
        }
        #120 380 100 100 100 100 100
        $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($discounted, 2), '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $scatgrp = strtoupper($data->category);
        $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
        $part = $data->part;

        if ($skipnegative) {
          if ($data->balance >= 0) {
            $grandtotal = $grandtotal + ($data->balance * $discounted);
            $totalbalqty = $totalbalqty + $data->balance;
          }
        } else {
          $grandtotal = $grandtotal + ($data->balance * $discounted);
          $totalbalqty = $totalbalqty + $data->balance;
        }

        if ($multiheader) {
          if ($this->reporter->linecounter >= $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
            if (!$allowfirstpage) {
              $str .= $this->default_displayHeader_SELLING_PRICE($config);
            }
            $str .= $this->default_selling_price_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);
            $page = $page + $count;
          }
        }
      }

      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= '<br/>';
      $str .= $this->reporter->startrow();

      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
        case 41: //labsol manila
        case 52: //technolab
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          break;
        case 17: //unihome
        case 28: //xcomp
        case 39: //CBBSI
          $str .= $this->reporter->col('OVERALL STOCKS :', '670', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          break;
      }

      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
      $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', $padding, '8px');
      $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', $padding, '8px');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
      $str .= $this->reporter->endreport();
    } catch (Exception $e) {
      $this->othersClass->logConsole('Exception' . $e->getMessage());
    }

    return $str;
  }

  private function default_displayHeader_LATEST_COST($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $end       = $config['params']['dataparams']['end'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


    $partid    = $config['params']['dataparams']['partid'];
    $partname  = $config['params']['dataparams']['partname'];

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


    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }
    $datelabel = 'Balance as of : ' . $asof;
    if ($companyid == 60) { //transpower
      $dtagathering = '';
      $asof = date('Y-m-d', strtotime($asof));
      $end = date('Y-m-d', strtotime($end));
      $datelabel = 'Date from: ' . $asof . ' ' . ' to: ' . $end;
    }

    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($datelabel, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($companyid == 14) { //majesty
      if ($groupname == '') {
        $str .= $this->reporter->col('Division : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Division : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($groupname == '') {
        $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($companyid == 14) { //majesty
      if ($partname == '') {
        $str .= $this->reporter->col('Principal : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Principal : ' . $partname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('Generic : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_latest_cost_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($companyid == 10) { //afti
      $str .= $this->reporter->col('SKU/PART NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '140', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    }

    $str .= $this->reporter->col('ITEM DESCRIPTION', '440', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsolcebu
      case 41: //labsolparanaque
      case 52: //technolab
        $str .= $this->reporter->col('LOT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
      case 17: //unihome
      case 39: //CBBSI
        $str .= $this->reporter->col('LAST REC DATE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        $str .= $this->reporter->col('LAST SOLD DATE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
      case 24: //goodfound
        $str .= $this->reporter->col('LOCATION', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
      case 50: //unitech
        $str .= $this->reporter->col('BRAND', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
    }

    $str .= $this->reporter->col('UOM', '40', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('COST', '80', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');

    return $str;
  }

  public function reportDefaultLayout_LATEST_COST($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 10;
    $padding = '';
    $margin = '8px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $skipnegative = false;
    switch ($companyid) {
      case 49: //hotmix
        $count = 56;
        $page = 56;
        $skipnegative = true;
        $font_size = 12;
        break;
      default:
        $count = 51;
        $page = 50;
        break;
    }
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_LATEST_COST($config);

    $str .= $this->default_latest_cost_table_cols($this->reportParams['layoutSize'], $border, $font,  $font_size, $config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $igrp = "";
    $totalext = 0;
    $grandtotal = 0;

    $multiheader = true;
    switch ($companyid) {
      case 14: //majesty
        $multiheader = false;
        break;
    }

    foreach ($result as $key => $data) {

      $balance = number_format($data->balance, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $cost = $this->getLatestCost($data->itemid);

      //not majesty, unihome & goodfound
      if ($companyid != 14 && $companyid != 17 && $companyid != 24) {
        if ($data->part != 0 || $data->part != null) {
          if (strtoupper($part) == strtoupper($data->part)) {
            $part = "";
          } else {
            $part = strtoupper($data->part);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $part = "";
        }

        if ($data->category != 0 || $data->category != null) {
          if (strtoupper($scatgrp) == strtoupper($data->category)) {
            $scatgrp = "";
          } else {
            $scatgrp = strtoupper($data->category);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $scatgrp = "";
        }
      }


      if ($companyid == 24) { //goodfound

        if ($data->stockgrp_name != 0 || $data->stockgrp_name != null) {
          if (strtoupper($igrp) == strtoupper($data->stockgrp_name)) {
            $igrp = "";
          } else {
            $igrp = strtoupper($data->stockgrp_name);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($igrp, '75', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $igrp = "";
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 40) { //cdo
        $str .= $this->reporter->col($data->partno, '140', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col($data->barcode, '140', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
      }

      if ($companyid == 17) { //unihome
        $lastreceive = "select h.dateid from lahead as h
                  left join lastock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='RR' and s.itemid = $data->itemid
                  union all
                  select h.dateid from glhead as h
                  left join glstock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='RR' and s.itemid = $data->itemid
                  order by dateid desc limit 1";
        $lrdateresult =  $this->coreFunctions->opentable($lastreceive);

        $lastsell = "select h.dateid from lahead as h
                  left join lastock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  union all
                  select h.dateid from glhead as h
                  left join glstock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  order by dateid desc limit 1";
        $lsdateresult =  $this->coreFunctions->opentable($lastsell);

        $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

        if (empty($lrdateresult)) {
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
        } else {
          foreach ($lrdateresult as $key1 => $lrdatedata) {
            $str .= $this->reporter->col($lrdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
          }
        }

        if (empty($lsdateresult)) {
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
        } else {
          foreach ($lsdateresult as $key1 => $lsdatedata) {
            $str .= $this->reporter->col($lsdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
          }
        }
      } else {

        if ($companyid == 47) { //kitchenstar
          $str .= $this->reporter->col($data->itemname . ' ' . $data->color . ' ' . $data->sizeid, '460', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col($data->itemname, '440', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        }
      }

      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
        case 41: //labsolparanaque
        case 52: //technolab
          $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
        case 24: //goodfound
          $str .= $this->reporter->col($data->subcatname, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          break;
        case 50: //unitech
          $str .= $this->reporter->col($data->brandname, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          break;
      }

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $itemserialno = '';
          $serialdata = $this->serialquery2($data->itemid);
          if (!empty($serialdata)) {
            foreach ($serialdata as $key => $value) {
              $itemserialno .= $value['serialno'];
            }
          }
          $itemserialno = rtrim($itemserialno, ", ");
          $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
          break;
      }
      $totalext = $data->balance * $cost;
      $costv = $cost;
      if ($cost == 0) {
        $cost = '-';
      } else {
        if ($companyid == 23) { //labsol cebu
          $cost = number_format($cost, 6);
        } else {
          $cost = number_format($cost, 2);
        }
      }

      if ($totalext == 0) {
        $totalext = '-';
      } else {
        if ($companyid == 23) { //labsol cebu
          $totalext = number_format($totalext, 6);
        } else {
          $totalext = number_format($totalext, 2);
        }
      }

      $str .= $this->reporter->col($data->uom, '40', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($cost, '80', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $scatgrp = strtoupper($data->category);
      $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
      $part = $data->part;

      if ($skipnegative) {
        if ($data->balance >= 0) {
          $totalbalqty = $totalbalqty + $data->balance;
          $grandtotal = $grandtotal + ($data->balance * $costv);
        }
      } else {
        $totalbalqty = $totalbalqty + $data->balance;
        $grandtotal = $grandtotal + ($data->balance * $costv);
      }

      if ($multiheader) {

        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$allowfirstpage) {
            $str .= $this->default_displayHeader_LATEST_COST($config);
          }
          $str .= $this->default_latest_cost_table_cols($this->reportParams['layoutSize'], $border, $font,  $font_size, $config);
          $page = $page + $count;
        }
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
      case 41: //labsol manila
      case 52: //technolab
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $str .= $this->reporter->col('OVERALL STOCKS :', '680', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
    }

    switch ($companyid) {
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function default_displayHeader_NONE($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $end       = $config['params']['dataparams']['end'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
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

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }
    $datelabel = 'Balance as of : ' . $asof;
    if ($companyid == 60) { //transpower
      $dtagathering = '';
      $asof = date('Y-m-d', strtotime($asof));
      $end = date('Y-m-d', strtotime($end));
      $datelabel = 'Date from: ' . $asof . ' ' . ' to: ' . $end;
    }
    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($datelabel, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($companyid == 14) { //majesty
      if ($groupname == '') {
        $str .= $this->reporter->col('Division : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Division : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($groupname == '') {
        $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($companyid == 14) { //majesty
      $partid    = $config['params']['dataparams']['partid'];
      $partname  = $config['params']['dataparams']['partname'];
      if ($partname == '') {
        $str .= $this->reporter->col('Principal : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Principal : ' . $partname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($categoryname == '') {
        $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('Generic : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $subcatname = $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }
  //aa
  private function default_none_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($companyid == 10) { //afti
      $str .= $this->reporter->col('SKU/PART NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    }

    $str .= $this->reporter->col('ITEM DESCRIPTION', '420', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsolparanaque
      case 52: //technolab
        $str .= $this->reporter->col('LOT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
      case 17: //unihome
      case 39: //CBBSI
        $str .= $this->reporter->col('LAST REC DATE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        $str .= $this->reporter->col('LAST SOLD DATE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
      case 24: //goodfound
        $str .= $this->reporter->col('LOCATION', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
      case 50: //unitech
        $str .= $this->reporter->col('BRAND', '100', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        break;
    }

    $str .= $this->reporter->col('UOM', '60', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    switch ($companyid) {
      case 50:
        break;
      default:
        if ($itemstock != '(0,1)') {
          $str .= $this->reporter->col('SRP', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
          $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
        }
        break;
    }

    if ($amountformat == 'testing') {
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    } else {
      $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    }

    return $str;
  }

  public function reportDefaultLayout_NONE($config)
  {
    $str = '';
    try {
      $result = $this->reportDefault($config);

      $border = '1px solid';
      $border_line = '';
      $alignment = '';
      $font = $this->companysetup->getrptfont($config['params']);
      $font_size = 10;
      $fontsize11 = 11;
      $padding = '';
      $margin = '8px';

      // $center     = $config['params']['center'];
      // $username   = $config['params']['user'];
      $companyid = $config['params']['companyid'];

      if ($companyid == 49) {
        $font_size = 12;
      }

      // $asof       = $config['params']['dataparams']['start'];
      // $client     = $config['params']['dataparams']['client'];
      // $clientname = $config['params']['dataparams']['clientname'];
      // $barcode    = $config['params']['dataparams']['barcode'];
      // $itemname   = $config['params']['dataparams']['itemname'];
      // $classid    = $config['params']['dataparams']['classid'];
      // $classname  = $config['params']['dataparams']['classic'];
      // $categoryid = $config['params']['dataparams']['categoryid'];
      // $categoryname  = $config['params']['dataparams']['categoryname'];
      // $groupid    = $config['params']['dataparams']['groupid'];
      // $groupname  = $config['params']['dataparams']['stockgrp'];
      // $brandid    = $config['params']['dataparams']['brandid'];
      // $brandname  = $config['params']['dataparams']['brandname'];
      // $modelid    = $config['params']['dataparams']['modelid'];
      // $modelname  = $config['params']['dataparams']['modelname'];
      // $wh         = $config['params']['dataparams']['wh'];
      // $whname     = $config['params']['dataparams']['whname'];
      // $amountformat   = $config['params']['dataparams']['amountformat'];
      $itemstock  = isset($config['params']['dataparams']['itemstock']) ? $config['params']['dataparams']['itemstock'] : '(0,1)';
      // $itemtype   = $config['params']['dataparams']['itemtype'];

      $count = 51;
      $page = 50;

      $this->reporter->linecounter = 0;

      if (empty($result)) {
        return $this->othersClass->emptydata($config);
      }


      $layoutsize = '1000';
      $str .= $this->reporter->beginreport($layoutsize);
      $str .= $this->default_displayHeader_NONE($config);
      $str .= $this->default_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

      $totalbalqty = 0;
      $part = "";
      $scatgrp = "";
      $igrp = "";
      $totalext = 0;
      $grandtotal = 0;
      $cost = 0;

      $multiheader = true;

      if (isset($config['params']['multiheader'])) {
        $multiheader = $config['params']['multiheader'];
      }

      switch ($companyid) {
        case 14: //majesty
          $multiheader = false;
          break;
      }

      foreach ($result as $key => $data) {

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

        //not majesty,unihome & goodfound
        if ($companyid != 14 && $companyid != 17 && $companyid != 24) {

          if ($data->part != 0 || $data->part != null) {
            if (strtoupper($part) == strtoupper($data->part)) {
              $part = "";
            } else {
              $part = strtoupper($data->part);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
              $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            $part = "";
          }

          if ($data->category != 0 || $data->category != null) {
            if (strtoupper($scatgrp) == strtoupper($data->category)) {
              $scatgrp = "";
            } else {
              $scatgrp = strtoupper($data->category);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            $scatgrp = "";
          }
        }

        if ($companyid == 24) { //goodfound
          if ($data->stockgrp_name != 0 || $data->stockgrp_name != null) {
            if (strtoupper($igrp) == strtoupper($data->stockgrp_name)) {
              $igrp = "";
            } else {
              $igrp = strtoupper($data->stockgrp_name);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($igrp, '75', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            $igrp = "";
          }
        }

        $totalext = $data->balance * $data->amt;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        switch ($companyid) {
          case 40: //cdo
            $str .= $this->reporter->col($data->partno == '' ? '-' : $data->partno, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            break;
          default:
            $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            break;
        }

        switch ($companyid) {
          case 17: //unihome
            $lastreceive = "select h.dateid from lahead as h
            left join lastock as s on s.trno=h.trno
            left join item as i on i.itemid=s.itemid
            where doc='RR' and s.itemid = $data->itemid
            union all
            select h.dateid from glhead as h
            left join glstock as s on s.trno=h.trno
            left join item as i on i.itemid=s.itemid
            where doc='RR' and s.itemid = $data->itemid
            order by dateid desc limit 1";
            $lrdateresult =  $this->coreFunctions->opentable($lastreceive);

            $lastsell = "select h.dateid from lahead as h
            left join lastock as s on s.trno=h.trno
            left join item as i on i.itemid=s.itemid
            where doc='SJ' and s.itemid = $data->itemid
            union all
            select h.dateid from glhead as h
            left join glstock as s on s.trno=h.trno
            left join item as i on i.itemid=s.itemid
            where doc='SJ' and s.itemid = $data->itemid
            order by dateid desc limit 1";
            $lsdateresult =  $this->coreFunctions->opentable($lastsell);

            $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

            if (empty($lrdateresult)) {
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            } else {
              foreach ($lrdateresult as $key1 => $lrdatedata) {
                $str .= $this->reporter->col($lrdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
              }
            }

            if (empty($lsdateresult)) {
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
            } else {
              foreach ($lsdateresult as $key1 => $lsdatedata) {
                $str .= $this->reporter->col($lsdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
              }
            }
            break;
          default:
            if ($companyid == 47) { //kitchenstar
              $str .= $this->reporter->col($data->itemname . ' ' . $data->color . ' ' . $data->sizeid, '420', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            } else {
              $str .= $this->reporter->col($data->itemname, '420', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            }
            break;
        }

        switch ($companyid) {
          case 1: //vitaline
          case 23: //labsol cebu
          case 41: //labsolparanaque
          case 52: //technolab
            $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
            break;
          case 24: //goodfound
            $str .= $this->reporter->col($data->subcatname, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            break;
          case 50: //unitech
            $str .= $this->reporter->col($data->brandname, '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            break;
        }

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $itemserialno = '';
            $serialdata = $this->serialquery2($data->itemid);
            if (!empty($serialdata)) {
              foreach ($serialdata as $key => $value) {
                $itemserialno .= $value['serialno'];
              }
            }
            $itemserialno = rtrim($itemserialno, ", ");
            $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
            break;
          default:
            $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            break;
        }

        $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        switch ($companyid) {
          case 50: //unitech
            break;

          default:
            if ($itemstock != '(0,1)') {
              $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
              $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
            }
            break;
        }
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $scatgrp = strtoupper($data->category);
        $part = strtoupper($data->part);
        $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';

        $grandtotal = $grandtotal + $totalext;
        $totalbalqty = $totalbalqty + $data->balance;


        if ($multiheader) {
          if ($this->reporter->linecounter >= $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->reporter->begintable($layoutsize);
            $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);

            if (!$allowfirstpage) {
              $str .= $this->default_displayHeader_NONE($config);
            }
            $str .= $this->default_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
            $page = $page + $count;
          }
        }
      }

      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable($layoutsize);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
          break;
      }

      switch ($companyid) {
        case 17: //unihome
        case 28: //xcomp
        case 39: //CBBSI
          $str .= $this->reporter->col('OVERALL STOCKS :', '680', null, false, '1px solid ', 'TB', 'r', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', '', '');
          $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          break;
        case 50: //unitech
          $str .= $this->reporter->col('OVERALL STOCKS :', '375', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('OVERALL STOCKS :', '375', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
          break;
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
      $str .= $this->reporter->endreport();
    } catch (Exception $e) {
      $this->othersClass->logConsole('Exception' . $e->getMessage());
    }

    return $str;
  }

  //hotmix none option

  private function hotmix_none_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '130', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '390', null, false, '1px solid ', 'B', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('UOM', '80', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '8px');

    if ($itemstock == '(0)') {
      $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('SRP', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    } else {
      $str .= $this->reporter->col('COUNT', '200', null, false, '1px solid ', 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function hotmixLayout_NONE($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 12;
    $padding = '';
    $margin = '8px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 37;
    $page = 37;

    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_NONE($config);
    $str .= $this->hotmix_none_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $igrp = "";
    $totalext = 0;
    $grandtotal = 0;
    $cost = 0;

    $multiheader = true;

    foreach ($result as $key => $data) {
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

      if ($data->part != 0 || $data->part != null) {
        if (strtoupper($part) == strtoupper($data->part)) {
          $part = "";
        } else {
          $part = strtoupper($data->part);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $part = "";
      }

      if ($data->category != 0 || $data->category != null) {
        if (strtoupper($scatgrp) == strtoupper($data->category)) {
          $scatgrp = "";
        } else {
          $scatgrp = strtoupper($data->category);
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
          $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
      } else {
        $scatgrp = "";
      }

      $totalext = $data->balance * $data->amt;
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if ($itemstock == '(0)') {
        $border = '';
        $border1 = 'B';
      } else {
        $border = 'LB';
        $border1 = 'LBR';
      }
      $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', $border, 'LT', $font, $font_size, '', '', '4px');
      $str .= $this->reporter->col($data->itemname, '400', null, false, '1px solid ', $border, 'LT', $font, $font_size, '', '', '4px');
      $str .= $this->reporter->col($data->uom, '80', null, false, '1px solid ', $border, 'CT', $font, $font_size, '', '', '4px');


      if ($itemstock == '(0)') {
        $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', $border, 'RT', $font, $font_size, '', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', $border, 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', $border, 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', $border1, 'CT', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'LBR', 'CT', $font, $font_size, '', '', '4px');
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $scatgrp = strtoupper($data->category);
      $part = strtoupper($data->part);
      $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';

      $grandtotal = $grandtotal + $totalext;
      $totalbalqty = $totalbalqty + $data->balance;


      if ($multiheader) {
        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          // $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);

          if (!$allowfirstpage) {
            $str .= $this->default_displayHeader_NONE($config);
          }
          $str .= $this->hotmix_none_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $config);
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  //AFTI SELLING PRICE
  private function Afti_displayHeader_SELLING_PRICE($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $proj   = $config['params']['dataparams']['project'];
    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
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

    $str .= $this->reporter->col('INVENTORY BALANCE', null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');




    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('SKU/PART NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('WAREHOUSE NAME', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE QTY', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('COST', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('COUNT', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportAftiLayout_SELLING_PRICE($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->Afti_displayHeader_SELLING_PRICE($config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {



      $balance = number_format($data->balance, 0);
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
      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');


      $str .= $this->reporter->col($data->projname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->brandname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->whname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');


      $totalext = $data->balance * $discounted;

      if ($totalext == 0) {
        $totalext = '-';
      } else {
        $totalext = number_format($totalext, 2);
      }

      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($discounted, 2), '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($totalext, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $itemserialno = '';

      $serialdata = $this->serialquery2($data->itemid);
      if (!empty($serialdata)) {
        foreach ($serialdata as $key => $value) {
          $itemserialno .= $value['serialno'];
        }
      }
      $itemserialno = rtrim($itemserialno, ", ");
      $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      $scatgrp = strtoupper($data->category);
      $part = $data->part;
      $grandtotal = $grandtotal + ($data->balance * $discounted);
      $totalbalqty = $totalbalqty + $data->balance;
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', 'Bi', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 0), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');

    $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');






    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  //AFTI LATEST COST
  private function Afti_displayHeader_LATEST_COST($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $proj   = $config['params']['dataparams']['project'];
    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
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

    $str .= $this->reporter->col('INVENTORY BALANCE', null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');

    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SKU/PART NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('WAREHOUSE NAME', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE QTY', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('COST', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('COUNT', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    return $str;
  }

  public function reportAftiLayout_LATEST_COST($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $count = 46;
    $page = 45;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->Afti_displayHeader_LATEST_COST($config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $balance = number_format($data->balance, 0);
      if ($balance == 0) {
        $balance = '-';
      }
      $cost = number_format($data->cost, 4);
      if ($cost == 0) {
        $cost = '-';
      }

      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->projname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->brandname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->whname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

      $totalext = $data->balance * round($data->cost, 4);
      if ($totalext == 0) {
        $totalext = '-';
      } else {
        $totalext = number_format($totalext, 2);
      }
      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($cost, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($totalext, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

      $itemserialno = '';
      $serialdata = $this->serialquery2($data->itemid);
      if (!empty($serialdata)) {
        foreach ($serialdata as $key => $value) {
          $itemserialno .= $value['serialno'];
        }
      }
      $itemserialno = rtrim($itemserialno, ", ");
      $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'LT', $font, '10', '', '', '');

      $scatgrp = strtoupper($data->category);
      $part = $data->part;
      $totalbalqty = $totalbalqty + $data->balance;
      $grandtotal = $grandtotal + ($data->balance * $data->cost);
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('OVERALL STOCKS :', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', 'Bi', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 0), '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  //AFTI NONE
  private function Afti_displayHeader_NONE($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


    $proj   = $config['params']['dataparams']['project'];
    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
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

    $str .= $this->reporter->col('INVENTORY BALANCE', null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');

    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $subcatname = $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('SKU/PART NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('WAREHOUSE NAME', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');

    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE QTY', '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    if ($itemstock != 'None') {
      $str .= $this->reporter->col('COST', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    }
    $str .= $this->reporter->col('SERIAL NO.', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('COUNT', '75', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->endrow();


    return $str;
  }

  public function reportAftiLayout_NONE($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $count = 46;
    $page = 45;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->Afti_displayHeader_NONE($config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;

    foreach ($result as $key => $data) {


      $str .= $this->reporter->addline();




      $balance = number_format($data->balance, 0);


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



      $totalext = $data->balance * $data->amt;


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->projname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->brandname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->whname, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      if ($itemstock != '(0,1)') {

        $str .= $this->reporter->col($isamt, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      }
      $itemserialno = '';
      $serialdata = $this->serialquery2($data->itemid);
      if (!empty($serialdata)) {
        foreach ($serialdata as $key => $value) {
          $itemserialno .= $value['serialno'];
        }
      }
      $itemserialno = rtrim($itemserialno, ", ");
      $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'CT', $font, '10', '', '', '');

      $scatgrp = strtoupper($data->category);
      $part = strtoupper($data->part);
      $grandtotal = $grandtotal + $totalext;
      $totalbalqty = $totalbalqty + $data->balance;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'L', $font, '10', 'Bi', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 0), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    if ($itemstock != '(0,1)') {
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');
    }
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', '', '', '');




    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function kinggeorge_header($config, $p_fontsize)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = $p_fontsize;
    $padding = '';
    $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $end       = $config['params']['dataparams']['end'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
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
    if ($companyid == 50) { //unitech
      $layoutsize = '1100';
    }
    $datelabel = 'Balance as of : ' . $asof;
    if ($companyid == 60) {
      $asof = date('Y-m-d', strtotime($asof));
      $end = date('Y-m-d', strtotime($end));
      $datelabel = 'Date from: ' . $asof . ' ' . ' to: ' . $end;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('INVENTORY BALANCE REPORT', null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($datelabel, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '140', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '500', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');

    if ($companyid == 50) { //unitech
      $str .= $this->reporter->col('BRAND', '100', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    }
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('UOM', '60', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('UNIT COST', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('TOTAL COST', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function report_kinggeorge_cost($config)
  {

    $result = $this->reportDefault($config);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $count = 46;
    $page = 45;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    if ($companyid == 50) { //unitech
      $layoutsize = '1100';
    }
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_header($config, $font_size);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {

      $balance = number_format($data->balance, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $latestCost = $this->getLatestCost($data->itemid);
      if ($latestCost == '') {
        $latestCost = 0;
      }

      if ($companyid == 21) {

        $cost = number_format($latestCost, 2);
      } else {
        $cost = number_format($data->cost, 2);
      }

      if ($cost == 0) {
        $cost = '-';
      }

      if ($companyid != 14 && $companyid != 17) { //not majesty & unihome
        if ($data->part != 0 || $data->part != null) {
          if (strtoupper($part) == strtoupper($data->part)) {
            $part = "";
          } else {
            $part = strtoupper($data->part);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $part = "";
        }

        if ($data->category != 0 || $data->category != null) {
          if (strtoupper($scatgrp) == strtoupper($data->category)) {
            $scatgrp = "";
          } else {
            $scatgrp = strtoupper($data->category);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($scatgrp, '100', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $scatgrp = "";
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

      if ($companyid == 17) { //unihome
        $lastsell = "select h.dateid from lahead as h
                  left join lastock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  union all
                  select h.dateid from glhead as h
                  left join glstock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  order by dateid desc limit 1";
        $lsdateresult =  $this->coreFunctions->opentable($lastsell);
        $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

        if (empty($lsdateresult)) {
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
        } else {
          foreach ($lsdateresult as $key1 => $lsdatedata) {
            $str .= $this->reporter->col($lsdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
          }
        }
      } else {
        if ($companyid == 47) { //kitchenstar
          $str .= $this->reporter->col($data->itemname . ' ' . $data->color . ' ' . $data->sizeid, '150', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        } else {
          $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        }
      }

      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
          $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
          break;
      }

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $itemserialno = '';
          $serialdata = $this->serialquery2($data->itemid);
          if (!empty($serialdata)) {
            foreach ($serialdata as $key => $value) {
              $itemserialno .= $value['serialno'];
            }
          }
          $itemserialno = rtrim($itemserialno, ", ");
          $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          break;
      }

      if ($companyid == 21) {
        $totalext = $data->balance * $latestCost;
      } else {
        $totalext = $data->balance * $data->cost;
      }


      if ($totalext == 0) {
        $totalext = '-';
      } else {
        $totalext = number_format($totalext, 2);
      }

      $str .= $this->reporter->col($balance, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->uom, '75', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->col($cost, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($totalext, '75', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');

      $str .= $this->reporter->endrow();

      $scatgrp = strtoupper($data->category);
      $part = $data->part;
      $totalbalqty = $totalbalqty + $data->balance;

      if ($companyid == 21) {
        $grandtotal = $grandtotal + ($data->balance * $latestCost);
      } else {
        $grandtotal = $grandtotal + ($data->balance * $data->cost);
      }


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->kinggeorge_header($config, $font_size);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $str .= $this->reporter->col('OVERALL STOCKS :', '680', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        break;
    }

    switch ($companyid) {
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'TB', '', '', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'TB', '', '', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col('', '125', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_avecost($config)
  {
    $result = $this->reportDefaultAveCost($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 10;
    $padding = '';
    $margin = '8px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $skipnegative = false;

    switch ($companyid) {
      case 49: //hotmix
        $count = 45;
        $page = 45;
        $skipnegative = true;
        $font_size = 12;
        break;
      default:
        $count = 55;
        $page = 55;
        break;
    }
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    if ($companyid == 50) { //unitech
      $layoutsize = '1100';
    }
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_header($config, $font_size);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $totalext = 0;
    $grandtotal = 0;
    $latestCost = 0;
    $multiheader = true;

    $decimal_place = 2;
    switch ($companyid) {
      case 14: //majesty
        $multiheader = false;
        break;
    }
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {

      if ($companyid == 36) { //ROZLAB
        $decimal_place = 4;
      }

      $balance = number_format($data->balance, $decimal_place);
      if ($balance == 0) {
        $balance = '-';
      }

      $latestCost = $this->getLatestCost($data->itemid);

      if ($latestCost != '') {
        $cost = number_format($latestCost, $decimal_place);
      }

      if ($cost == 0) {
        $cost = '-';
      }


      if ($companyid != 14 && $companyid != 17) { //not majesty & unihome
        if ($data->part != 0 || $data->part != null) {
          if (strtoupper($part) == strtoupper($data->part)) {
            $part = "";
          } else {
            $part = strtoupper($data->part);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $part = "";
        }

        if ($data->category != 0 || $data->category != null) {
          if (strtoupper($scatgrp) == strtoupper($data->category)) {
            $scatgrp = "";
          } else {
            $scatgrp = strtoupper($data->category);
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
        } else {
          $scatgrp = "";
        }
      }
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '140', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

      if ($companyid == 17) { // unihome
        $lastsell = "select h.dateid from lahead as h
                  left join lastock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  union all
                  select h.dateid from glhead as h
                  left join glstock as s on s.trno=h.trno
                  left join item as i on i.itemid=s.itemid
                  where doc='SJ' and s.itemid = $data->itemid
                  order by dateid desc limit 1";
        $lsdateresult =  $this->coreFunctions->opentable($lastsell);
        $str .= $this->reporter->col($data->itemname, '150', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');

        if (empty($lsdateresult)) {
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
        } else {
          foreach ($lsdateresult as $key1 => $lsdatedata) {
            $str .= $this->reporter->col($lsdatedata->dateid, '100', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
          }
        }
      } else {
        if ($companyid == 47) { //kitchenstar
          $str .= $this->reporter->col($data->itemname . ' ' . $data->color . ' ' . $data->sizeid, '520', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col($data->itemname, '500', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        }
      }

      switch ($companyid) {
        case 1: //vitaline
        case 23: //labsol cebu
        case 41: //labsol paranaque
        case 52: //technolab
          $str .= $this->reporter->col($data->loc, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
        case 50: //unitech
          $str .= $this->reporter->col($data->brandn, '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
          break;
      }

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $itemserialno = '';
          $serialdata = $this->serialquery2($data->itemid);
          if (!empty($serialdata)) {
            foreach ($serialdata as $key => $value) {
              $itemserialno .= $value['serialno'];
            }
          }
          $itemserialno = rtrim($itemserialno, ", ");
          $str .= $this->reporter->col($itemserialno, '75', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
          break;
      }

      $totalext = $data->balance * $data->cost;
      if ($totalext == 0) {
        $totalext = '-';
      } else {
        $totalext = number_format($totalext, 2);
      }

      $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($cost, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();
      $scatgrp = strtoupper($data->category);
      $part = $data->part;

      if ($skipnegative) {
        if ($data->balance >= 0) {
          $totalbalqty = $totalbalqty + $data->balance;
          $grandtotal = $grandtotal + ($data->balance * $data->cost);
        }
      } else {
        $totalbalqty = $totalbalqty + $data->balance;
        $grandtotal = $grandtotal + ($data->balance * $data->cost);
      }

      //oks
      if ($multiheader) {
        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);

          switch ($companyid) {
            case 49: //hotmix
              if (!$allowfirstpage) {
                $str .= $this->kinggeorge_header($config, $font_size);
              }
              break;

            default:
              if ($allowfirstpage) {
                $str .= $this->kinggeorge_header($config, $font_size);
              }
              break;
          }


          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $str .= $this->reporter->col('OVERALL STOCKS :', '680', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
      case 50: //unitech
        $str .= $this->reporter->col('OVERALL STOCKS :', '540', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('OVERALL STOCKS :', '500', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
    }

    switch ($companyid) {
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '85', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        break;
      case 50: //unitech
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'TB', '', $padding, '8px');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function getLatestCost($itemid)
  {
    // $whid = $config['params']['dataparams']['whid'];
    $filer = "";
    // if ($whid != 0) {
    //   $filer = " and whid = $whid";
    // }
    $qry = "select ifnull(cost,0) as value from rrstatus where itemid= ?  $filer  order by dateid desc limit 1";
    return $this->coreFunctions->datareader($qry, [$itemid], '', true);
  }

  private function serialquery($trno, $line)
  {
    $query = "select ifnull(concat(rr.serial,', '),'') as serialno
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where head.trno='$trno' and stock.line = '$line' and rr.outline = 0 
    union all
    select ifnull(concat(rr.serial,', '),'') as serialno
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where head.trno='$trno' and stock.line = '$line' and rr.outline = 0 
    order by serialno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  private function serialquery2($itemid)
  {
    $query = "select ifnull(concat(rr.serial,', '),'') as serialno
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.itemid='$itemid' and rr.outline = 0 
    union all
    select ifnull(concat(rr.serial,', '),'') as serialno
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.itemid='$itemid' and rr.outline = 0
    order by serialno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function mmm_layout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $uom   = $config['params']['dataparams']['uom'];

    $amountformat   = $config['params']['dataparams']['amountformat'];

    $count = 46;
    $page = 45;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('INVENTORY BALANCE', null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Items : ' . ($barcode != "" ? $barcode : "ALL"), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Group : ' . ($groupname != "" ? $groupname : "ALL"), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Brand : ' . ($brandname != "" ? $brandname : "ALL"), '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Category : ' . ($categoryname != "" ? $categoryname : "ALL"), '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse : ' . ($whname != "" ? $whname : "ALL"), '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Model : ' . ($modelname != "" ? $modelname : "ALL"), '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Sub-Category : ' .  ($subcatname != "" ? $subcatname : "ALL"), '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->pagenumber('Page', '200', null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col(($uom != "" ? $uom : ''), '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    if ($amountformat == 'isamt') {
      $str .= $this->reporter->col('SRP', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    } else if ($amountformat == 'rrcost') {
      $str .= $this->reporter->col('COST', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');
    }
    $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '');

    $str .= $this->reporter->endrow();


    $totalbalqty = 0;
    $part = "";
    $wh = "";
    $subtotalqty = 0;
    $scatgrp = "";
    $igrp = "";
    $totalext = 0;
    $grandtotal = 0;

    foreach ($result as $key => $data) {

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


      $latestCost = $this->getLatestCost($data->itemid);
      if ($latestCost == '') {
        $latestCost = 0;
      }

      $cost = number_format($latestCost, 2);

      if ($cost == 0) {
        $cost = '-';
      }

      if ($reporttype == 'wh') {
        if ($wh == '' || strtoupper($wh) != strtoupper($data->whname)) {

          if (strtoupper($wh) != strtoupper($data->whname) && $subtotalqty != 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '');
            $str .= $this->reporter->col('SUB TOTAL STOCKS :', '200', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'TB', '', '', '');
            $str .= $this->reporter->col(number_format($subtotalqty, 2), '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
            $subtotalqty = 0;
          }
          $wh = strtoupper($data->whname);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($wh, '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
        }
      }


      if ($data->part != 0 || $data->part != null) {
        if (strtoupper($part) == strtoupper($data->part)) {
          $part = "";
        } else {
          $part = strtoupper($data->part);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $part = "";
      }

      if ($data->category != 0 || $data->category != null) {
        if (strtoupper($scatgrp) == strtoupper($data->category)) {
          $scatgrp = "";
        } else {
          $scatgrp = strtoupper($data->category);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($scatgrp, '100', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', 'Bi', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $scatgrp = "";
      }

      if ($amountformat == 'isamt') {
        $totalext = $data->balance * $data->amt;
      } elseif ($amountformat == 'rrcost') {
        $totalext = $data->balance * $latestCost;
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      if ($uom != "") {
        $qry = "select ifnull(factor,0) as value from uom where itemid = ? and uom = ?";
        $uombal = $this->coreFunctions->datareader($qry, [$data->itemid, $uom]);
        $str .= $this->reporter->col((($data->balance != 0 && $uombal != 0) ? number_format($data->balance / $uombal, 2) : "NONE"), '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      } else {
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      }
      $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      if ($amountformat == 'isamt') {
        $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        $str .= $this->reporter->col(number_format($totalext), '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      } else if ($amountformat == 'rrcost') {
        $str .= $this->reporter->col($cost, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        $str .= $this->reporter->col(number_format($totalext), '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      }

      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      $scatgrp = strtoupper($data->category);
      $part = strtoupper($data->part);
      if ($reporttype == 'wh') {
        $wh = strtoupper($data->whname);
      }
      $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
      $grandtotal = $grandtotal + $totalext;
      $totalbalqty = $totalbalqty + $data->balance;
      $subtotalqty = $subtotalqty + $data->balance;
    }


    $str .= '<br/>';

    if ($reporttype == 'wh') {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('SUB TOTAL STOCKS :', '200', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'TB', '', '', '');
      $str .= $this->reporter->col(number_format($subtotalqty, 2), '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '');
      $subtotalqty = 0;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '200', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'TB', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function test_qry($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $whname     = $config['params']['dataparams']['whname'];
    $filter = '';
    if ($wh != "") {
      $filter = "where stock.whid='$whid'";
    }
    $query = "select i.barcode,i.itemid,i.itemname, i.qty,i.uom from item as i
              left join lastock as stock on stock.itemid=i.itemid $filter ";
    return $this->coreFunctions->opentable($query);
  }

  public function testingformat($config)
  {
    $result = $this->test_qry($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '8px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $filter = " and item.isimport in $itemtype";
    $excwh = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '';

    $count = 51;
    $page = 50;

    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_NONE($config);
    $str .= $this->default_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
    $totalbal = 0;
    foreach ($result as $key => $data) {
      $qryhere = "select itemid,itemname , uom, sum(qty - iss) as balance
            from (
            select i.itemid,i.itemname,sum(s.qty) as qty,sum(s.iss) as iss,s.uom
            from lahead as head
            left join lastock as s on s.trno = head.trno
            left join item as i on i.itemid = s.itemid
            group by i.itemname, s.uom,i.itemid
            union all
            select i.itemid,i.itemname,sum(s.qty) as qty,sum(s.iss) as iss,s.uom
            from glhead as head
            left join glstock as s on s.trno = head.trno
            left join item as i on i.itemid = s.itemid
            group by i.itemname, s.uom,i.itemid) as a
              group by itemid,itemname,uom";
      $data2 =  $this->coreFunctions->opentable($qryhere);
      foreach ($data2 as $key => $data2) {
        if ($data->itemid == $data2->itemid) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', '', 'C', $font, '10', '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '420', null, false, '1px solid ', '', 'C', $font, '10', '', '', '', '');
          $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
          $str .= $this->reporter->col(number_format($data2->balance, 2), '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
          $totalbal += $data2->balance;
        }
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', 'T', 'C', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('', '420', null, false, '1px solid ', 'T', 'C', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Total', '60', null, false, '1px solid ', 'T', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px solid ', 'T', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function dcurrent_qry($config)
  {

    $itemname   = isset($config['params']['dataparams']['itemname']) ? $config['params']['dataparams']['itemname'] : '';
    $itemid   = isset($config['params']['dataparams']['itemid']) ? $config['params']['dataparams']['itemid'] : 0;
    $uom = isset($config['params']['dataparams']['uom']) ? $config['params']['dataparams']['uom'] : '';
    $groupid    = isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $brand    = isset($config['params']['dataparams']['brand']) ? $config['params']['dataparams']['brand'] : '';
    $modelid    = isset($config['params']['dataparams']['modelid']) ? $config['params']['dataparams']['modelid'] : '';
    $classid    = isset($config['params']['dataparams']['classid']) ? $config['params']['dataparams']['classid'] : '';
    $category  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  isset($config['params']['dataparams']['subcat']) ? $config['params']['dataparams']['subcat'] : '';
    $wh         = isset($config['params']['dataparams']['wh']) ? $config['params']['dataparams']['wh'] : '';
    $whid         = isset($config['params']['dataparams']['whid']) ? $config['params']['dataparams']['whid'] : '';
    $whname     = isset($config['params']['dataparams']['whname']) ? $config['params']['dataparams']['whname'] : '';

    $itemstock  = isset($config['params']['dataparams']['itemstock']) ? $config['params']['dataparams']['itemstock'] : '(0,1)';
    $itemtype   = isset($config['params']['dataparams']['itemtype']) ? $config['params']['dataparams']['itemtype'] : '(0,1)';
    $companyid = $config['params']['companyid'];
    $amountformat   = $config['params']['dataparams']['amountformat'];

    $filter = '';
    $addfield = '';
    $joins = '';
    $grpby = '';

    if ($itemname != "") {
      // $filter = $filter . "and i.itemname='$itemname' ";
      $filter = $filter . "and rr.itemid=" . $itemid;
    }

    if ($uom != "") {
      $filter =  $filter . "and i.uom='$uom' ";
    }

    if ($groupid != "") {
      $addfield = $addfield . ',stockgrp.stockgrp_name ';
      $joins = $joins . "left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid ";
      $grpby  = $grpby . ",stockgrp.stockgrp_name ";
      $filter =  $filter . "and i.groupid='$groupid' ";
    }

    if ($brand != "") {
      $filter = $filter . " and i.brand='$brand' ";
    }

    if ($modelid != "") {
      $addfield = $addfield . ",ifnull(modelgrp.model_name, '') as modelname ";
      $joins = $joins . "left join model_masterfile as modelgrp on modelgrp.model_id = i.model ";
      $filter = $filter . " and i.model='$modelid' ";
      $grpby = $grpby . ", modelgrp.model_name ";
    }

    if ($classid != "") {
      $filter = $filter . " and i.class='$classid' ";
    }

    if ($category != "") {
      // $addfield = $addfield . ",cat.name as category ";
      // $joins = $joins . " left join itemcategory as cat on cat.line = i.category ";
      // $grpby = $grpby . ",cat.name ";
      $filter = $filter . " and i.category='$category' ";
    }

    if ($subcatname != "") {
      $addfield = $addfield . ",subcat.name as subcatname ";
      $joins = $joins . " left join itemsubcategory as subcat on subcat.line = i.subcat ";
      $grpby = $grpby . ",subcat.name ";
      $filter = $filter . " and i.subcat='$subcatname' ";
    }

    if ($wh != "") {
      $filter = $filter . " and rr.whid='$whid' ";
    }

    $filterbal = "";
    if ($itemstock == '(0,1)') { //both
    } else {
      if ($itemstock == '(1)') {
        $filterbal =  " having ifnull(sum(rr.bal),0)<>0 "; //with balance
      } else {
        $filterbal = " having ifnull(sum(rr.bal),0)=0 "; //without balance
      }
    }
    if ($companyid == 56) { //homeworks
      $addfield = $addfield . ", cl.client as supcode, cl.clientname as supname, iclass.cl_name as classname, stockgrp.stockgrp_name , wh.clientname as warehouse   ";
      $joins = $joins . "left join client as cl on cl.clientid=i.supplier
                         left join item_class as iclass on iclass.cl_id=i.class
                         left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
                         left join client as wh on wh.clientid=rr.whid ";
      $grpby = $grpby . ", cl.client, cl.clientname , iclass.cl_name, stockgrp.stockgrp_name, wh.clientname";
    }
    if ($companyid == 50) { // unitech
      $addfield = $addfield . ", brand.brand_desc as brandname ";
      $grpby .= ", brand.brand_desc ";
      $joins .= "left join frontend_ebrands as brand on brand.brandid = i.brand";
    }
    $query = "select i.itemid,i.itemname,i.uom,i.amt,i.disc, sum(rr.bal) as balance, partgrp.part_name as part,i.barcode,
        '' as category, 0 as stockgrp_name, cat.name as category,rr.loc,rr.expiry $addfield 
        from rrstatus as rr
        left join item as i on i.itemid=rr.itemid
        left join part_masterfile as partgrp on partgrp.part_id = i.part
        left join itemcategory as cat on cat.line = i.category
        $joins
        where i.isofficesupplies = 0 " . $filter . "  and i.isimport in $itemtype
        group by i.itemid, i.itemname,i.uom,i.amt,i.disc, partgrp.part_name,i.barcode,cat.name,rr.loc,rr.expiry $grpby" . $filterbal . " order by i.itemname";
    return $query;
  }
  public function reportdatacsv($config)
  {
    $format = $config['params']['dataparams']['amountformat'];
    $gather = $config['params']['dataparams']['dtagathering'];
    $itemstock  = isset($config['params']['dataparams']['itemstock']) ? $config['params']['dataparams']['itemstock'] : '(0,1)';
    $companyid = $config['params']['companyid'];

    switch ($format) {
      case 'avecost':
        $query = $this->avecost_query($config);
        break;
      case 'none':
      case 'rrcost':
      case 'isamt':
      case 'showboth':
        if ($gather == 'dcurrent') {
          $query = $this->none_query($config);
        } else {
          $query = $this->history_query($config);
        }

        break;
    }

    $data = $this->coreFunctions->opentable($query);
    switch ($format) {
      case 'isamt':
        foreach ($data as $row => $value) {
          $discounted = $this->othersClass->Discount($value->amt, $value->disc);
          $value->SRP = number_format($discounted, 2);
          $value->TOTAL = number_format($value->BALANCE * $discounted, 2);
          $value->BALANCE = number_format($value->BALANCE, 2);
          unset($value->itemid);
          unset($value->amt);
          unset($value->disc);
          if ($companyid == 56) { //homeworks
            unset($value->UOM);
          }
        }
        break;
      case 'rrcost':
        foreach ($data as $row => $value) {
          $cost =  $this->getLatestCost($value->itemid);
          $value->COST = number_format($cost, 2);
          $value->BALANCE = number_format($value->BALANCE, 2);
          $value->TOTAL = number_format($value->BALANCE * $cost, 2);
          unset($value->itemid);
          if ($companyid == 56) { //homeworks
            unset($value->UOM);
          }
        }
        break;
      case 'avecost':
        foreach ($data as $row => $value) {
          $cost =  $this->getLatestCost($value->itemid);
          $value->UNITCOST = number_format($cost, 2);
          $value->TOTALCOST = number_format($value->BALANCE * $value->cost, 2);
          unset($value->itemid);
          unset($value->cost);
        }
        break;
      case 'none':
        foreach ($data as $row => $value) {
          $value->BALANCE = number_format($value->BALANCE, 2);
          unset($value->itemid);
          if ($companyid == 56) { //homeworks
            unset($value->UOM);
            unset($value->COUNT);
            unset($value->amt);
            unset($value->disc);
            if ($itemstock  != '(0,1)') {
              $value->TOTAL = number_format($value->TOTAL, 2);
            }
          }
        }
        break;
      case 'showboth':
        foreach ($data as $row => $value) {
          $discounted = $this->othersClass->Discount($value->amt, $value->disc);
          $value->SRP = number_format($discounted, 2);
          $cost =  $this->getLatestCost($value->itemid);
          $value->COST = number_format($cost, 2);
          $value->BALANCE = number_format($value->BALANCE, 2);
          unset($value->itemid);
          unset($value->amt);
          unset($value->disc);
          unset($value->UOM);
        }
        break;
    }
    $status =  true;
    $msg = 'Generating CSV successfully';
    if (empty($data)) {
      $status =  false;
      $msg = 'No data Found';
    }
    return ['status' => $status, 'msg' => $msg, 'data' => $data, 'params' => $this->reportParams, 'name' => 'ItemList'];
  }

  public function none_query($config)
  {
    $itemname   = isset($config['params']['dataparams']['itemname']) ? $config['params']['dataparams']['itemname'] : '';
    $itemid   = isset($config['params']['dataparams']['itemid']) ? $config['params']['dataparams']['itemid'] : 0;
    $uom = isset($config['params']['dataparams']['uom']) ? $config['params']['dataparams']['uom'] : '';
    $groupid    = isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $brand    = isset($config['params']['dataparams']['brand']) ? $config['params']['dataparams']['brand'] : '';
    $modelid    = isset($config['params']['dataparams']['modelid']) ? $config['params']['dataparams']['modelid'] : '';
    $classid    = isset($config['params']['dataparams']['classid']) ? $config['params']['dataparams']['classid'] : '';
    $category  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  isset($config['params']['dataparams']['subcat']) ? $config['params']['dataparams']['subcat'] : '';
    $wh         = isset($config['params']['dataparams']['wh']) ? $config['params']['dataparams']['wh'] : '';
    $whid         = isset($config['params']['dataparams']['whid']) ? $config['params']['dataparams']['whid'] : '';
    $whname     = isset($config['params']['dataparams']['whname']) ? $config['params']['dataparams']['whname'] : '';

    $itemstock  = isset($config['params']['dataparams']['itemstock']) ? $config['params']['dataparams']['itemstock'] : '(0,1)';
    $itemtype   = isset($config['params']['dataparams']['itemtype']) ? $config['params']['dataparams']['itemtype'] : '(0,1)';
    $format = $config['params']['dataparams']['amountformat'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $addfield = '';
    $joins = '';
    $grpby = '';

    if ($itemname != "") {
      // $filter = $filter . "and i.itemname='$itemname' ";
      $filter = $filter . "and rr.itemid=" . $itemid;
    }

    if ($uom != "") {
      $filter =  $filter . "and i.uom='$uom' ";
    }

    if ($groupid != "") {
      $joins = $joins . "left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid ";
      $grpby  = $grpby . ",stockgrp.stockgrp_name ";
      $filter =  $filter . "and i.groupid='$groupid' ";
    }

    if ($brand != "") {
      $filter = $filter . " and i.brand='$brand' ";
    }

    if ($modelid != "") {
      $joins = $joins . "left join model_masterfile as modelgrp on modelgrp.model_id = i.model ";
      $filter = $filter . " and i.model='$modelid' ";
      $grpby = $grpby . ", modelgrp.model_name ";
    }

    if ($classid != "") {
      $filter = $filter . " and i.class='$classid' ";
    }

    if ($category != "") {
      $filter = $filter . " and i.category='$category' ";
    }

    if ($subcatname != "") {
      $joins = $joins . " left join itemsubcategory as subcat on subcat.line = i.subcat ";
      $grpby = $grpby . ",subcat.name ";
      $filter = $filter . " and i.subcat='$subcatname' ";
    }

    if ($wh != "") {
      $filter = $filter . " and rr.whid='$whid' ";
    }

    $field1 = '';
    switch ($format) {
      case 'rrcost':
        if ($companyid == 56) { //homeworks
          // $field = ",0 as COST,0 as TOTAL ";
          $field = ",0 as COST, cl.client as `SUPPLIER_CODE`, cl.clientname as `SUPPLIER_NAME`, cat.name as `CATEGORY`,
         iclass.cl_name as `CLASS`, stockgrp.stockgrp_name as `SUBCLASS` , wh.clientname as `WAREHOUSE`, 0 as TOTAL";
          $joins = $joins . "left join client as cl on cl.clientid=i.supplier
                            left join item_class as iclass on iclass.cl_id=i.class
                            left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
                            left join client as wh on wh.clientid=rr.whid ";
          $grpby = $grpby . ", cl.client, cl.clientname , iclass.cl_name, stockgrp.stockgrp_name, wh.clientname ";
        } else {
          $field = ",0 as COST,0 as TOTAL,'' as COUNT ";
        }
        $grpby = $grpby . ",rr.whid ";
        break;
      case 'none':
        if ($itemstock  != '(0,1)') {
          if ($companyid == 56) { //homeworks
            $field = ",0 as SRP,cl.client as `SUPPLIER_CODE`, cl.clientname as `SUPPLIER_NAME`, cat.name as `CATEGORY`,
                      iclass.cl_name as `CLASS`, stockgrp.stockgrp_name as `SUBCLASS` , wh.clientname as `WAREHOUSE`,
                      i.amt as TOTAL ";
            $joins = $joins . "left join client as cl on cl.clientid=i.supplier
                            left join item_class as iclass on iclass.cl_id=i.class
                            left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
                            left join client as wh on wh.clientid=rr.whid ";
            $grpby = $grpby . ", cl.client, cl.clientname , iclass.cl_name, stockgrp.stockgrp_name, wh.clientname";
          } else {

            $field = ",0 as SRP, i.amt as TOTAL,'' as COUNT ";
          }
        } else {
          if ($companyid == 56) { //homeworks
            $field = ",cl.client as `SUPPLIER_CODE`, cl.clientname as `SUPPLIER_NAME`, cat.name as `CATEGORY`,
                      iclass.cl_name as `CLASS`, stockgrp.stockgrp_name as `SUBCLASS` , wh.clientname as `WAREHOUSE` ";
            $joins = $joins . "left join client as cl on cl.clientid=i.supplier
                            left join item_class as iclass on iclass.cl_id=i.class
                            left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
                            left join client as wh on wh.clientid=rr.whid ";
            $grpby = $grpby . ", cl.client, cl.clientname , iclass.cl_name, stockgrp.stockgrp_name, wh.clientname";
          } else {
            $field = ", '' as COUNT ";
          }
        }
        break;

      case 'isamt':
        if ($companyid == 56) {
          $field = ",i.amt,i.disc,0 as SRP, cl.client as `SUPPLIER_CODE`, cl.clientname as `SUPPLIER_NAME`, cat.name as `CATEGORY`,
         iclass.cl_name as `CLASS`, stockgrp.stockgrp_name as `SUBCLASS` , wh.clientname as `WAREHOUSE`, 0 as TOTAL";
          $joins = $joins . "left join client as cl on cl.clientid=i.supplier
                            left join item_class as iclass on iclass.cl_id=i.class
                            left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
                            left join client as wh on wh.clientid=rr.whid ";
          $grpby = $grpby . ", cl.client, cl.clientname , iclass.cl_name, stockgrp.stockgrp_name, wh.clientname ";
        } else {
          $field = ",i.amt,i.disc,0 as SRP,0 as TOTAL,'' as COUNT";
        }
        break;
      case 'showboth':
        $field1 = " 0 as SRP,0 as COST , ";
        $field = ",i.amt,i.disc,  cl.client as `SUPPLIER_CODE`, cl.clientname as `SUPPLIER_NAME`, cat.name as `CATEGORY`,
         iclass.cl_name as `CLASS`, stockgrp.stockgrp_name as `SUBCLASS` , wh.clientname as `WAREHOUSE` ";
        $joins = $joins . " left join client as cl on cl.clientid=i.supplier
                            left join item_class as iclass on iclass.cl_id=i.class
                            left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = i.groupid
                            left join client as wh on wh.clientid=rr.whid ";
        $grpby = $grpby . ", cl.client, cl.clientname , iclass.cl_name, stockgrp.stockgrp_name, wh.clientname ";
        break;
    }

    switch ($itemstock) {
      case '(1)': // with balance
        $filter = $filter .  " and rr.bal<> 0 ";
        break;
      case '(0)': // without balance
        $filter = $filter . " and rr.bal= 0 ";
        break;
    }
    $query = "select i.itemid,i.barcode as `ITEM CODE`,i.itemname as `ITEM NAME`,i.uom as UOM, $field1 sum(rr.bal) as BALANCE $field
        from rrstatus as rr
        left join item as i on i.itemid=rr.itemid
        left join part_masterfile as partgrp on partgrp.part_id = i.part
        left join itemcategory as cat on cat.line = i.category
        $joins
        where i.isofficesupplies = 0 " . $filter . "  and i.isimport in $itemtype
        group by i.itemid, i.itemname,i.uom,i.amt,i.disc, partgrp.part_name,i.barcode,cat.name $grpby";
    return $query;
  }
  public function avecost_query($config)
  {
    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $whid         = $config['params']['dataparams']['whid'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    $partid    = isset($config['params']['dataparams']['partid']) ? $config['params']['dataparams']['partid'] : 0;
    $partname  = isset($config['params']['dataparams']['partname']) ? $config['params']['dataparams']['partname'] : '';

    $uom   = $config['params']['dataparams']['uom'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';

    $order = " order by itemname";
    $filter = " and item.isimport in $itemtype";

    $filtermain = "";
    $addfield = "";
    $addfieldgrpby = "";
    $leftjoin = "";

    if ($brand != "") {
      $filter = $filter . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filter = $filter . " and item.model='$modelid'";
      $addfieldgrpby = ", modelgrp.model_name";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($partname != "") {
      $filter = $filter . " and item.part='$partid'";
      $addfieldgrpby = ", partgrp.part_name";
      $leftjoin .= " left join part_masterfile as partgrp on partgrp.part_id = item.part";
    }

    if ($groupname != "") {
      $filter = $filter . " and item.groupid='$groupid'";
      $addfieldgrpby = ", stockgrp.part_name";
      $leftjoin .= " left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid";
    }

    if ($whname != "") {
      $filtermain = $filtermain . " and stock.whid=$whid";
    }

    $proj = '';
    $proj1 = '';
    $proj2 = '';
    $proj3 = '';

    if ($uom != '') {
      $uom = "and uom.uom = '$uom'";
    } else {
      $uom = 'and uom.uom = item.uom';
    }

    $barcode = ",item.barcode as barcode";
    $cutoffdate = $this->coreFunctions->datareader("select pvalue as value from profile where psection= ? limit 1", ['INVCUTOFF']);
    $invwh = "";
    if ($cutoffdate != '') {
      if ($wh != "") {
        $invwh = $invwh . " and inv.whid='$whid'";
      }

      if ($cutoffdate > $asof) {
        goto def;
      }
      $filtetdate = "head.dateid > '$cutoffdate' and head.dateid <= '$asof'";

      $unionInvbal = "
          union all
          select inv.itemid, inv.whid, sum(inv.cost) as costin, 0 as costout, sum(bal) as qty, 0 as iss, inv.loc, inv.expiry,0 as accost
          from invbal as inv left join item on item.itemid = inv.itemid
          where inv.dateid <= '$cutoffdate' $invwh
          group by inv.itemid,item.uom,inv.whid,iss,inv.loc,inv.expiry";
    } else {
      def:
      $unionInvbal = "";
      $filtetdate = "head.dateid <= '$asof'";
    }

    $query = "select item.itemid,item.barcode as `ITEM CODE`,item.itemname as `ITEM DESCRIPTION`,format(sum(ib.qty-ib.iss),2) as BALANCE,item.uom as UOM,
        sum(round(ib.costin-ib.costout,2))/sum(ib.qty-ib.iss) as cost,0 as UNITCOST,0 AS TOTALCOST
        
        from ( 
        select item.itemid, stock.whid, sum(stock.cost*stock.qty) as costin, sum(stock.cost*stock.iss) as costout, 
        sum(stock.qty/ifnull(uom.factor,1)) as qty, sum(stock.iss/ifnull(uom.factor,1)) as iss, stock.loc, stock.expiry
        from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid = item.itemid $uom
        where $filtetdate $filtermain
        group by item.itemid, stock.whid, stock.loc, stock.expiry

        $unionInvbal

        ) as ib 
        left join item on item.itemid=ib.itemid
        left join itemcategory as cat on cat.line = item.category
        $leftjoin
        where ''='' $filter
        group by item.disc, item.minimum,item.maximum,item.itemid,barcode, itemname, groupid,  model, part, cat.name,
        item.brand, sizeid,body, class, item.uom, loc, expiry, item.amt,UNITCOST $addfieldgrpby
        having (case when sum(ib.qty-ib.iss)>0 then 1 else 0 end) in " . $itemstock . ' ' . $order;


    return $query;
  }
  public function history_query($config)
  {

    $asof       = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brand    = $config['params']['dataparams']['brand'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $excwh = isset($config['params']['dataparams']['layoutformat']) ? $config['params']['dataparams']['layoutformat'] : '';
    $uom = $config['params']['dataparams']['uom'];
    $companyid = $config['params']['companyid'];
    $repitemcol = '';


    $filter = " and item.isimport in $itemtype";
    $filter1 = "";
    $filteritem = "";

    $isallitems = true;

    if ($brand != "") {
      $filteritem = $filteritem . " and item.brand='$brand'";
    }

    if ($modelid != "") {
      $filteritem = $filteritem . " and item.model='$modelid'";
    }

    if ($classid != "") {
      $filteritem = $filteritem . " and item.class='$classid'";
    }

    if ($category != "") {
      $filteritem = $filteritem . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filteritem = $filteritem . " and item.subcat='$subcatname'";
    }

    if ($barcode != "") {
      $filteritem = $filteritem . " and item.barcode='$barcode'";
    }

    if ($groupid != "") {
      if ($isallitems) {
        $filteritem = $filteritem . " and item.groupid='$groupid'";
      } else {
        $filter = $filter . " and item.groupid='$groupid'";
      }
    }

    if ($wh != "") {
      $filter = $filter . " and stock.whid='$whid'";
    }


    $proj = '';
    $proj1 = '';
    $proj2 = '';
    $proj3 = '';

    $fielduom = ' item.uom';
    $fieldqty = ' stock.qty';
    $fieldiss = ' stock.iss';
    $fieldAmt = ' item.amt';
    $defaultljoinmain = '';
    $defaultljoin = '';
    $addfieldgrpby = '';

    $filter1 .= "";
    $repitemcol = "item.itemname as itemname,";
    $exp = ", expiry";
    $filterexcwh = '';
    $addfield = '';

    $addfield = ', loc';

    $cost = '';
    $grpCost = '';
    $fieldCost = '';
    $cost = '0 as cost, ';

    $isallitems = false;
    // if ($isallitems) {
    //   $query = "select  item.itemid, item.barcode as `ITEM CODE`,item.itemname as `ITEM DESCRIPTION`," . $fielduom . " as UOM,
    //   sum(ib.qty - ib.iss) as BALANCE, '' as COUNT
    //   from item 
    //   left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    //   left join model_masterfile as modelgrp on modelgrp.model_id = item.model
    //   left join part_masterfile as partgrp on partgrp.part_id = item.part 
    //   left join itemcategory as cat on cat.line = item.category
    //   left join itemsubcategory as subcat on subcat.line = item.subcat
    //   left join iteminfo as iinfo on iinfo.itemid = item.itemid
    //   " . $defaultljoinmain . "
    //   left join (
    //   select item.itemid, " . $repitemcol . " " . $fielduom . ", 
    //   " . $fieldqty . " as qty, 
    //   " . $fieldiss . " as iss,
    //   $fieldCost stock.loc, stock.expiry $proj1
    //   from lahead as head
    //   left join lastock as stock on stock.trno = head.trno
    //   left join item on item.itemid = stock.itemid
    //   $proj2
    //   $proj3
    //   $defaultljoin
    //   where head.dateid <= '$asof' and ifnull(item.barcode, '') <> '' $filter $filter1 and item.isofficesupplies = 0 " . $filteritem . " " . $filterexcwh . " 
    //   union all
    //   select item.itemid, " . $repitemcol . " " . $fielduom . ", 
    //   " . $fieldqty . " as qty, 
    //   " . $fieldiss . " as iss,
    //   $fieldCost stock.loc, stock.expiry $proj1  
    //   from glhead as head
    //   left join glstock as stock on stock.trno = head.trno
    //   left join item on item.itemid = stock.itemid
    //   $proj2
    //   $proj3
    //   $defaultljoin
    //   where head.dateid <= '$asof' and ifnull(item.barcode, '') <> '' $filter $filter1 and item.isofficesupplies = 0 " . $filteritem . " " . $filterexcwh . " 
    //   ) as ib on ib.itemid = item.itemid
    //   where item.isofficesupplies = 0 " . $filteritem . " 
    //   group by item.disc, minimum, maximum, cat.name, subcat.name, item.itemid,item.barcode,item.partno, item.itemname,
    //   groupid, brand, partgrp.part_name, model, modelgrp.model_name, partgrp.part_name, brand, sizeid, body, class, $grpCost item.amt, iinfo.serialno, $fielduom" . $addfield .  $exp . $proj . $addfieldgrpby . "
    //   having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;
    // } else {

    //fortesting =========================================
    $field = "";
    $field1 = "";
    $hmjoin = "";
    $hmpostedwh = "";
    $hmunpostedwh = "";
    $addwh = "";
    $hmgroup = "";
    $addhgroup = "";
    $order = " order by item.category,itemname";
    switch ($amountformat) {
      case 'rrcost':
        if ($companyid == 56) { //homeworks
          $field1 = " 0 as COST,";
          $field .= ', cl.client as `SUPPLIER_CODE`, cl.clientname as  `SUPPLIER_NAME`, cat.name as `CATEGORY`, iclass.cl_name as `CLASS` , stockgrp.stockgrp_name as `SUBCLASS`, `WAREHOUSE`,0 as TOTAL ';
          $addwh .= ', wh.clientname as warehouse';
          $hmjoin = ' left join client as cl on cl.clientid=item.supplier
                  left join item_class as iclass on iclass.cl_id=item.class';
          $hmunpostedwh = ' left join client as wh on wh.client=head.wh';
          $hmpostedwh = ' left join client as wh on wh.clientid=head.whid';
          $hmgroup = ', wh.clientname';
          $addhgroup = ', cl.client, cl.clientname, iclass.cl_name , warehouse, stockgrp.stockgrp_name';
          $order = " order by itemname";
        } else {
          $field = ",0 as COST,0 as TOTAL,'' as COUNT ";
        }
        break;
      case 'none':
        if ($itemstock  != '(0,1)') {
          if ($companyid == 56) { //homeworks
            $field1 = " 0 as SRP,";
            $field .= ',item.amt,item.disc, cl.client as `SUPPLIER_CODE`, cl.clientname as  `SUPPLIER_NAME`, cat.name as `CATEGORY`, iclass.cl_name as `CLASS` , stockgrp.stockgrp_name as `SUBCLASS`, `WAREHOUSE`,item.amt as TOTAL ';
            $addwh .= ', wh.clientname as warehouse';
            $hmjoin = ' left join client as cl on cl.clientid=item.supplier
                  left join item_class as iclass on iclass.cl_id=item.class';
            $hmunpostedwh = ' left join client as wh on wh.client=head.wh';
            $hmpostedwh = ' left join client as wh on wh.clientid=head.whid';
            $hmgroup = ', wh.clientname';
            $addhgroup = ', cl.client, cl.clientname, iclass.cl_name , warehouse, stockgrp.stockgrp_name';
            $order = " order by itemname";
          } else {
            $field = ",0 as SRP, item.amt as TOTAL,'' as COUNT ";
          }
        } else {
          if ($companyid == 56) { //homeworks
            // $field = ", '' as COUNT ";
            $field .= ',item.amt,item.disc, cl.client as `SUPPLIER_CODE`, cl.clientname as  `SUPPLIER_NAME`, cat.name as `CATEGORY`, iclass.cl_name as `CLASS` , stockgrp.stockgrp_name as `SUBCLASS`, `WAREHOUSE` ';
            $addwh .= ', wh.clientname as warehouse';
            $hmjoin = ' left join client as cl on cl.clientid=item.supplier
                  left join item_class as iclass on iclass.cl_id=item.class';
            $hmunpostedwh = ' left join client as wh on wh.client=head.wh';
            $hmpostedwh = ' left join client as wh on wh.clientid=head.whid';
            $hmgroup = ', wh.clientname';
            $addhgroup = ', cl.client, cl.clientname, iclass.cl_name , warehouse, stockgrp.stockgrp_name';
            $order = " order by itemname";
          } else {
            $field = ", '' as COUNT ";
          }
        }
        break;
      case 'isamt':
        if ($companyid == 56) { //homeworks
          $field1 = " 0 as SRP,";
          $field .= ',item.amt,item.disc, cl.client as `SUPPLIER_CODE`, cl.clientname as  `SUPPLIER_NAME`, cat.name as `CATEGORY`, iclass.cl_name as `CLASS` , stockgrp.stockgrp_name as `SUBCLASS`, `WAREHOUSE`,0 as TOTAL ';
          $addwh .= ', wh.clientname as warehouse';
          $hmjoin = ' left join client as cl on cl.clientid=item.supplier
                  left join item_class as iclass on iclass.cl_id=item.class';
          $hmunpostedwh = ' left join client as wh on wh.client=head.wh';
          $hmpostedwh = ' left join client as wh on wh.clientid=head.whid';
          $hmgroup = ', wh.clientname';
          $addhgroup = ', cl.client, cl.clientname, iclass.cl_name , warehouse, stockgrp.stockgrp_name';
          $order = " order by itemname";
        } else {
          $field = ",item.amt,item.disc,0 as SRP,0 as TOTAL,'' as COUNT";
        }
        break;
      case 'showboth':
        $field1 = " 0 as SRP,0 as COST,";
        $field .= ',item.amt,item.disc, cl.client as `SUPPLIER_CODE`, cl.clientname as  `SUPPLIER_NAME`, cat.name as `CATEGORY`, iclass.cl_name as `CLASS` , stockgrp.stockgrp_name as `SUBCLASS`, `WAREHOUSE` ';
        $addwh .= ', wh.clientname as warehouse';
        $hmjoin = ' left join client as cl on cl.clientid=item.supplier
                  left join item_class as iclass on iclass.cl_id=item.class';
        $hmunpostedwh = ' left join client as wh on wh.client=head.wh';
        $hmpostedwh = ' left join client as wh on wh.clientid=head.whid';
        $hmgroup = ', wh.clientname';
        $addhgroup = ', cl.client, cl.clientname, iclass.cl_name , warehouse, stockgrp.stockgrp_name';
        $order = " order by itemname";
        break;
    }
    $cutoffdate = $this->coreFunctions->datareader("select pvalue as value from profile where psection= ? limit 1", ['INVCUTOFF']);
    $invwh = "";
    $unionInvbal = "";
    if ($cutoffdate != '') {
      if ($wh != "") {
        $invwh = $invwh . " and inv.whid='$whid'";
      }

      if ($cutoffdate > $asof) {
        goto def;
      }
      $filtetdate = "head.dateid > '$cutoffdate' and head.dateid <= '$asof'";

      $unionInvbal = "
          union all
          select inv.itemid,item.uom,inv.whid,sum(bal) as qty, 0 as iss, inv.loc,inv.expiry from invbal as inv
          left join item on item.itemid = inv.itemid
          where inv.dateid <= '$cutoffdate' $invwh $filteritem
          group by inv.itemid,item.uom,inv.whid,iss,inv.loc,inv.expiry";
    } else {
      def:
      $filtetdate = "head.dateid <= '$asof'";
    }

    $query = "select ib.itemid, item.barcode as `ITEM CODE`, item.itemname as `ITEM DESCRIPTION`, ib.uom as UOM, $field1
          sum(ib.qty - ib.iss) as BALANCE $field
          from (
          select stock.itemid,  " . $fielduom . ", stock.whid, 
          sum(" . $fieldqty . ") as qty, 
          sum(" . $fieldiss . ") as iss, 
          stock.loc, stock.expiry $proj1 $addwh
          from lahead as head left join lastock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
          $proj2
          $proj3 $hmunpostedwh
          $defaultljoin
          where $filtetdate $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
          group by stock.itemid,  " . $fielduom . ", stock.whid, stock.loc, stock.expiry $proj1 $hmgroup
          union all
          select stock.itemid, " . $fielduom . ", stock.whid, 
          sum(" . $fieldqty . ") as qty, 
          sum(" . $fieldiss . ") as iss, 
          stock.loc, stock.expiry $proj1 $addwh
          from glhead as head left join glstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid
          $proj2
          $proj3 $hmpostedwh
          $defaultljoin
          where $filtetdate $filter $filter1 $filteritem  $filterexcwh  and item.isofficesupplies = 0
          group by stock.itemid,  " . $fielduom . ", stock.whid, stock.loc, stock.expiry $proj1 $hmgroup

          $unionInvbal
          ) as ib left join item on item.itemid = ib.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          left join part_masterfile as partgrp on partgrp.part_id = item.part
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
          left join iteminfo as iinfo on iinfo.itemid = item.itemid
           $hmjoin
          group by item.disc, minimum, maximum, cat.name, subcat.name, ib.itemid, barcode, itemname, item.groupid,partno,
          ifnull(modelgrp.model_name, ''), item.model, partgrp.part_name, brand, sizeid, body, class, item.amt, ib.uom, iinfo.serialno $addfield $exp $proj $addhgroup
          having (case when sum(ib.qty - ib.iss) > 0 then 1 else 0 end) in " . $itemstock . ' ' . $order;
    // }
    // var_dump($query);
    return $query;
  }

  public function showsrpandcost($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '8px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $this->reportParams['orientation'] = 'l';

    if ($wh == '') {
      $wh = 'ALL';
    }
    $count = 13;
    $page = 13;

    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1200';

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10;margin-top:0px;margin-left:70px;');
    $str .= $this->default_displayHeader_srpandcost($config);
    $str .= $this->default_srp_and_cost_table_cols($layoutsize, $border, $font, $fontsize11, $config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $igrp = "";
    $totalext = 0;
    $grandtotal = 0;

    $multiheader = true;


    foreach ($result as $key => $data) {

      $balance = number_format($data->balance, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $cost = $this->getLatestCost($data->itemid);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '10', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

      $totalext = $data->balance * $cost;
      $costv = $cost;
      if ($cost == 0) {
        $cost = '-';
      } else {
        $cost = number_format($cost, 2);
      }

      if ($totalext == 0) {
        $totalext = '-';
      } else {
        $totalext = number_format($totalext, 2);
      }

      $discounted = $this->othersClass->Discount($data->amt, $data->disc);
      $str .= $this->reporter->col(number_format($discounted, 2), '80', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($cost, '80', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '15', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->supcode, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '15', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->supname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->category, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->classname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->warehouse, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      // $str .= $this->reporter->endtable();

      $scatgrp = strtoupper($data->category);
      $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
      $part = $data->part;
      $totalbalqty = $totalbalqty + $data->balance;
      // $grandtotal = $grandtotal + ($data->balance * $costv);

      // // if ($multiheader) {
      // if ($this->reporter->linecounter > $page) {
      //   $str .= $this->reporter->endtable();

      //   // $str .= '<br/>';
      //   $str .= $this->reporter->begintable($layoutsize);
      //   $str .= $this->reporter->col('', '1200', null, false, '1px solid ', '', '', $font, '10', '', '', '');
      //   // $str .= '<br/>';
      //   // $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
      //   // if (!$allowfirstpage) {
      //   //   $str .= $this->default_displayHeader_srpandcost($config);
      //   // }
      //   // $str .= $this->default_srp_and_cost_table_cols($layoutsize, $border, $font, $fontsize11, $config);
      //   $page = $page + $count;
      // }

      // if ($this->reporter->linecounter >= $page) {
      //   $str .= $this->reporter->endtable();
      //   $str .= $this->reporter->page_break();
      //   $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
      //   if (!$allowfirstpage) {
      //     $str .= $this->default_displayHeader_srpandcost($config);
      //   }
      //   $str .= $this->default_srp_and_cost_table_cols($layoutsize, $border, $font, $fontsize11, $config);
      //   $page = $page + $count;
      // }

    }
    // $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '200', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');

    // $str .= $this->reporter->col('OVERALL STOCKS :', '825', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    // $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'TB', '', $padding, '8px');
    // $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    // $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');
    // $str .= $this->reporter->col(number_format($grandtotal, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
    // $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function default_srp_and_cost_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $this->reportParams['orientation'] = 'l';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUPPLIER', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '200', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SRP', '80', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('COST', '80', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUPPLIER NAME', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CATEGORY', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CLASS', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUBCLASS', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('WAREHOUSE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    return $str;
  }

  private function default_displayHeader_srpandcost($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $this->reportParams['orientation'] = 'l';
    $font_size = '10';
    $padding = '';
    // $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


    $partid    = $config['params']['dataparams']['partid'];
    $partname  = $config['params']['dataparams']['partname'];

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

    $layoutsize = '1200';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }

    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($subcat == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }



  public function hm_SELLING_PRICE_layout($config)
  {
    $str = '';

    try {
      //oks
      $result = $this->reportDefault($config);

      $border = '1px solid';
      $border_line = '';
      $alignment = '';
      $font = $this->companysetup->getrptfont($config['params']);
      $font_size = '10';
      $fontsize11 = 11;
      $padding = '';
      $margin = '8px';

      $center     = $config['params']['center'];
      $username   = $config['params']['user'];
      $companyid = $config['params']['companyid'];

      $asof       = $config['params']['dataparams']['start'];
      $client     = $config['params']['dataparams']['client'];
      $clientname = $config['params']['dataparams']['clientname'];
      $barcode    = $config['params']['dataparams']['barcode'];
      $itemname   = $config['params']['dataparams']['itemname'];
      $classid    = $config['params']['dataparams']['classid'];
      $classname  = $config['params']['dataparams']['classic'];
      $categoryid = $config['params']['dataparams']['categoryid'];
      $categoryname  = $config['params']['dataparams']['categoryname'];
      $groupid    = $config['params']['dataparams']['groupid'];
      $groupname  = $config['params']['dataparams']['stockgrp'];
      $brandid    = $config['params']['dataparams']['brandid'];
      $brandname  = $config['params']['dataparams']['brandname'];
      $modelid    = $config['params']['dataparams']['modelid'];
      $modelname  = $config['params']['dataparams']['modelname'];
      $wh         = $config['params']['dataparams']['wh'];
      $whname     = $config['params']['dataparams']['whname'];
      $amountformat   = $config['params']['dataparams']['amountformat'];
      $itemstock  = $config['params']['dataparams']['itemstock'];
      $itemtype   = $config['params']['dataparams']['itemtype'];
      $this->reportParams['orientation'] = 'l';

      $count = 51;
      $page = 50;


      if (empty($result)) {
        return $this->othersClass->emptydata($config);
      }


      $layoutsize = '1200';
      $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10;margin-top:0px;margin-left:70px;');
      $str .= $this->default_displayHeader_hmSELLING_PRICE($config);
      $str .= $this->default_selling_price_hmtable_cols($layoutsize, $border, $font, $fontsize11, $config);

      $totalbalqty = 0;
      $part = "";
      $scatgrp = "";
      $igrp = "";
      $totalext = 0;
      $grandtotal = 0;

      $multiheader = true;


      foreach ($result as $key => $data) {

        $balance = number_format($data->balance, 2);
        if ($balance == 0) {
          $balance = '-';
        }
        $isamt = number_format($data->amt, 2);
        if ($isamt == 0) {
          $isamt = '-';
        }

        $discounted = $this->othersClass->Discount($data->amt, $data->disc);


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->itemname, '170', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

        $totalext = $data->balance * $discounted;

        if ($totalext == 0) {
          $totalext = '-';
        } else {
          $totalext = number_format($totalext, 2);
        }
        #120 380 100 100 100 100 100
        $str .= $this->reporter->col(number_format($discounted, 2), '80', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->supcode, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->supname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->category, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->classname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($data->warehouse, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');

        $str .= $this->reporter->endrow();

        // $scatgrp = strtoupper($data->category);
        // $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
        // $part = $data->part;
        $grandtotal = $grandtotal + ($data->balance * $discounted);
        $totalbalqty = $totalbalqty + $data->balance;

        // if ($multiheader) {
        //   if ($this->reporter->linecounter >= $page) {
        //     $str .= $this->reporter->endtable();
        //     $str .= $this->reporter->page_break();
        //     $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        //     if (!$allowfirstpage) {
        //       $str .= $this->default_displayHeader_hmSELLING_PRICE($config);
        //     }
        //     $str .= $this->default_selling_price_hmtable_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        //     $page = $page + $count;
        //   }
        // }
      }

      // $str .= $this->reporter->endtable();
      // $str .= $this->reporter->begintable($layoutsize);
      $str .= '<br/>';
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('OVERALL STOCKS :', '170', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
      $str .= $this->reporter->endreport();
    } catch (Exception $e) {
      $this->othersClass->logConsole('Exception' . $e->getMessage());
    }

    return $str;
  }



  private function default_displayHeader_hmSELLING_PRICE($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '5px';
    $this->reportParams['orientation'] = 'l';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


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
    $layoutsize = '1200';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }

    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');


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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($subcat == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    // $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_selling_price_hmtable_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $this->reportParams['orientation'] = 'l';
    $padding = '';
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '170', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUPPLIER', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '170', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SRP', '80', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUPPLIER NAME', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CATEGORY', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CLASS', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUBCLASS', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('WAREHOUSE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_hmdisplayHeader_LATEST_COST($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $this->reportParams['orientation'] = 'l';
    $font_size = '10';
    $padding = '';
    $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];


    $partid    = $config['params']['dataparams']['partid'];
    $partname  = $config['params']['dataparams']['partname'];


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
    $layoutsize = '1200';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }

    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

    if ($subcat == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_hmlatest_cost_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $this->reportParams['orientation'] = 'l';
    // $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->col('ITEM DESCRIPTION', '460', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->col('UOM', '40', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->col('COST', '80', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    // $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');

    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '170', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUPPLIER', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '15', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '170', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('COST', '80', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUPPLIER NAME', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CATEGORY', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('CLASS', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('SUBCLASS', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('WAREHOUSE', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    return $str;
  }

  public function hm_LATEST_COST_layout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '8px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $this->reportParams['orientation'] = 'l';

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];

    if ($wh == '') {
      $wh = 'ALL';
    }

    $count = 51;
    $page = 50;

    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1200';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10;margin-top:0px;margin-left:75px;');
    $str .= $this->default_hmdisplayHeader_LATEST_COST($config);
    $str .= $this->default_hmlatest_cost_table_cols($layoutsize, $border, $font, $fontsize11, $config);

    $totalbalqty = 0;
    $part = "";
    $scatgrp = "";
    $igrp = "";
    $totalext = 0;
    $grandtotal = 0;

    $multiheader = true;

    foreach ($result as $key => $data) {

      $balance = number_format($data->balance, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $cost = $this->getLatestCost($data->itemid);


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '10', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '170', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

      $totalext = $data->balance * $cost;
      $costv = $cost;
      if ($cost == 0) {
        $cost = '-';
      } else {
        $cost = number_format($cost, 2);
      }

      if ($totalext == 0) {
        $totalext = '-';
      } else {
        $totalext = number_format($totalext, 2);
      }

      $str .= $this->reporter->col($cost, '80', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->supcode, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->supname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->category, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->classname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->warehouse, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
      $str .= $this->reporter->col($totalext, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      // $scatgrp = strtoupper($data->category);
      // $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';
      // $part = $data->part;
      $totalbalqty = $totalbalqty + $data->balance;
      $grandtotal = $grandtotal + ($data->balance * $costv);

      // if ($multiheader) {

      //   if ($this->reporter->linecounter >= $page) {
      //     $str .= $this->reporter->endtable();
      //     $str .= $this->reporter->page_break();
      //     $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
      //     if (!$allowfirstpage) {
      //       $str .= $this->default_hmdisplayHeader_LATEST_COST($config);
      //     }
      //     $str .= $this->default_hmlatest_cost_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
      //     $page = $page + $count;
      //   }
      // }
    }

    // $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '170', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function hm_NONE_layout($config)
  {
    $str = '';
    try {
      $result = $this->reportDefault($config);

      $border = '1px solid';
      $border_line = '';
      $alignment = '';
      $font = $this->companysetup->getrptfont($config['params']);
      $font_size = '10';
      $fontsize11 = 11;
      $padding = '';
      $margin = '8px';


      $companyid = $config['params']['companyid'];
      $itemstock  = isset($config['params']['dataparams']['itemstock']) ? $config['params']['dataparams']['itemstock'] : '(0,1)';
      $this->reportParams['orientation'] = 'l';
      $count = 51;
      $page = 50;

      $this->reporter->linecounter = 0;

      if (empty($result)) {
        return $this->othersClass->emptydata($config);
      }

      $layoutsize = '1200';
      $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10;margin-top:0px;margin-left:75px;');
      $str .= $this->default_displayHeader_hmNONE($config);
      $str .= $this->default_hmnone_table_cols($layoutsize, $border, $font, $fontsize11, $config);

      $totalbalqty = 0;
      $part = "";
      $scatgrp = "";
      $igrp = "";
      $totalext = 0;
      $grandtotal = 0;
      $cost = 0;

      $multiheader = true;

      // if (isset($config['params']['multiheader'])) {
      //   $multiheader = $config['params']['multiheader'];
      // }

      foreach ($result as $key => $data) {

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

        $totalext = $data->balance * $data->amt;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();


        if ($itemstock != '(0,1)') {
          $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '10', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->itemname, '170', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
          $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->supcode, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->supname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->category, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->classname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->warehouse, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        } else {
          $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '10', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->itemname, '250', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->supcode, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
          $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->supname, '200', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->category, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->classname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
          $str .= $this->reporter->col($data->warehouse, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        }


        // $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col('', '10', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->itemname, '170', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // if ($itemstock != '(0,1)') {
        //   $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'C', $font, '10', '', '', '');
        // }
        // $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        // $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->supcode, '100', null, false, '1px solid ', '', 'CT', $font, '10', '', '', '');
        // $str .= $this->reporter->col('', '20', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->supname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->category, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->classname, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->stockgrp_name, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');
        // $str .= $this->reporter->col($data->warehouse, '100', null, false, '1px solid ', '', 'LT', $font, '10', '', '', '');

        // if ($itemstock != '(0,1)') {
        //   $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'RT', $font, '10', '', '', '');
        // }
        $str .= $this->reporter->endrow();
        // $scatgrp = strtoupper($data->category);
        // $part = strtoupper($data->part);
        // $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';

        $grandtotal = $grandtotal + $totalext;
        $totalbalqty = $totalbalqty + $data->balance;


        // if ($multiheader) {
        //   if ($this->reporter->linecounter >= $page) {
        //     $str .= $this->reporter->endtable();
        //     $str .= $this->reporter->page_break();
        //     $str .= $this->reporter->begintable($layoutsize);
        //     $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);

        //     if (!$allowfirstpage) {
        //       $str .= $this->default_displayHeader_hmNONE($config);
        //     }
        //     $str .= $this->default_hmnone_table_cols($layoutsize, $border, $font, $fontsize11, $config);
        //     $page = $page + $count;
        //   }
        // }
      }

      // $str .= $this->reporter->endtable();
      // $str .= $this->reporter->begintable($layoutsize);

      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '', '');

      // $str .= $this->reporter->col('OVERALL STOCKS :', '375', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
      // $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '', '');
      if ($itemstock != '(0,1)') {
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '170', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      } else {
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('OVERALL STOCKS :', '250', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col(number_format($totalbalqty, 2), '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'LT', $font, '10', '', '', '8px', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
      $str .= $this->reporter->endreport();
    } catch (Exception $e) {
      $this->othersClass->logConsole('Exception' . $e->getMessage());
    }

    return $str;
  }


  private function default_displayHeader_hmNONE($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '5px';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $asof       = $config['params']['dataparams']['start'];
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $categoryid = $config['params']['dataparams']['categoryid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $modelid    = $config['params']['dataparams']['modelid'];
    $modelname  = $config['params']['dataparams']['modelname'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $this->reportParams['orientation'] = 'l';

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
    $layoutsize = '1200';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $dtagathering = ' - (Current)';
    if ($config['params']['dataparams']['dtagathering'] == 'dhistory') {
      $dtagathering = ' - (History)';
    }
    $str .= $this->reporter->col('INVENTORY BALANCE' . $dtagathering, null, null, false, '1px solid ', '', '', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance as of : ' . $asof, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($barcode == '') {
      $str .= $this->reporter->col('Items : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Items : ' . $barcode, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    if ($groupname == '') {
      $str .= $this->reporter->col('Group : ALL', '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . $groupname, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }


    $str .= $this->reporter->col('Brand : ' . $brandname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('WH : ' . $whname, '300', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

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
    $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');

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
    $str .= $this->reporter->col('Item Stock : ' . strtoupper($itemstock), '150', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->col('Model : ' . $modelname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    if ($subcat == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    }

    // $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_hmnone_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $this->reportParams['orientation'] = 'l';
    // $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($itemstock != '(0,1)') {
      $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('ITEM DESCRIPTION', '170', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('SRP', '80', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('SUPPLIER NAME', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('CATEGORY', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('CLASS', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('SUBCLASS', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('WAREHOUSE', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    } else {
      $str .= $this->reporter->col('ITEM CODE', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '10', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('ITEM DESCRIPTION', '250', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('', '20', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('SUPPLIER NAME', '200', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('CATEGORY', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('CLASS', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('SUBCLASS', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
      $str .= $this->reporter->col('WAREHOUSE', '100', null, false, '1px solid ', 'TB', 'C', $font, '10', 'B', '', '8px', '');
    }
    return $str;
  }

  private function transpower_none_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $itemstock  = $config['params']['dataparams']['itemstock'];
    $amountformat   = $config['params']['dataparams']['amountformat'];
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('ITEM CODE', '120', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '420', null, false, '1px solid ', 'B', 'L', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    if ($itemstock != '(0,1)') {
      $str .= $this->reporter->col('SRP', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
      $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    } else {
      $str .= $this->reporter->col('MAX', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
      $str .= $this->reporter->col('MIN', '100', null, false, '1px solid ', 'B', 'R', $font, '10', 'B', '', '', '8px');
    }
    $str .= $this->reporter->col('UOM', '60', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    $str .= $this->reporter->col('COUNT', '100', null, false, '1px solid ', 'B', 'C', $font, '10', 'B', '', '', '8px');
    return $str;
  }


  public function transpower_NONE_Layout($config)
  {
    $str = '';
    try {
      $result = $this->reportDefault($config);

      $border = '1px solid';
      $border_line = '';
      $alignment = '';
      $font = $this->companysetup->getrptfont($config['params']);
      $font_size = 10;
      $fontsize11 = 11;
      $padding = '';
      $margin = '8px';

      // $center     = $config['params']['center'];
      // $username   = $config['params']['user'];
      $companyid = $config['params']['companyid'];

      $itemstock  = isset($config['params']['dataparams']['itemstock']) ? $config['params']['dataparams']['itemstock'] : '(0,1)';

      $count = 51;
      $page = 50;

      $this->reporter->linecounter = 0;

      if (empty($result)) {
        return $this->othersClass->emptydata($config);
      }


      $layoutsize = '1000';
      $str .= $this->reporter->beginreport($layoutsize);
      $str .= $this->default_displayHeader_NONE($config);
      $str .= $this->transpower_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

      $totalbalqty = 0;
      $part = "";
      $scatgrp = "";
      $igrp = "";
      $totalext = 0;
      $grandtotal = 0;
      $cost = 0;

      $multiheader = true;

      if (isset($config['params']['multiheader'])) {
        $multiheader = $config['params']['multiheader'];
      }

      foreach ($result as $key => $data) {

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

        //not majesty,unihome & goodfound
        if ($data->part != 0 || $data->part != null) {
          if (strtoupper($part) == strtoupper($data->part)) {
            $part = "";
          } else {
            $part = strtoupper($data->part);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($part, '100', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '450', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $part = "";
        }

        if ($data->category != 0 || $data->category != null) {
          if (strtoupper($scatgrp) == strtoupper($data->category)) {
            $scatgrp = "";
          } else {
            $scatgrp = strtoupper($data->category);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($scatgrp, '300', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $font_size, 'Bi', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          $scatgrp = "";
        }

        $totalext = $data->balance * $data->amt;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        $str .= $this->reporter->col($data->barcode, '120', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->itemname, '420', null, false, '1px solid ', '', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        if ($itemstock != '(0,1)') {
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($isamt, '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->maximum, 2), '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
          $str .= $this->reporter->col(number_format($data->minimum, 2), '100', null, false, '1px solid ', '', 'RT', $font, $font_size, '', '', '');
        }
        $str .= $this->reporter->col($data->uom, '60', null, false, '1px solid ', '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $scatgrp = strtoupper($data->category);
        $part = strtoupper($data->part);
        $igrp = isset($data->stockgrp_name) ? strtoupper($data->stockgrp_name) : '';

        $grandtotal = $grandtotal + $totalext;
        $totalbalqty = $totalbalqty + $data->balance;


        if ($multiheader) {
          if ($this->reporter->linecounter >= $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->reporter->begintable($layoutsize);
            $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);

            if (!$allowfirstpage) {
              $str .= $this->default_displayHeader_NONE($config);
            }
            $str .= $this->transpower_none_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
            $page = $page + $count;
          }
        }
      }

      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable($layoutsize);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '', '');

      $str .= $this->reporter->col('OVERALL STOCKS :', '375', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($totalbalqty, 2), '75', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
      $str .= $this->reporter->endreport();
    } catch (Exception $e) {
      $this->othersClass->logConsole('Exception' . $e->getMessage());
    }

    return $str;
  }
}//end class