<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

use DateTime;

class detailed_sales_report
{
  public $modulename = 'POS Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1000'];

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

    $fields = ['radioprint', 'start', 'end', 'dcentername', 'prefix'];
    $col1 = $this->fieldClass->create($fields);
    if ($companyid == 56) { // homeworks
      data_set($col1, 'radioprint.options', [
        // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
      ]);
    }
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.readonly', false);
    data_set($col1, 'prefix.readonly', false);

    $fields = [];
    if ($config['params']['user'] == 'sbc') {
      $fields = ['pos_station', 'cashier', 'stock_groupname', 'modelname', 'brandname', 'pospayment', 'wh', 'part', 'class', 'posdoctype', 'customer_name', 'categoryname', 'dagentname'];
    }

    $col2 = $this->fieldClass->create($fields);
    if ($config['params']['user'] == 'sbc') {
      data_set($col2, 'pos_station_lookup.lookupclass', 'pos_station');
      data_set($col2, 'cashier.lookupclass', 'cashier');
      data_set($col2, 'stock_groupname.lookupclass', 'lookupgroup_stock');
      data_set($col2, 'modelname.lookupclass', 'lookupmodel_stock');
      data_set($col2, 'brandname.lookupclass', 'lookupbrand');
      data_set($col2, 'pospayment.lookupclass', 'pospayment');
      data_set($col2, 'station_rep.lookupclass', 'station');
      data_set($col2, 'wh.lookupclass', 'wh');
      data_set($col2, 'part.lookupclass', 'lookuppart');
      data_set($col2, 'class.lookupclass', 'lookupclass');
      unset($col2['class']['labeldata']);
      data_set($col2, 'pos_doctype_lookup.lookupclass', "posdoctype");
      data_set($col2, 'customer_name.lookupclass', 'lookupcustomer');
      data_set($col2, 'categoryname.action', 'lookupcategoryitemstockcard');
      data_set($col2, 'dagentname.lookupclass', 'agent');
    }


    $fields = [];
    if ($config['params']['user'] == 'sbc') {
      array_push($fields, 'radioreporttype');
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'radioreporttype.label', "Format");

    if ($config['params']['user'] == 'sbc') {
      data_set($col3, 'radioreporttype.options', [
        // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'Standard', 'value' => 'standard', 'color' => 'red'],
        ['label' => 'Collection', 'value' => 'collection', 'color' => 'red'],
        ['label' => 'Net Sales', 'value' => 'nsales', 'color' => 'red'],
        ['label' => 'Viod Items', 'value' => 'vitem', 'color' => 'red'],
        ['label' => 'Categories', 'value' => 'category', 'color' => 'red'],
        ['label' => 'Sales w/o Del. Charge', 'value' => 'sdcharoe', 'color' => 'red'],
        ['label' => 'QSR/ FINE DINE', 'value' => 'qsr', 'color' => 'red'],
        ['label' => 'Crew Sales', 'value' => 'csales', 'color' => 'red'],
        ['label' => 'Cost of sales', 'value' => 'cosales', 'color' => 'red'],
        ['label' => 'Sales per Supplier', 'value' => 'sps', 'color' => 'red'],
        ['label' => 'Walk-in-Sales', 'value' => 'wis', 'color' => 'red'],
        ['label' => 'Daily Sub Category Sales Report', 'value' => 'dsub', 'color' => 'red'],
        ['label' => 'Ingredient', 'value' => 'ingredient', 'color' => 'red'],
        ['label' => 'Complimentary', 'value' => 'complimentary', 'color' => 'red'],
        ['label' => 'Deliver-Sales', 'value' => 'dsales', 'color' => 'red'],
      ]);
    }

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);



    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);

    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      date(now()) as end, 
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix,
      'standard' as reporttype,
      '' as stock_groupname,
      '0' as groupid,
      '0' as brandid,
      '0' as stationid,
      '' as station,
      '' as brandname,
      '' as brand,
      '0' as modelid,
      '' as modelname,
      '0' as model,
      '0' as stockgrp,
      '' as stockgrp_name,
      '' as brand_desc,
      '' as payment,
      '' as pospayment, 
      '' as paymentcond,
      '0' as partid,
      '' as partname,
      '0' as classid,
      '' as classic,
       '' as categoryid,
    '' as categoryname,
    '' as dateid,
    '' as customer,
    '' as clientname,
    '0' as clientid,
    '' as supplier,
    0 as stline,
    '' as cashier,
    0 as whid,
    '' as posdoctype
      ";

    return $this->coreFunctions->opentable($paramstr);
  }


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
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $reporttype = $config['params']['dataparams']['reporttype'];
    // if ($companyid == 56) { // homework
    // } else {
    //   return $this->detailed_salesreport_layout($config);
    // }

    switch ($reporttype) {
      case 'standard':
        if ($companyid == 56) { // homework
          return $this->homework_detailed_sales_layout($config);
        } else {
          return $this->detailed_sales_standard($config);
        }
        break;
      case 'sdcharoe':
      case 'wis':
      case 'dsales':
        return $this->detailed_sales_standard($config);
        break;

      case 'category':
        return $this->detailed_sales_categories($config);
        break;

      case 'sps':
        return $this->detailed_sales_sales_per_supplier($config);
        break;

      case 'dsub':
        return $this->detailed_sales_dailysub($config);
        break;

      case 'ingredient':
        return $this->detailed_sales_ingredient($config);
        break;

      case 'complimentary':
        return $this->detailed_sales_complimentary($config);
        break;

      case 'collection':
        return $this->detailed_sales_collection($config);
        break;

      case 'nsales':
        return $this->detailed_sales_netsales($config);
        break;

      case 'csales':
        return $this->detailed_sales_crewsales($config);
        break;

      case 'qsr':
        return $this->detailed_sales_qsr($config);
        break;

      case 'vitem':
        return $this->detailed_sales_void($config);
        break;

      case 'cosales':
        return $this->detailed_sales_costofsales($config);
        break;
    }
  }

  public function homework_detailed_sales_layout($config)
  {
    $data = $this->homeworks_query($config);
    $this->reporter->linecounter = 0;
    $count = 18;
    $page = 23;
    $layoutsize = '1200';
    $font = "Century Gothic";
    $fontsize = "5";
    $border = "1px solid ";
    $gr = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:65px;');
    $str .= $this->header_homework($config);
    $str .= $this->reporter->begintable($layoutsize);



    $srp = 0;
    $qty = 0;
    $grtotal = 0;
    $disc = 0;
    $ext = 0;
    $nvat = 0;
    $vatx = 0;
    $vatamt = 0;
    for ($i = 0; $i < count($data); $i++) {
      $gr = $data[$i]['amt'] * $data[$i]['iss'];
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['dateid'], '40', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['orno'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['department'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['class'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['subcategory'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['iss'], 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($gr, 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['disc'], 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['nvat'], 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['vatex'], 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['vatamt'], 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['suppcode'], '40', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['supplier'], '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['station'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['stationname'], '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['promodesc'], '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['serialno'], '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $srp += $data[$i]['amt'];
      $qty += $data[$i]['iss'];
      $grtotal += $gr;
      $disc += $data[$i]['disc'];
      $ext += $data[$i]['ext'];
      $nvat += $data[$i]['nvat'];
      $vatx += $data[$i]['vatex'];
      $vatamt += $data[$i]['vatamt'];
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total:', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($srp, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($qty, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grtotal, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($disc, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($ext, 2), '60', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($nvat, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatx, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatamt, 2), '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_homework($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];

    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $centername     = $config['params']['dataparams']['centername'];
    $data = $this->detailed_salesreport_query($config);
    $str = '';
    $layoutsize = '1200';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $dt = new DateTime($current_timestamp);
    $date = $dt->format('m/d/y H:i:s A');

    if ($config['params']['dataparams']['centername'] == '') {
      $centername = '-';
    }
    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($centername, null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED SALES REPORT', '1200', null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col($centername, '300', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Prindate : ' . $date, '800', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Refference #', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Code', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category', '60', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sub Category', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SRP', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Sales', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Net of Disc', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vatable Sales', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Excemt', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('12% Vat', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Code', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Name', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('StoreStation', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('StoreName', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount Type', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SerialNo', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function detailed_salesreport_query($config)
  {
    // $center = $config['params']['dataparams']['center'];
    // QUERY
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $filter   = "";
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];

    if ($prefix != '') {
      $filter = " and cntnum.doc in ('$prefix')";
    }
    if ($center != '') {
      $filter = " and cntnum.center = '$center'";
    }

    $query = "select cntnum.doc,item.barcode,item.itemname,item.brand,stock.isamt as amt,case when cntnum.doc = 'CM' then sum(stock.qty) else sum(stock.iss) end as iss,
    case cntnum.doc when 'CM' then sum(stock.ext-si.sramt-si.pwdamt-si.lessvat)-1 else (case item.barcode when '' then sum(stock.ext-si.sramt-si.pwdamt-si.lessvat)-1 else
    sum(stock.ext-si.sramt-si.pwdamt-si.lessvat) end) end as ext,
    sum(si.sramt+si.pwdamt+si.discamt) as disc,sum(si.nvat) as nvat,sum(si.vatex) as vatex,sum(si.vatamt) as vatamt,sum(si.lessvat) as lessvat,
    cb.clientname as stationname,head.docno,
    head.yourref,head.dateid, stock.rem,concat(left(stock.ref,2),right(stock.ref,5)) as orno,cntnum.station 
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
    left join item on item.itemid=stock.itemid
 
    left join client as supplier on supplier.client=item.supp
    left join cntnum on cntnum.trno=head.trno left join client as cb on cb.clientid = head.branch
    where 1=1 " . $filter . " and left(cntnum.bref,3) in ('SJS','SRS') and head.dateid between '$start' and '$end' 
    group by cntnum.doc,head.docno,stock.ref,item.barcode,item.itemname,item.brand,stock.isamt,
    supplier.clientname ,cb.clientname,head.yourref,head.dateid,stock.rem,cntnum.station  
    order by dateid,orno";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $data;
  }

  public function homeworks_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $filter   = "";
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];

    if ($prefix != '') {
      $filter = " and cntnum.doc in ('$prefix')";
    }
    if ($center != '') {
      $filter = " and cntnum.center = '$center'";
    }

    $query = "select cntnum.doc,item.barcode,item.itemname,item.brand,stock.isamt as amt,
	  case when cntnum.doc = 'CM' then (sum(stock.qty) * -1) else sum(stock.iss) end as iss,
    case cntnum.doc when 'CM' then (sum(stock.ext-si.sramt-si.pwdamt-si.lessvat)* -1) else sum(stock.ext-si.sramt-si.pwdamt-si.lessvat) end as ext,
    sum(si.sramt+si.pwdamt+si.discamt) as disc,sum(si.nvat) as nvat,
	  sum(si.vatex) as vatex,sum(si.vatamt) as vatamt,sum(si.lessvat) as lessvat,
    cb.clientname as stationname,head.docno,
    head.yourref,head.dateid, stock.rem,concat(left(stock.ref,2),
	  right(stock.ref,5)) as orno,cntnum.station ,
	  ifnull(subcat.name,'') as subcategory,ifnull(cat.name,'') as department,
	  itclass.cl_name as class,cl.clientname as supplier,cl.client as suppcode,
    (case when si.promodesc like '%value%' then 'VAD' else 
	  (case when si.promodesc like '%ATD%' then 'ATD' else 
	  (case when si.promodesc like '%promo%' then 'PROMO' else '' end) end) end) as promodesc,
    si.serialno
      
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
    left join item on item.itemid=stock.itemid
    
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join item_class as itclass on item.class = itclass.cl_id
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    left join client as cl on cl.clientid = item.supplier
    left join client as supplier on supplier.client=item.supp

    left join cntnum on cntnum.trno=head.trno left join client as cb on cb.clientid = head.branch
    where 1=1 " . $filter . " and left(cntnum.bref,3) in ('SJS','SRS') and head.dateid between '$start' and '$end' 
    group by cntnum.doc,head.docno,stock.ref,item.barcode,item.itemname,item.brand,stock.isamt,
    supplier.clientname ,cb.clientname,head.yourref,head.dateid,stock.rem,cntnum.station ,subcat.name,cat.name,itclass.cl_name,cl.clientname,cl.client,si.promodesc,si.serialno
    order by dateid,orno";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $data;
  }
  public function reportdatacsv($config)
  {
    $prefix    = $config['params']['dataparams']['prefix'];
    $filter   = "";
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];

    if ($prefix != '') {
      $filter = " and cntnum.doc in ('$prefix')";
    }
    if ($center != '') {
      $filter = " and cntnum.center = '$center'";
    }
    $query = "select date(head.dateid) as `Date`,concat(left(stock.ref,2),right(stock.ref,5)) as Refference,
    item.barcode as ItemCode,item.itemname as ItemName,
    ifnull(cat.name,'') as Department,itclass.cl_name as Category,ifnull(subcat.name,'') as Subcategory,
    ROUND(stock.isamt,2) as SRP,ROUND(case when cntnum.doc = 'CM' then (sum(stock.qty) * -1) else sum(stock.iss) end,2) as Qty,
    ROUND(stock.isamt * case cntnum.doc when 'CM' then sum(stock.qty)-1 else sum(stock.iss) end,2) as GrossSales,
    ROUND(sum(si.sramt+si.pwdamt+si.discamt),2) as Disc,
    ROUND(case cntnum.doc when 'CM' then (sum(stock.ext-si.sramt-si.pwdamt-si.lessvat)*-1) else sum(stock.ext-si.sramt-si.pwdamt-si.lessvat) end,2) as SalesOfNetDisc,
    ROUND(sum(si.nvat),2) as VatableSales,
    ROUND(sum(si.vatex),2) as VatExcemt,
    ROUND(sum(si.vatamt),2) as `12%Vat`,
    cl.client as SupplierCode,cl.clientname as SupplierName,
    cntnum.station as StoreStation, cb.clientname as StoreName,
    (case when si.promodesc like '%value%' then 'VAD' else (case when si.promodesc like '%ATD%' 
    then 'ATD' else (case when si.promodesc like '%promo%' then 'PROMO' else '' end) end) end) as DiscountType,
    si.serialno as SerialNo
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
    left join item on item.itemid=stock.itemid

    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join item_class as itclass on item.class = itclass.cl_id
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    left join client as cl on cl.clientid = item.supplier

    
    left join client as supplier on supplier.client=item.supp
    left join cntnum on cntnum.trno=head.trno left join client as cb on cb.clientid = head.branch
    where 1=1 " . $filter . " and left(cntnum.bref,3) in ('SJS','SRS') and date(head.dateid) between '$start' and '$end' 
    group by cntnum.doc,head.docno,stock.ref,item.barcode,item.itemname,stock.isamt,
    supplier.clientname ,cb.clientname,head.dateid,cntnum.station,
    subcat.name,cat.name,itclass.cl_name,cl.clientname,cl.client,si.promodesc,si.serialno 
    order by `Date`,Refference";
    $data = $this->coreFunctions->opentable($query);

    $vat12 = '12%Vat';
    foreach ($data as $key => $value) {
      $value->SRP = (float)$value->SRP;
      $value->Qty = (float)$value->Qty;
      $value->GrossSales = (float)$value->GrossSales;
      $value->Disc = (float)$value->Disc;
      $value->SalesOfNetDisc = (float)$value->SalesOfNetDisc;
      $value->VatableSales = (float)$value->VatableSales;
      $value->VatExcemt = (float)$value->VatExcemt;
      $value->$vat12 = (float)$value->$vat12;
    }


    return ['status' => true, 'msg' => 'Generating CSV successfully', 'data' => $data, 'params' => $this->reportParams, 'name' => 'DetailSales'];
  }

  private function detailed_salesreport_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];

    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $dcentername     = $config['params']['dataparams']['dcentername'];
    $data = $this->detailed_salesreport_query($config);
    $str = '';
    $layoutsize = '1400';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Branch: ' . ($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED SALES REPORT', '1000', null, false, $border, '', '', $font, '20', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('POS STATION : ', '150', null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['station']) ? $data[0]['station'] : ''), '1160', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SI #', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Code', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '310', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SRP', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Sales', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less 12% Vat', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Net of Disc', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vatable Sales', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Exempt', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('12% Vat', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Store', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function detailed_salesreport_layout($config)
  {
    $result = $this->detailed_salesreport_query($config);

    $this->reporter->linecounter = 0;
    $count = 18;
    $page = 23;
    $layoutsize = '1400';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->detailed_salesreport_query($config);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;margin-left:10px;margin-right:10px;');
    $str .= $this->detailed_salesreport_header($config);
    // $start     = $config['params']['dataparams']['start'];
    // $end     = $config['params']['dataparams']['end'];

    $totalsrp = 0;
    $totalqty = 0;
    $totalgross = 0;
    $totaldisc = 0;
    $totallessvat = 0;
    $totalnetdesc = 0;
    $totalvatsales = 0;
    $totalvatexc = 0;
    $totalvat = 0;

    $c = 0;

    for ($i = 0; $i < count($data); $i++) {
      $gr = $data[$i]['amt'] * $data[$i]['iss'];
      $str .= $this->reporter->addline(); // increment linecounter
      $len = strlen($data[$i]['itemname']);
      $c++;
      if ($len > 38) {
        $c++;
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['dateid'], '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['docno'], '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '310', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['iss'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($gr, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['disc'], 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['lessvat'], 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['nvat'], 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['vatex'], 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['vatamt'], 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['station'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $totalsrp += $data[$i]['amt'];
      $totalqty += $data[$i]['iss'];
      $totalgross += $gr;
      $totaldisc += $data[$i]['disc'];
      $totallessvat += $data[$i]['lessvat'];
      $totalnetdesc += $data[$i]['ext'];
      $totalvatsales += $data[$i]['nvat'];
      $totalvatexc += $data[$i]['vatex'];
      $totalvat += $data[$i]['vatamt'];
      if ($c >= $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->detailed_salesreport_header($config);
        $str .= $this->reporter->printline();
        // $page = $page + $count;
        $c = 0;
      }
    }

    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL: ', '180', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsrp, 2), '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgross, 2), '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldisc, 2), '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totallessvat, 2), '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalnetdesc, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvatsales, 2), '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvatexc, 2), '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvat, 2), '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    // TOTAL


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function header_homeworks($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];

    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $centername     = $config['params']['dataparams']['centername'];
    $data = $this->detailed_salesreport_query($config);
    $str = '';
    $layoutsize = '1200';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $dt = new DateTime($current_timestamp);
    $date = $dt->format('m/d/y H:i:s A');

    if ($config['params']['dataparams']['centername'] == '') {
      $centername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED SALES REPORT', '1200', null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col($centername, '300', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Prindate : ' . $date, '800', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Refference #', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Code', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category', '60', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sub Category', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SRP', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Sales', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Net of Disc', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vatable Sales', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Excemt', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('12% Vat', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Code', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Name', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('StoreStation', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('StoreName', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount Type', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SerialNo', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  public function categories_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];


    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM SALES BY CATEGORY', '1000', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1000', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($modelid == '0') {
      $str .= $this->reporter->col('MODEL:  ALL MODEL', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('MODEL:  ' . strtoupper($model), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($paymentCond == '') {
      $str .= $this->reporter->col('PAYMENT:  ALL PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PAYMENT:  ' . strtoupper($pospayment), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code', '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '480', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quantity', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function categories_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = $checkwh";
    }

    $query = "select  sg.stockgrp_name as category, item.barcode, item.itemname, head.dateid,
          sum(stock.iss - stock.qty) as qty, 
          sum(stock.isamt * stock.isqty) as amt

          from lahead as head
          left join lastock as stock on head.trno = stock.trno
          join item as item on item.itemid = stock.itemid
          left join cntnum on head.trno = cntnum.trno
          left join stockgrp_masterfile as sg on sg.stockgrp_id = item.groupid
          left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
          left join frontend_ebrands  as brand on brand.brandid = item.brand
          left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
          left join client on client.client = head.client
          left join client as wh on client.client = head.wh


          where item.barcode <>'' and left (cntnum.bref,3) in ('SJS','SRS') 
          and cntnum.center = '$center' 
          and date(head.dateid) between '$start' and '$end' $filter
          group by sg.stockgrp_name, item.barcode, item.itemname, head.dateid, cntnum.doc, cntnum.bref
    
          union all

          select  sg.stockgrp_name as category, item.barcode, item.itemname, head.dateid,
         sum(stock.iss - stock.qty) as qty, 
          sum(stock.isamt * stock.isqty) as amt

          
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          join item as item on item.itemid = stock.itemid    
          left join stockgrp_masterfile as sg on sg.stockgrp_id = item.groupid 
          left join cntnum on head.trno = cntnum.trno
          left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
          left join frontend_ebrands  as brand on brand.brandid = item.brand
          left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
                    left join client on client.clientid = head.clientid
           left join client as wh on client.clientid = head.whid

          where item.barcode <>'' and left (cntnum.bref,3) in ('SJS','SRS') 
          and cntnum.center = '$center' 
          and date(head.dateid) between '$start' and '$end' $filter
          group by sg.stockgrp_name, item.barcode, item.itemname, head.dateid, cntnum.doc, cntnum.bref

          order by category,dateid
          ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }




  public function detailed_sales_categories($config)
  {

    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->categories_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->categories_header($config);


    $prevGroup = '';
    $groupQty = 0;
    $groupAmt = 0;


    for ($i = 0; $i < count($data); $i++) {
      $currGroup = $data[$i]['category'];


      $currGroupKey = empty($currGroup) ? ' ' : $currGroup;




      if ($currGroupKey !== $prevGroup && $prevGroup !== '') {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '480', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((!empty($groupQty) && $groupQty != 0) ? number_format($groupQty, 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((!empty($groupAmt) && $groupAmt != 0) ? number_format($groupAmt, 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $groupQty = 0;
        $groupAmt = 0;
      }


      if ($currGroupKey !== $prevGroup) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Category: " . $currGroupKey, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevGroup = $currGroupKey;
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['barcode'], '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '480', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(date('Y-m-d', strtotime($data[$i]['dateid'])), '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($data[$i]['qty']) && $data[$i]['qty'] != 0) ? number_format($data[$i]['qty'], 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($data[$i]['amt']) && $data[$i]['amt'] != 0) ? number_format($data[$i]['amt'], 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $groupQty += $data[$i]['qty'];
      $groupAmt += $data[$i]['amt'];
    }


    if ($prevGroup !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '480', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($groupQty) && $groupQty != 0) ? number_format($groupQty, 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col((!empty($groupAmt) && $groupAmt != 0) ? number_format($groupAmt, 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }




    $str .= $this->reporter->endreport();

    return $str;
  }

  public function detailed_sales_sales_per_supplier($config)
  {

    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->sales_per_supplier_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];



    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }


    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->sales_per_supplier_header($config);







    $prevDate = '';
    $prevSupplier = '';


    $dateQty  = 0;
    $dateCost = 0;
    $dateSell = 0;


    $grandQty  = 0;
    $grandCost = 0;
    $grandSell = 0;

    for ($i = 0; $i < count($data); $i++) {

      $currDate = date('Y-m-d', strtotime($data[$i]['dateid']));
      $currSupplier = $data[$i]['clientname'];
      $sell         = $data[$i]['isamt'];
      $qty          = $data[$i]['qty'];
      $cost         = $data[$i]['cost'];


      if ($i > 0 && $currDate !== $prevDate) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Total ($prevDate):", '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col((!empty($dateSell) && $dateSell != 0) ? number_format($dateSell, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((!empty($dateCost) && $dateCost != 0) ? number_format($dateCost, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((!empty($dateQty) && $dateQty != 0) ? number_format($dateQty, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $dateQty = $dateCost = $dateSell = 0;
        $prevSupplier = '';
      }


      if ($currDate !== $prevDate) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date: ' . $currDate, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevDate = $currDate;
      }


      if ($currSupplier !== $prevSupplier) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $currSupplier, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevSupplier = $currSupplier;
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['barcode'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['Description'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($sell) && $sell != 0) ? number_format($sell, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($cost) && $cost != 0) ? number_format($cost, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($qty) && $qty != 0) ? number_format($qty, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $dateQty  += $qty;
      $dateCost += $cost;
      $dateSell += $sell;

      $grandQty  += $qty;
      $grandCost += $cost;
      $grandSell += $sell;
    }


    if (!empty($prevDate)) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("Total ($prevDate):", '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col((!empty($dateSell) && $dateSell != 0) ? number_format($dateSell, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col((!empty($dateCost) && $dateCost != 0) ? number_format($dateCost, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col((!empty($dateQty) && $dateQty != 0) ? number_format($dateQty, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((!empty($grandSell) && $grandSell != 0) ? number_format($grandSell, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((!empty($grandCost) && $grandCost != 0) ? number_format($grandCost, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((!empty($grandQty) && $grandQty != 0) ? number_format($grandQty, 2) : '-', '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->endreport();

    return $str;
  }


  public function sales_per_supplier_header($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['modelid'];
    $model = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];



    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES PER SUPPLIER', '1200', null, false, $border, '', 'C', $font, '12', 'B', 'blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();






    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode:', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Selling', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quantity', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }


  public function sales_per_supplier_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = $checkwh";
    }

    $query = "
    select dateid, clientname, barcode, Description, isamt, cost,  qty from (
    
    select head.dateid, supp.clientname, item.barcode, item.itemname as Description,
    sum(stock.isamt) as isamt,
    stock.rrcost as cost,
    sum(stock.isqty) as qty
    from lahead as head

    left join lastock as stock on head.trno = stock.trno
    join item as item on stock.itemid = item.itemid
    left join cntnum  on cntnum.trno = head.trno
    left join client on head.client = client.client
    left join client as supp on supp.client = item.supp
    left join client as wh on wh.client = head.wh

    where cntnum.bref in ('SJS', 'SRS') and cntnum.center = '$center' and date(head.dateid) between '$start' and '$end' $filter
    group by head.dateid, supp.clientname, item.barcode, item.itemname, stock.rrcost

    union all

    select head.dateid, supp.clientname, item.barcode, item.itemname as Description,
    sum(stock.isamt) as isamt,
    stock.rrcost as cost,
    sum(stock.isqty) as qty
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    join item as item on stock.itemid = item.itemid
    left join cntnum  on cntnum.trno = head.trno 
    left join client on head.clientid = client.clientid
    left join client as supp on supp.client = item.supp
    left join client as wh on wh.clientid = head.whid


    where cntnum.bref in ('SJS', 'SRS') and cntnum.center = '$center' and date(head.dateid) between '$start' and '$end' $filter
    group by head.dateid, supp.clientname, item.barcode, item.itemname,  stock.rrcost
    order by dateid, clientname, barcode

   ) as t 


    ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }




  public function dailysub_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];



    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Daily Sub Category Sales Report', '1000', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, $fontsize, 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SKU Number:', '125', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '275', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Cost', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Profit', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function dailysub_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }

    if ($checkwh != '0') {
      $filter   .= " and wh.wh = $checkwh";
    }

    $query = "

    
select class.cl_name, item.barcode as sku, item.itemname, round(sum(stock.iss),2 ) as qty, stock.amt as amount,
    sum( stock.isqty * stock.isamt) AS totalsales,
    stock.rrcost as cost,
    stock.rrcost as totalcost
from lahead as head


left join lastock as stock on stock.trno = head.trno
left join item on item.itemid = stock.itemid
left join cntnum on cntnum.trno = head.trno
left join item_class as class on item.class = class.cl_id
left join client on client.client = head.client
left join client as wh on wh.client = head.wh

where cntnum.bref in ('SJS','SRS')and cntnum.center = '$center' and date(head.dateid) between '$start' and '$end' $filter
group by  item.barcode, class.cl_name, item.itemname, stock.amt,rrcost

union all

select class.cl_name, item.barcode as sku, item.itemname, round(sum(stock.iss),2 ) as qty, stock.amt as amount,
    sum( stock.isqty * stock.isamt) AS totalsales,
    stock.rrcost as cost,
    stock.rrcost as totalcost
from glhead as head
left join glstock as stock on stock.trno = head.trno
left join item on item.itemid = stock.itemid
left join cntnum on cntnum.trno = head.trno
left join item_class as class on item.class = class.cl_id
left join client on client.clientid = head.clientid
left join client as wh on wh.clientid = head.whid

where cntnum.bref in ('SJS','SRS')and cntnum.center = '$center' and date(head.dateid) between '$start' and '$end' $filter
group by  item.barcode, class.cl_name, item.itemname, stock.amt,rrcost
order by cl_name

    ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }




  public function detailed_sales_dailysub($config)
  {

    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->dailysub_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->dailysub_header($config);








    $prevClass = null;

    $subQty = 0;
    $subAmt = 0;
    $subTotalSales = 0;
    $subCost = 0;
    $subTotalCost = 0;
    $subGrossProfit = 0;


    $grandQty = 0;
    $grandAmt = 0;
    $grandTotalSales = 0;
    $grandTotalCost = 0;
    $grandGrossProfit = 0;


    for ($i = 0; $i < count($data); $i++) {
      $currClass = !empty($data[$i]['cl_name']) ? $data[$i]['cl_name'] : ' ';


      if ($prevClass === null || $currClass !== $prevClass) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(' ' . $currClass, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['sku'], '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '275', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['qty']) && $data[$i]['qty'] != 0 ? number_format($data[$i]['qty'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['amount']) && $data[$i]['amount'] != 0 ? number_format($data[$i]['amount'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['totalsales']) && $data[$i]['totalsales'] != 0 ? number_format($data[$i]['totalsales'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['cost']) && $data[$i]['cost'] != 0 ? number_format($data[$i]['cost'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['totalcost']) && $data[$i]['totalcost'] != 0 ? number_format($data[$i]['totalcost'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['totalsales']) && $data[$i]['totalsales'] != 0 ? number_format($data[$i]['totalsales'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $subQty += $data[$i]['qty'];
      $subAmt += $data[$i]['amount'];
      $subTotalSales += $data[$i]['totalsales'];
      $subCost += $data[$i]['cost'];
      $subTotalCost += $data[$i]['totalcost'];
      $subGrossProfit += ($data[$i]['totalsales'] - $data[$i]['totalcost']);


      $grandQty += $data[$i]['qty'];
      $grandAmt += $data[$i]['amount'];
      $grandTotalSales += $data[$i]['totalsales'];
      $grandTotalCost += $data[$i]['totalcost'];
      $grandGrossProfit += ($data[$i]['totalsales'] - $data[$i]['totalcost']);


      $nextClass = isset($data[$i + 1]) ? (!empty($data[$i + 1]['cl_name']) ? $data[$i + 1]['cl_name'] : ' ') : null;
      if ($nextClass !== $currClass) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sub Total : ' . $currClass, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(!empty($subQty) && $subQty != 0 ? number_format($subQty, 2) : '-', '90', null, false, '2px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(!empty($subAmt) && $subAmt != 0 ? number_format($subAmt, 2) : '-', '90', null, false, '2px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(!empty($subTotalSales) && $subTotalSales != 0 ? number_format($subTotalSales, 2) : '-', '90', null, false, '2px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(!empty($subCost) && $subCost != 0 ? number_format($subCost, 2) : '-', '90', null, false, '2px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(!empty($subTotalCost) && $subTotalCost != 0 ? number_format($subTotalCost, 2) : '-', '90', null, false, '2px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(!empty($subTotalSales) && $subTotalSales != 0 ? number_format($subTotalSales, 2) : '-', '100', null, false, '2px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $subQty = $subAmt = $subTotalSales = $subCost = $subTotalCost = $subGrossProfit = 0;
      }

      $prevClass = $currClass;
    }



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total', '125', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '275', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(!empty($grandQty) && $grandQty != 0 ? number_format($grandQty, 2) : '-', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(!empty($grandAmt) && $grandAmt != 0 ? number_format($grandAmt, 2) : '-', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(!empty($grandTotalSales) && $grandTotalSales != 0 ? number_format($grandTotalSales, 2) : '-', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(!empty($subCost) && $subCost != 0 ? number_format($subCost, 2) : '-', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(!empty($grandTotalCost) && $grandTotalCost != 0 ? number_format($grandTotalCost, 2) : '-', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(!empty($grandTotalSales) && $grandTotalSales != 0 ? number_format($grandTotalSales, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '275', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '275', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->endreport();

    return $str;
  }



  public function ingredient_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $date = date("m/d/Y g:i a");


    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY INGREDIENT CONSUMPTION', '300', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Print date:' . $date, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1000', null, false, $border, '', 'L', $font, 8, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CODE:', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NAME', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COST', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function ingredient_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.whid = $checkwh";
    }

    $query = "select barcode, itemname, cost, qty, uom, qty * cost as total from (

select item.barcode, item.itemname, sum(stock.cost) as cost, case when cntnum.doc = 'CM' then sum(stock.qty) else sum(stock.iss) end as qty, stock.uom
from lahead as head

left join lastock as stock on head.trno = stock.trno
left join item on stock.itemid = item.itemid
left join cntnum on head.trno = cntnum.trno
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join client on client.client = head.client
left join client as wh on wh.client = head.wh


where item.barcode <>'' and left(cntnum.bref,3) in ('SJS','SRS')and stock.iscomponent = 1 and cntnum.center = '$center' and date(head.dateid) between '$start' and '$end'
group by item.barcode, item.itemname, cntnum.bref, cntnum.doc, stock.uom
union all

select item.barcode, item.itemname, sum(stock.cost) as cost, case when cntnum.doc = 'CM' then sum(stock.qty) else sum(stock.iss) end as qty, stock.uom
from glhead as head

left join glstock as stock on head.trno = stock.trno
left join item on stock.itemid = item.itemid
left join cntnum on head.trno = cntnum.trno
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join client on client.clientid = head.clientid
left join client as wh on wh.clientid = head.whid


where item.barcode <>'' and left(cntnum.bref,3) in ('SJS','SRS')and stock.iscomponent = 1 and cntnum.center = '$center' and date(head.dateid) between '$start' and '$end'
group by item.barcode, item.itemname, cntnum.bref, cntnum.doc, stock.uom

) as t

    ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function detailed_sales_ingredient($config)
  {
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->ingredient_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->ingredient_header($config);


    $subcost = 0;
    $subqty = 0;
    $total = 0;

    $grandcost = 0;
    $grandqty = 0;
    $grandtotal = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['barcode'], '166', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '166', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['cost'], 2), '166', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 2), '166', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['uom'], '166', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['total'], 2), '166', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


      $subcost += $data[$i]['cost'];
      $subqty += $data[$i]['qty'];
      $total += $data[$i]['total'];

      $grandcost = $subcost;
      $grandqty = $subqty;
      $grandtotal = $total;

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MENU NAME', '166', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL:', '166', 10, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subcost, 2), '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, 2), '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(' GRAND TOTAL:', '166', 10, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcost, 2), '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandqty, 2), '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '166', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();






    $str .= $this->reporter->endreport();

    return $str;
  }

  public function detailed_sales_complimentary($config)
  {
    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->complimentary_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';

    $prevDate = '';
    $prevStation = '';
    $prevcashier = '';
    $grandtotalcost = 0;
    $str .= $this->reporter->beginreport();
    $str .= $this->complimentary_header($config);



    for ($i = 0; $i < count($data); $i++) {


      $currStation = $data[$i]['station'];
      $currCashier = strtoupper($data[$i]['openby']);
      $currDate    = $data[$i]['dateid'];


      if ($currStation !== $prevStation) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Station: ' . $currStation, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $prevStation = $currStation;

        $prevcashier = '';
        $prevDate = '';
      }


      if ($currCashier !== $prevcashier) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Cashier: " . $currCashier, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevcashier = $currCashier;
        $prevDate = '';
      }

      if ($currDate !== $prevDate) {
        $prevDate = $currDate;
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['docno'], '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['clientname'], '150', null, false, '1px dotted', 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '150', null, false, '1px dotted', 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, '1px dotted', 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '150', null, false, '1px dotted', 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['wh'], '150', null, false, '1px dotted', 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 2), '150', null, false, '1px dotted', 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['cost'], 2), '150', null, false, '1px dotted', 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['totalcost'], 2), '150', null, false, '1px dotted', 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');


      $grandtotalcost += $data[$i]['totalcost'];
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', 10, false, $border, 'T', 'R', $font, $fontsize, '', '', '#CCCCCC');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total Cost:', '150', 10, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotalcost, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();









    $str .= $this->reporter->endreport();

    return $str;
  }
  public function complimentary_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $date = date("m/d/Y g:i a");


    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMPLIMENTARY TRANSACTION REPORT', '1000', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer:', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quantity', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TBotal Cost', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function complimentary_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = $checkwh";
    }

    $query = "select cntnum.station, h.openby, h.docno, client.clientname, head.dateid, item.barcode, item.itemname,
wh.clientname as wh, stock.isqty as qty, stock.rrcost as cost, stock.isqty * stock.rrcost as totalcost

from lahead as head

left join lastock as stock on stock.trno = head.trno
left join client on client.client = head.client
left join head as h on h.webtrno =  head.trno   and h.docno = stock.ref
join item on item.itemid = stock.itemid
left join client as wh on wh.client = head.wh
left join cntnum on head.trno = cntnum.trno


where stock.iscomp = 1 and  cntnum.bref in ('SJS','SRS') and cntnum.center = '$center'
and date(head.dateid) between '$start' and '$end' $filter

union all

select cntnum.station, h.openby, h.docno,  client.clientname, head.dateid, item.barcode, item.itemname, wh.clientname as wh, stock.isqty, stock.rrcost, stock.isqty * stock.rrcost as totalcost

from glhead as head

left join glstock as stock on stock.trno = head.trno
left join client on client.clientid = head.clientid
left join head as h on h.webtrno =  head.trno   and h.docno = stock.ref
join item on item.itemid = stock.itemid
left join client as wh on wh.clientid = head.whid
left join cntnum on head.trno = cntnum.trno


where stock.iscomp = 1 and  cntnum.bref in ('SJS','SRS') and cntnum.center = '$center'
and date(head.dateid) between '$start' and '$end' $filter

order by station, openby, docno
    ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function detailed_sales_standard($config)
  {
    $layoutsize = '1501';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->standard_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';

    $prevStation = '';
    $prevCashier = '';
    $prevDocno = '';
    $prevCustomer = '';
    $prevDate = '';

    $cashierTotalCost = 0;
    $stationTotalAmt  = 0;

    $totalQty = 0;
    $totalOtherDisc = 0;
    $totalDisc = 0;
    $Amt = 0;
    $totalVat = 0;
    $totalExt = 0;


    $totalReturnAmt = 0;
    $totalVoidAmt = 0;
    $totalVatex = 0;
    $totalZeroRatedAmt = 0;
    $totalSramt = 0;
    $totalPwdamt = 0;
    $totalSoloamt = 0;
    $totalOtherDiscount = 0;
    $totalSales = 0;
    $netVat = 0;
    $zeroRated = 0;

    $str .= $this->reporter->beginreport();
    $str .= $this->standard_header($config);

    for ($i = 0; $i < count($data); $i++) {

      $currStation = $data[$i]['station'];
      $currCashier = strtoupper($data[$i]['openby']);


      if ($currStation !== $prevStation && $prevStation !== '') {


        if ($prevCashier !== '') {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("($prevCashier)" . " " . "Cashier Subtotal for " . ": " . number_format($cashierTotalCost, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $cashierTotalCost = 0;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Station Subtotal for " . $prevStation . ": " . number_format($stationTotalAmt, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $stationTotalAmt = 0;


        $prevCashier = '';
      }


      if ($currStation !== $prevStation) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Station: " . $currStation, null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevStation = $currStation;
        $prevCashier = null;
      }


      if ($currCashier !== $prevCashier && $prevCashier !== null) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("($prevCashier)" . " " . "Cashier Subtotal " . ": " . number_format($cashierTotalCost, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $cashierTotalCost = 0;
      }


      if ($currCashier !== $prevCashier) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Cashier: " . $currCashier, null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevCashier = $currCashier;
      }

      $docno = $data[$i]['docno'];

      if ($docno === $prevDocno) {
        $data[$i]['docno'] = '';
      } else {
        $prevDocno = $docno;
      }

      $customer = $data[$i]['Customer'];
      if ($customer === $prevCustomer) {
        $data[$i]['Customer'] = '';
      } else {
        $prevCustomer = $customer;
      }

      $dateid = $data[$i]['dateid'];
      if ($dateid === $prevDate) {
        $data[$i]['dateid'] = '';
      } else {
        $prevDate = $dateid;
        $data[$i]['dateid'] = date('Y-m-d', strtotime($dateid)); // format only once 
      }

      $cashierTotalCost += $data[$i]['gross'];
      $stationTotalAmt  += $data[$i]['gross'];

      // GRAND TOTALS
      $totalQty        += $data[$i]['qty'];
      $totalOtherDisc  += $data[$i]['otherdisc'];
      $totalDisc       += $data[$i]['disc'];
      $Amt        += $data[$i]['amt'];
      $totalVat        += $data[$i]['vat'];
      $totalExt        += $data[$i]['grossSales'];
      $totalSramt      += $data[$i]['sramt'];
      $totalPwdamt     += $data[$i]['pwdamt'];
      $totalSoloamt    += $data[$i]['soloamt'];
      $totalOtherDiscount += $data[$i]['otherdiscount'];
      $totalVoidAmt += $data[$i]['void'];
      $totalReturnAmt += $data[$i]['returnsales'];
      $totalSales += $data[$i]['gross'];
      $totalVatex += $data[$i]['vatex'];
      $zeroRated += $data[$i]['zerorated'];




      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['docno'], '107', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['Customer'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['agent'], '50', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['dateid'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['barcode'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['itemname'], '147', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['qty']) && $data[$i]['qty'] != 0) ? number_format($data[$i]['qty'], 2) : '-', '40', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['uom'], '79', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['wh'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['otherdisc']) && $data[$i]['otherdisc'] != 0) ? number_format($data[$i]['otherdisc'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['disc']) && $data[$i]['disc'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['cost']) && $data[$i]['cost'] != 0) ? number_format($data[$i]['cost'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['amt']) && $data[$i]['amt'] != 0) ? number_format($data[$i]['amt'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['vat']) && $data[$i]['vat'] != 0) ? number_format($data[$i]['vat'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['totalcost']) && $data[$i]['totalcost'] != 0) ? number_format($data[$i]['totalcost'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['ext']) && $data[$i]['ext'] != 0) ? number_format($data[$i]['ext'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['ref'], '79', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col(!empty($data[$i]['ispromo']) && $data[$i]['ispromo'] == 1 ? $data[$i]['ispromo'] : '', '79', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevCashier !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("($prevCashier)" . " " . "Cashier Subtotal " . ": " . number_format($cashierTotalCost, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevStation !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("Station Subtotal for " . $prevStation . ": " . number_format($stationTotalAmt, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '52', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("Total Quantiy: " . number_format($totalQty, 2), '107', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '50', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '147', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '40', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'C', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col(number_format($totalDisc, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" ", '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" ", '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($Amt, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVat, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col(number_format($totalSales, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'C', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'C', $font, $fontsize);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $total = $totalExt - $totalReturnAmt - $totalVoidAmt;
    $netvat = $totalSales -  $totalVat;
    $netVat = $netvat -  $totalVatex;
    $grandDisc = $totalDisc + $totalSramt + $totalPwdamt + $totalSoloamt + $totalOtherDiscount;


    $str .= $this->reporter->begintable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Gross Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalExt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Return Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalReturnAmt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Void Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVoidAmt, 2), '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($total, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Regular: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Total Sales:", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSales, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Less Vat(SC/PWD/Solo Parent/Diplomat):", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalOtherDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Senior: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSramt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Vat: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVat, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Less Regular Discounts: ", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" PWD: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalPwdamt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Net Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($netvat, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("VAT Exempt: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVatex, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Solo Parent: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSoloamt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Vatable Sales (Net of Vat): ", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($netVat, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Other Discount: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalOtherDiscount, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Zero Rated", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($zeroRated, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Total Discounts: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($grandDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Other Discount Breakdown", '190', null, false, $border, 'B', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $disc = $this->disc_query($config);


    for ($j = 0; $j < count($disc); $j++) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($disc[$j]['disctype'], '70', null, false, $border, '', 'L', $font, $fontsize, 'B');

      $str .= $this->reporter->col($disc[$j]['discamt'], '70', null, false, $border, '', 'R', $font, $fontsize, 'B');

      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();






    $str .= $this->reporter->endreport();
    return $str;
  }

  public function detailed_sales_standard_v2($config)
  {
    $layoutsize = '1501';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->standard_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';

    $prevStation = '';
    $prevCashier = '';
    $prevDocno = '';
    $prevCustomer = '';
    $prevDate = '';

    $cashierTotalCost = 0;
    $stationTotalAmt  = 0;

    $totalQty = 0;
    $totalOtherDisc = 0;
    $totalDisc = 0;
    $Amt = 0;
    $totalVat = 0;
    $totalExt = 0;


    $totalReturnAmt = 0;
    $totalVoidAmt = 0;
    $totalVatex = 0;
    $totalZeroRatedAmt = 0;
    $totalSramt = 0;
    $totalPwdamt = 0;
    $totalSoloamt = 0;
    $totalOtherDiscount = 0;
    $totalSales = 0;
    $netVat = 0;
    $zeroRated = 0;

    $str .= $this->reporter->beginreport();
    $str .= $this->standard_header($config);

    for ($i = 0; $i < count($data); $i++) {

      $currStation = $data[$i]['station'];
      $currCashier = strtoupper($data[$i]['openby']);


      if ($currStation !== $prevStation && $prevStation !== '') {


        if ($prevCashier !== '') {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("($prevCashier)" . " " . "Cashier Subtotal for " . ": " . number_format($cashierTotalCost, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $cashierTotalCost = 0;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Station Subtotal for " . $prevStation . ": " . number_format($stationTotalAmt, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $stationTotalAmt = 0;


        $prevCashier = '';
      }


      if ($currStation !== $prevStation) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Station: " . $currStation, null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevStation = $currStation;
        $prevCashier = null;
      }


      if ($currCashier !== $prevCashier && $prevCashier !== null) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("($prevCashier)" . " " . "Cashier Subtotal " . ": " . number_format($cashierTotalCost, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $cashierTotalCost = 0;
      }


      if ($currCashier !== $prevCashier) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Cashier: " . $currCashier, null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $prevCashier = $currCashier;
      }

      $docno = $data[$i]['docno'];

      if ($docno === $prevDocno) {
        $data[$i]['docno'] = '';
      } else {
        $prevDocno = $docno;
      }

      $customer = $data[$i]['Customer'];
      if ($customer === $prevCustomer) {
        $data[$i]['Customer'] = '';
      } else {
        $prevCustomer = $customer;
      }

      $dateid = $data[$i]['dateid'];
      if ($dateid === $prevDate) {
        $data[$i]['dateid'] = '';
      } else {
        $prevDate = $dateid;
        $data[$i]['dateid'] = date('Y-m-d', strtotime($dateid)); // format only once 
      }

      $cashierTotalCost += $data[$i]['gross'];
      $stationTotalAmt  += $data[$i]['gross'];

      // GRAND TOTALS
      $totalQty        += $data[$i]['qty'];
      $totalOtherDisc  += $data[$i]['otherdisc'];
      $totalDisc       += $data[$i]['disc'];
      $Amt        += $data[$i]['amt'];
      $totalVat        += $data[$i]['vat'];
      $totalExt        += $data[$i]['grossSales'];
      $totalSramt      += $data[$i]['sramt'];
      $totalPwdamt     += $data[$i]['pwdamt'];
      $totalSoloamt    += $data[$i]['soloamt'];
      $totalOtherDiscount += $data[$i]['otherdiscount'];
      $totalVoidAmt += $data[$i]['void'];
      $totalReturnAmt += $data[$i]['returnsales'];
      $totalSales += $data[$i]['gross'];
      $totalVatex += $data[$i]['vatex'];
      $zeroRated += $data[$i]['zerorated'];




      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['docno'], '107', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['Customer'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['agent'], '50', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['dateid'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['barcode'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['itemname'], '147', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['qty']) && $data[$i]['qty'] != 0) ? number_format($data[$i]['qty'], 2) : '-', '40', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['uom'], '79', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['wh'], '79', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['otherdisc']) && $data[$i]['otherdisc'] != 0) ? number_format($data[$i]['otherdisc'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['disc']) && $data[$i]['disc'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['cost']) && $data[$i]['cost'] != 0) ? number_format($data[$i]['cost'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['amt']) && $data[$i]['amt'] != 0) ? number_format($data[$i]['amt'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['vat']) && $data[$i]['vat'] != 0) ? number_format($data[$i]['vat'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['totalcost']) && $data[$i]['totalcost'] != 0) ? number_format($data[$i]['totalcost'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col((isset($data[$i]['ext']) && $data[$i]['ext'] != 0) ? number_format($data[$i]['ext'], 2) : '-', '79', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['ref'], '79', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col(!empty($data[$i]['ispromo']) && $data[$i]['ispromo'] == 1 ? $data[$i]['ispromo'] : '', '79', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevCashier !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("($prevCashier)" . " " . "Cashier Subtotal " . ": " . number_format($cashierTotalCost, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevStation !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("Station Subtotal for " . $prevStation . ": " . number_format($stationTotalAmt, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '52', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("Total Quantiy: " . number_format($totalQty, 2), '107', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '50', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '147', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col("", '40', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'C', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'L', $font, $fontsize);
    $str .= $this->reporter->col(number_format($totalDisc, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" ", '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" ", '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($Amt, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVat, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col(number_format($totalSales, 2), '79', null, false, $border, 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'C', $font, $fontsize);
    $str .= $this->reporter->col("", '79', null, false, $border, 'T', 'C', $font, $fontsize);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $total = $totalExt - $totalReturnAmt - $totalVoidAmt;
    $netvat = $totalSales -  $totalVat;
    $netVat = $netvat -  $totalVatex;
    $grandDisc = $totalDisc + $totalSramt + $totalPwdamt + $totalSoloamt + $totalOtherDiscount;


    $str .= $this->reporter->begintable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Gross Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalExt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Return Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalReturnAmt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Void Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVoidAmt, 2), '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($total, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Regular: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Total Sales:", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSales, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Less Vat(SC/PWD/Solo Parent/Diplomat):", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalOtherDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Senior: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSramt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Vat: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVat, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Less Regular Discounts: ", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" PWD: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalPwdamt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Net Sales: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($netvat, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("VAT Exempt: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVatex, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Solo Parent: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSoloamt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Vatable Sales (Net of Vat): ", '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($netVat, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Other Discount: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalOtherDiscount, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Zero Rated", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($zeroRated, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(" Total Discounts: ", '70', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($grandDisc, 2), '70', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("Other Discount Breakdown", '190', null, false, $border, 'B', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col("", '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $disc = $this->disc_query($config);


    for ($j = 0; $j < count($disc); $j++) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(" ", '600', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($disc[$j]['disctype'], '70', null, false, $border, '', 'L', $font, $fontsize, 'B');

      $str .= $this->reporter->col($disc[$j]['discamt'], '70', null, false, $border, '', 'R', $font, $fontsize, 'B');

      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();






    $str .= $this->reporter->endreport();
    return $str;
  }

  public function standard_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $date = date("m/d/Y g:i a");
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];


    $layoutsize = '1501';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED SALES REPORT', '1206', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($modelid == '0') {
      $str .= $this->reporter->col('MODEL:  ALL MODEL', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('MODEL:  ' . strtoupper($model), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($paymentCond == '') {
      $str .= $this->reporter->col('PAYMENT:  ALL PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PAYMENT:  ' . strtoupper($pospayment), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($doc  == '') {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ALL DOC', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ' . strtoupper($doc), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '107', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '147', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '79', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '107', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer:', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales agent', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '147', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '40', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '79', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Warehouse', '79', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SC/Pwd/solo Disc.', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc.:', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Cost', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Amt.', '79', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Doc Ref.', '79', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Promo', '79', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  public function standard_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkbarcode = $config['params']['dataparams']['reporttype'];
    $checkorder = $config['params']['dataparams']['reporttype'];
    $checkwh = $config['params']['dataparams']['whid'];
    $checkorder3 = $config['params']['dataparams']['reporttype'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }

    if ($checkbarcode == 'sdcharoe') {
      $filter   .= " and item.barcode  NOT LIKE '%$$%'";
    }

    if ($checkorder == 'wis') {
      $filter   .= " and h.ordertype  <> 3";
    }

    if ($checkwh != '0') {
      $filter   .= " and wh.wh  = $checkwh";
    }

    if ($checkorder3 == 'dsales') {
      $filter   .= " and h.ordertype  = 3";
    }
    $query = "select  station, openby, docno, Customer, agent, dateid, barcode, itemname, qty, uom, wh, otherdisc, disc, cost, amt, vat, cost * qty as totalcost, gross, ref, ispromo, void, vatex, zerorated, sramt, pwdamt, soloamt, otherdiscount,returnsales,grossSales
from (


select  cntnum.station,h.openby,h.docno as docno, client.clientname as Customer,
client.agent, head.dateid, item.barcode, item.itemname,sum(stock.isqty) as qty,stock.uom, wh.clientname as wh,
sum(stockinfo.sramt+stockinfo.pwdamt+stockinfo.soloamt) as otherdisc,
 stockinfo.discamt as disc,
stock.rrcost as cost, stock.isamt AS amt
,sum(stockinfo.vatamt) as vat, sum(stock.isqty * stock.isamt) as gross, CASE WHEN h.bref = 'V' THEN stock.ref ELSE '' END AS ref, stockinfo.ispromo,sum((stock.ext - stockinfo.lessvat + stockinfo.pwdamt + stockinfo.sramt + stockinfo.soloamt) * if(cntnum.bref='SRS' and h.bref='V',1,0)) as void, stockinfo.vatex, if(stockinfo.isdiplomat = 1, stockinfo.vatex, 0) as zerorated, stockinfo.sramt as sramt, stockinfo.pwdamt as pwdamt, stockinfo.soloamt as soloamt, sum(stockinfo.acdisc+ stockinfo.vipdisc+ stockinfo.empdisc+ stockinfo.oddisc+ stockinfo.smacdisc) as otherdiscount,sum((stock.ext - stockinfo.lessvat + stockinfo.pwdamt + stockinfo.sramt + stockinfo.soloamt) * if(cntnum.bref='SRS' and h.bref='RT',1,0)) as returnsales,SUM(stock.isqty * stock.isamt) + SUM((stock.ext - stockinfo.lessvat + stockinfo.pwdamt + stockinfo.sramt + stockinfo.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales

from lahead as head

left join lastock as stock on stock.trno = head.trno
left join cntnum on cntnum.trno = head.trno
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
join item on stock.itemid = item.itemid
left join client on head.client = client.client
left join client as wh on wh.client = head.wh
left join stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
left join frontend_ebrands  as brand on brand.brandid = item.brand

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
 and stock.void = 0  $filter

group by   cntnum.station,h.openby,h.docno, client.clientname,
client.agent, head.dateid, item.barcode, item.itemname,stock.uom, wh.clientname,stockinfo.discamt,
stock.rrcost, stock.isamt,stock.ext,stockinfo.lessvat,stockinfo.sramt,
stockinfo.pwdamt,stockinfo.soloamt, h.bref, stock.ref, stockinfo.ispromo, stock.void, stockinfo.vatex, stockinfo.isdiplomat


union all

select   cntnum.station,h.openby,h.docno as docno, client.clientname as Customer,
client.agent, head.dateid, item.barcode, item.itemname,sum(stock.isqty) as qty,stock.uom, wh.clientname as wh,
sum(hstockinfo.sramt+hstockinfo.pwdamt+hstockinfo.soloamt) as otherdisc, hstockinfo.discamt as disc,
stock.rrcost as cost,stock.isamt AS amt
,sum(hstockinfo.vatamt) as vat, sum(stock.isqty * stock.isamt) as gross, CASE WHEN h.bref = 'V' THEN stock.ref ELSE '' END AS ref, hstockinfo.ispromo,sum((stock.ext - hstockinfo.lessvat + hstockinfo.pwdamt + hstockinfo.sramt + hstockinfo.soloamt) * if(cntnum.bref='SRS' and h.bref='V',1,0)) as void, hstockinfo.vatex,if(hstockinfo.isdiplomat = 1, hstockinfo.vatex, 0) as zerorated, hstockinfo.sramt as sramt, hstockinfo.pwdamt as pwdamt, hstockinfo.soloamt as soloamt, sum(hstockinfo.acdisc + hstockinfo.vipdisc + hstockinfo.empdisc + hstockinfo.oddisc + hstockinfo.smacdisc)as otherdiscount,sum((stock.ext - hstockinfo.lessvat + hstockinfo.pwdamt + hstockinfo.sramt + hstockinfo.soloamt) * if(cntnum.bref='SRS' and h.bref='RT',1,0)) as returnsales, SUM(stock.isqty * stock.isamt) + SUM((stock.ext - hstockinfo.lessvat + hstockinfo.pwdamt + hstockinfo.sramt + hstockinfo.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales
from glhead as head

left join glstock as stock on stock.trno = head.trno
left join cntnum on cntnum.trno = head.trno
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
join item on stock.itemid = item.itemid
left join client on head.clientid = client.clientid
left join client as wh on wh.clientid = head.whid
left join hstockinfo on hstockinfo.trno = stock.trno and hstockinfo.line = stock.line
left join frontend_ebrands  as brand on brand.brandid = item.brand

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS') 
 and stock.void = 0 $filter

group by   cntnum.station,h.openby,h.docno, client.clientname,
client.agent, head.dateid, item.barcode, item.itemname,stock.uom, wh.clientname,hstockinfo.discamt,
stock.rrcost, stock.isamt,stock.ext,hstockinfo.lessvat,hstockinfo.sramt,
hstockinfo.pwdamt,hstockinfo.soloamt, h.bref, stock.ref,  hstockinfo.ispromo, stock.void, hstockinfo.vatex, hstockinfo.isdiplomat



) as t
 order by openby, docno, station,  dateid
 ";


    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function disc_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkbarcode = $config['params']['dataparams']['reporttype'];
    $checkorder = $config['params']['dataparams']['reporttype'];
    $checkwh = $config['params']['dataparams']['whid'];
    $checkorder3 = $config['params']['dataparams']['reporttype'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }

    if ($checkbarcode == 'sdcharoe') {
      $filter   .= " and item.barcode  NOT LIKE '%$$%'";
    }

    if ($checkorder == 'wis') {
      $filter   .= " and h.ordertype  <> 3";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh  = $checkwh";
    }
    if ($checkorder3 == 'dsales') {
      $filter   .= " and h.ordertype  = 3";
    }

    $query = "select dateid, disctype, sum(discamt) as discamt from (
select head.dateid,'ATHLETES/COACHES' as disctype,sum(stockinfo.acdisc) as discamt from lahead as head

left join lastock as stock on stock.trno=head.trno
left join stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
left join cntnum on cntnum.trno = head.trno
join item on stock.itemid = item.itemid
left join client on head.client = client.client
left join client as wh on wh.client = head.wh
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join frontend_ebrands  as brand on brand.brandid = item.brand

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS')
and stock.void = 0 $filter
group by 'ATHLETES/COACHES', head.dateid
union all
select head.dateid,'MEDAL OF VALOR' as disctype,sum(stockinfo.valoramt) as discamt from lahead as head
left join lastock as stock on stock.trno=head.trno
left join stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
left join cntnum on cntnum.trno = head.trno
join item on stock.itemid = item.itemid
left join client on head.client = client.client
left join client as wh on wh.client = head.wh
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join frontend_ebrands  as brand on brand.brandid = item.brand
where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS')
and stock.void = 0 $filter
group by 'MEDAL OF VALOR', head.dateid
union all
select head.dateid,'ATHLETES/COACHES' as disctype,sum(hstockinfo.acdisc) as discamt from glhead as head

left join glstock as stock on stock.trno=head.trno
left join hstockinfo on hstockinfo.trno = stock.trno and hstockinfo.line = stock.line
left join cntnum on cntnum.trno = head.trno
join item on stock.itemid = item.itemid
left join client on head.clientid = client.clientid
left join client as wh on wh.clientid = head.whid
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join frontend_ebrands  as brand on brand.brandid = item.brand

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS')
and stock.void = 0 $filter
group by 'ATHLETES/COACHES', head.dateid
union all
select head.dateid,'MEDAL OF VALOR' as disctype,sum(hstockinfo.valoramt) as discamt from glhead as head
left join glstock as stock on stock.trno=head.trno
left join hstockinfo on hstockinfo.trno = stock.trno and hstockinfo.line = stock.line
left join cntnum on cntnum.trno = head.trno
join item on stock.itemid = item.itemid
left join client on head.clientid = client.clientid
left join client as wh on wh.clientid = head.whid
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join frontend_ebrands  as brand on brand.brandid = item.brand
where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS')
and stock.void = 0 $filter
group by 'MEDAL OF VALOR', head.dateid
) as disc
group by  dateid, disctype
order by disctype
";


    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function detailed_sales_collection($config)
  {
    $layoutsize = '1600';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->collection_query($config);

    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';





    $prevDocno = '';



    $cashierTotalExt  = 0;


    $docnoTotalQty = 0;
    $qtyTotal = 0;
    $docnoTotalAmt = 0;
    $docnoTotalDisc = 0;
    $docnoTotalExt = 0;

    $currentStationTotalAmt = 0;

    $grossSales = 0;
    $voidSales = 0;
    $totalQty = 0;
    $vatExempt = 0;
    $vatSales = 0;
    $regDisc = 0;
    $srDisc = 0;
    $pwdDisc = 0;
    $soloDisc = 0;
    $lessvat = 0;
    $totalSales = 0;
    $totalVat = 0;
    $netSales = 0;


    $cashierDineInCount = 0;
    $cashierTakeOutCount = 0;
    $cashierDeliveryCount = 0;


    $groupedData = [];
    foreach ($data as $row) {
      $cashierName = strtoupper($row['openby']);
      if (!isset($groupedData[$cashierName])) {
        $groupedData[$cashierName] = [];
      }
      $groupedData[$cashierName][] = $row;
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->collection_header($config);


    $currentStation = '';
    $displayedStation = false;


    $displayedDocnos = [];


    $displayedCustomersByDocno = [];

    foreach ($groupedData as $cashierName => $cashierRows) {

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("Cashier: " . $cashierName, null, null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $prevDocno = '';
      $cashierTotalExt = 0;


      $cashierDineInCount = 0;
      $cashierTakeOutCount = 0;
      $cashierDeliveryCount = 0;


      $cashierCountedDocnos = [
        1 => [],
        2 => [],
        3 => []
      ];

      for ($i = 0; $i < count($cashierRows); $i++) {
        $currStation = $cashierRows[$i]['station'];
        $currDocno = $cashierRows[$i]['docno'];
        $currCustomer = $cashierRows[$i]['customer'];

        if ($currStation !== $currentStation) {

          if ($currentStation !== '' && $currentStationTotalAmt > 0) {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("Station Subtotal for " . $currentStation . ": " . number_format($currentStationTotalAmt, 2), null, null, false, $border, '', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $currentStationTotalAmt = 0;
          }


          $currentStation = $currStation;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("Station: " . $currentStation, null, null, false, $border, '', 'L', $font, $fontsize, 'B');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $displayedStation = true;
        }

        if ($currDocno !== $prevDocno && $prevDocno !== '') {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
          $str .= $this->reporter->col('SubTotal', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B');
          $str .= $this->reporter->col(number_format($docnoTotalQty, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', 'white');
          $str .= $this->reporter->col(number_format($docnoTotalAmt, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
          $str .= $this->reporter->col(number_format($docnoTotalDisc, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
          $str .= $this->reporter->col(number_format($docnoTotalExt, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $docnoTotalQty = $docnoTotalAmt = $docnoTotalDisc = $docnoTotalExt = 0;
        }

        if ($currDocno !== $prevDocno) {
          $prevDocno = $currDocno;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $displayDocno = '';
        if (!in_array($currDocno, $displayedDocnos)) {
          $displayDocno = $currDocno;
          $displayedDocnos[] = $currDocno;
        }


        $displayCustomer = '';
        if (!isset($displayedCustomersByDocno[$currDocno])) {
          $displayCustomer = $currCustomer;
          $displayedCustomersByDocno[$currDocno] = $currCustomer;
        }

        $str .= $this->reporter->col($displayDocno, '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($displayCustomer, '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($cashierRows[$i]['dateid'], '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($cashierRows[$i]['barcode'], '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($cashierRows[$i]['itemname'], '170', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['qty']) && $cashierRows[$i]['qty'] != 0) ? number_format($cashierRows[$i]['qty'], 2) : '-', '30', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['amt']) && $cashierRows[$i]['amt'] != 0) ? number_format($cashierRows[$i]['amt'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['lessvat']) && $cashierRows[$i]['lessvat'] != 0) ? number_format($cashierRows[$i]['lessvat'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['sramt']) && $cashierRows[$i]['sramt'] != 0) ? number_format($cashierRows[$i]['sramt'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['discamt']) && $cashierRows[$i]['discamt'] != 0) ? number_format($cashierRows[$i]['discamt'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['empdisc']) && $cashierRows[$i]['empdisc'] != 0) ? number_format($cashierRows[$i]['empdisc'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['pwddisc']) && $cashierRows[$i]['pwddisc'] != 0) ? number_format($cashierRows[$i]['pwddisc'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((isset($cashierRows[$i]['ext']) && $cashierRows[$i]['ext'] != 0) ? number_format($cashierRows[$i]['ext'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
        $str .= $this->reporter->col((!empty($cashierRows[$i]['ordertype']) && $cashierRows[$i]['ispromo'] != 0) ? number_format($cashierRows[$i]['ispromo'], 2) : '', '100', null, false, $border, '', 'C', $font, $fontsize);
        $str .= $this->reporter->col($cashierRows[$i]['yourref'], '100', null, false, $border, '', 'C', $font, $fontsize);
        $str .= $this->reporter->col((!empty($cashierRows[$i]['ispromo']) && $cashierRows[$i]['ispromo'] != 0) ? number_format($cashierRows[$i]['ispromo'], 2) : '', '100', null, false, $border, '', 'C', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $docnoTotalQty  += isset($cashierRows[$i]['qty']) ? $cashierRows[$i]['qty'] : 0;
        $docnoTotalAmt  += isset($cashierRows[$i]['amt']) ? $cashierRows[$i]['amt'] : 0;
        $docnoTotalDisc += isset($cashierRows[$i]['discamt']) ? $cashierRows[$i]['discamt'] : 0;
        $docnoTotalExt  += isset($cashierRows[$i]['ext']) ? $cashierRows[$i]['ext'] : 0;

        $cashierTotalExt  += isset($cashierRows[$i]['ext']) ? $cashierRows[$i]['ext'] : 0;


        $currentStationTotalAmt  += isset($cashierRows[$i]['gross']) ? $cashierRows[$i]['gross'] : 0;
        $qtyTotal += isset($cashierRows[$i]['qty']) ? $cashierRows[$i]['qty'] : 0;


        $totalQty  += isset($cashierRows[$i]['qty']) ? $cashierRows[$i]['qty'] : 0;
        $vatExempt  += isset($cashierRows[$i]['vatex']) ? $cashierRows[$i]['vatex'] : 0;
        $regDisc += isset($cashierRows[$i]['discamt']) ? $cashierRows[$i]['discamt'] : 0;
        $srDisc += isset($cashierRows[$i]['sramt']) ? $cashierRows[$i]['sramt'] : 0;
        $pwdDisc += isset($cashierRows[$i]['pwdamt']) ? $cashierRows[$i]['pwdamt'] : 0;
        $soloDisc += isset($cashierRows[$i]['soloamt']) ? $cashierRows[$i]['soloamt'] : 0;
        $lessvat  += isset($cashierRows[$i]['otherdiscount']) ? $cashierRows[$i]['otherdiscount'] : 0;
        $totalVat += isset($cashierRows[$i]['vat']) ? $cashierRows[$i]['vat'] : 0;
        $voidSales += isset($cashierRows[$i]['void']) ? $cashierRows[$i]['void'] : 0;

        $orderType = isset($cashierRows[$i]['ordertype']) ? $cashierRows[$i]['ordertype'] : 0;


        if ($currDocno && $orderType > 0) {
          if ($orderType == 1 && !in_array($currDocno, $cashierCountedDocnos[1])) {
            $cashierCountedDocnos[1][] = $currDocno;
            $cashierDineInCount++;
          } elseif ($orderType == 2 && !in_array($currDocno, $cashierCountedDocnos[2])) {
            $cashierCountedDocnos[2][] = $currDocno;
            $cashierTakeOutCount++;
          } elseif ($orderType == 3 && !in_array($currDocno, $cashierCountedDocnos[3])) {
            $cashierCountedDocnos[3][] = $currDocno;
            $cashierDeliveryCount++;
          }
        }
      }

      if ($prevDocno !== '') {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('SubTotal', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($docnoTotalQty, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', 'white');
        $str .= $this->reporter->col(number_format($docnoTotalAmt, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
        $str .= $this->reporter->col(number_format($docnoTotalDisc, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize);
        $str .= $this->reporter->col(number_format($docnoTotalExt, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $docnoTotalQty = $docnoTotalAmt = $docnoTotalDisc = $docnoTotalExt = 0;
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('(' . $cashierName . ') Sub Total', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In: ' . $cashierDineInCount, '100', null, false, $border, 'T', 'R', $font, $fontsize);
      $str .= $this->reporter->col('Take-Out: ' . $cashierTakeOutCount, '100', null, false, $border, 'T', 'R', $font, $fontsize);
      $str .= $this->reporter->col(number_format($cashierTotalExt, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize);

      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

      $cashierTotalExt = 0;
      $cashierDineInCount = 0;
      $cashierTakeOutCount = 0;
      $cashierDeliveryCount = 0;
      $cashierCountedDocnos = [1 => [], 2 => [], 3 => []];
      $docnoTotalQty = $docnoTotalAmt = $docnoTotalDisc = $docnoTotalExt = 0;
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('(' . $currStation . ') Sub Total', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Dine-In: ' . $cashierDineInCount, '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Take-Out: ' . $cashierTakeOutCount, '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($currentStationTotalAmt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $grossSales = $currentStationTotalAmt;
    $totalSales = $currentStationTotalAmt;
    $totalDiscount = $regDisc + $srDisc + $pwdDisc + $soloDisc + $lessvat;
    $vatSales = $totalSales - $totalVat - $vatExempt;
    $netSales = $totalSales - $totalVat;



    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Gross Sales: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($grossSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Regular: ', '', null, false, '100', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($regDisc, 2), '', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Total Sales: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Void Sales: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($voidSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Senior: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($srDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Vat: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalVat, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Total Quantity: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($qtyTotal, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('PWD: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($pwdDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Net Sales: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($netSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Vat Exempt: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($vatExempt, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Solo Parent: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($soloDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Vatable Sales: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($vatSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Other Discounts: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($lessvat, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('Total Discounts: ', '100', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalDiscount, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function collection_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $date = date("m/d/Y g:i a");
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];



    $layoutsize = '1600';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED COLLECTION  SALES REPORT', '1206', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($modelid == '0') {
      $str .= $this->reporter->col('MODEL:  ALL MODEL', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('MODEL:  ' . strtoupper($model), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($paymentCond == '') {
      $str .= $this->reporter->col('PAYMENT:  ALL PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PAYMENT:  ' . strtoupper($pospayment), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    if ($doc  == '') {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ALL DOC', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ' . strtoupper($doc), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '170', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(' ', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(' ', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(' ', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(' ', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer:', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '170', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '30', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less SVat', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SDisc', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Emp Disc.:', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PWD Disc', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Order Type', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payment Type', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Promo', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  public function collection_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }

    if ($checkwh != '0') {
      $filter   .= " and wh.wh = '$checkwh'";
    }

    $query = "select station, openby, docno, customer, dateid, barcode,itemname, qty, amt, lessvat,sramt,discamt,
  empdisc,pwdamt,soloamt,ext,ordertype,yourref,ispromo,vatex,otherdiscount,vat, gross,void

  from (

    select cntnum.station, h.openby, h.docno, client.clientname as customer, h.dateid,item.barcode,item.itemname,
    sum(s.isqty) AS qty, s.amt as amt,
    si.lessvat, si.sramt, si.discamt,si.empdisc,si.pwdamt,si.soloamt,
    round(sum((s.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext,si.ordertype, si.ispromo, s.void as isvoid,
    if(si.vatex<>0, (si.vatex+si.sramt+si.pwdamt+si.soloamt), si.vatex) as vatex,
    sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, sum(si.vatamt) as vat, sum(s.isqty * s.isamt) as gross,
    sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void, IFNULL((SELECT GROUP_CONCAT(IF(h.yourref='others',SUBSTRING_INDEX(h.checktype,'~',1),h.yourref) SEPARATOR ',\n') FROM head AS h WHERE h.webtrno=head.trno AND h.docno=s.ref),'') AS yourref
    from lahead as head


    left join lastock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join client on head.client = client.client
    join item on item.itemid = s.itemid
    left join stockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join frontend_ebrands  as brand on brand.brandid = item.brand  
    left join client as wh on wh.client = head.wh

     where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0 $filter 
    group by cntnum.station, h.openby, h.docno, client.clientname, h.dateid,item.barcode,item.itemname, s.amt,si.ordertype,
    si.lessvat, si.sramt, si.discamt,si.empdisc,si.pwdamt,h.yourref, si.ispromo, s.void, si.vatex,si.soloamt, head.trno, s.ref

    union all

        select cntnum.station, h.openby, h.docno, client.clientname as customer, h.dateid,item.barcode,item.itemname,
    sum(s.isqty) AS qty, s.amt as amt,
    si.lessvat, si.sramt, si.discamt,si.empdisc,si.pwdamt,si.soloamt,
    round(sum((s.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext,si.ordertype,si.ispromo, s.void as isvoid,
    if(si.vatex<>0, (si.vatex+si.sramt+si.pwdamt+si.soloamt), si.vatex) as vatex,
    sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, sum(si.vatamt) as vat, sum(s.isqty * s.isamt) as gross,
    sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void, IFNULL((SELECT GROUP_CONCAT(IF(h.yourref='others',SUBSTRING_INDEX(h.checktype,'~',1),h.yourref) SEPARATOR ',\n') FROM head AS h WHERE h.webtrno=head.trno AND h.docno=s.ref),'') AS yourref
    from glhead as head


    left join glstock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join client on head.clientid = client.clientid
    join item on item.itemid = s.itemid
    left join hstockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join frontend_ebrands  as brand on brand.brandid = item.brand
    left join client as wh on wh.clientid = head.whid

     where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0 $filter 
    group by cntnum.station, h.openby, h.docno, client.clientname, h.dateid,item.barcode,item.itemname, s.amt,si.ordertype,
    si.lessvat, si.sramt, si.discamt,si.empdisc,si.pwdamt,h.yourref, si.ispromo, s.void, si.vatex,si.soloamt, head.trno, s.ref


    ) as detailedsales

     ";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function detailed_sales_netsales($config)
  {

    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->netsales_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->netsales_header($config);


    $prevGroup = '';
    $prevDocno = '';
    $prevStation = '';

    $groupQty = 0;
    $groupAmt = 0;
    $subTotalSales = 0;

    $stationTotalSales = 0;
    $stationTotalVat = 0;
    $stationTotalNetSales = 0;

    $cashierTotalSales = 0;
    $cashierTotalVat = 0;
    $cashierTotalNetSales = 0;

    $grossSales = 0;
    $voidSales  = 0;
    $totalQty = 0;
    $vatExempt = 0;
    $vatSales = 0;
    $regDisc = 0;
    $srDisc = 0;
    $pwdDisc = 0;
    $soloDisc = 0;
    $otherDisc = 0;
    $totalDiscount = 0;
    $totalSales = 0;
    $totalVat = 0;
    $totalNetSales = 0;

    $cashierDineInCount = 0;
    $cashierTakeOutCount = 0;
    $stationDineInCount = 0;
    $stationTakeOutCount = 0;


    for ($i = 0; $i < count($data); $i++) {
      $currGroup = $data[$i]['openby'];
      $currStation = $data[$i]['station'];
      $currGroupKey = empty($currGroup) ? ' ' : $currGroup;
      $currDocno = $data[$i]['docno'];
      $showDocno = ($currDocno !== $prevDocno);


      if ($prevDocno !== '' && $currDocno !== $prevDocno) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Subtotal:', '280', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subTotalSales != 0) ? number_format($subTotalSales, 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $subTotalSales = 0;
      }


      if ($prevStation !== '' && $currStation !== $prevStation) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col("Station ({$prevStation}) Sub Total:", '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Sales: ' . (($stationTotalSales != 0) ? number_format($stationTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-In: ' . $stationDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Take-Out: ' . $stationTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total VAT: ' . (($stationTotalVat != 0) ? number_format($stationTotalVat, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Net Sales: ' . (($stationTotalNetSales != 0) ? number_format($stationTotalNetSales, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $stationTotalSales = $stationTotalVat = $stationTotalNetSales = 0;
      }

      if ($currGroupKey !== $prevGroup && $prevGroup !== '') {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(' (' . $prevGroup . ') Sub Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-In : ' . $cashierDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Take-Out : ' . $cashierTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Sales: ' . (($cashierTotalSales != 0) ? number_format($cashierTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total VAT: ' . (($cashierTotalVat != 0) ? number_format($cashierTotalVat, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Net Sales: ' . (($cashierTotalNetSales != 0) ? number_format($cashierTotalNetSales, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $cashierTotalSales = $cashierTotalVat = $cashierTotalNetSales = 0;
      }


      if ($currGroupKey !== $prevGroup || $currStation !== $prevStation) {
        $displayStation = ($currStation !== $prevStation) ? "  Station: {$currStation}" : '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Cashier: {$currGroupKey}{$displayStation}", null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endtable();
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($showDocno ? $currDocno : '', '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($showDocno ? $data[$i]['customer'] : '', '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($showDocno ? date('Y-m-d', strtotime($data[$i]['dateid'])) : '', '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['qty'] != 0) ? number_format($data[$i]['qty'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['amt'] != 0) ? number_format($data[$i]['amt'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['disc'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['sramt'] != 0) ? number_format($data[$i]['sramt'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['empdisc'] != 0) ? number_format($data[$i]['empdisc'], 2) : '-', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['totalsales'] != 0) ? number_format($data[$i]['totalsales'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['vat'] != 0) ? number_format($data[$i]['vat'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['net'] != 0) ? number_format($data[$i]['net'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['waiter'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      if ($data[$i]['ordertype'] == 1) {
        $cashierDineInCount++;
        $stationDineInCount++;
      } elseif ($data[$i]['ordertype'] == 2) {
        $cashierTakeOutCount++;
        $stationTakeOutCount++;
      }




      $groupQty += (float)$data[$i]['qty'];
      $groupAmt += (float)$data[$i]['amt'];
      $subTotalSales += (float)$data[$i]['totalsales'];
      $stationTotalSales += (float)$data[$i]['totalsales'];
      $stationTotalVat += (float)$data[$i]['vat'];
      $stationTotalNetSales += (float)$data[$i]['net'];
      $cashierTotalSales += (float)$data[$i]['totalsales'];
      $cashierTotalVat += (float)$data[$i]['vat'];
      $cashierTotalNetSales += (float)$data[$i]['net'];


      $grossSales += (float)$data[$i]['totalsales'];
      $voidSales  += (float)$data[$i]['void'];
      $totalQty += (float)$data[$i]['qty'];
      $vatExempt += (float)$data[$i]['vatex'];
      $regDisc += (float)$data[$i]['disc'];
      $srDisc += (float)$data[$i]['sramt'];
      $pwdDisc += (float)$data[$i]['pwdamt'];
      $soloDisc += (float)$data[$i]['soloamt'];
      $otherDisc += (float)$data[$i]['otherdiscount'];
      $totalDiscount += (float)$data[$i]['disc'] + (float)$data[$i]['sramt'] + (float)$data[$i]['pwdamt'] + (float)$data[$i]['soloamt'] + (float)$data[$i]['otherdiscount'];
      $totalSales += (float)$data[$i]['totalsales'];
      $totalVat += (float)$data[$i]['vat'];
      $totalNetSales += (float)$data[$i]['net'];


      $vatSales =  $totalNetSales - $vatExempt;


      $prevDocno = $currDocno;
      $prevGroup = $currGroupKey;
      $prevStation = $currStation;
    }


    if ($prevDocno !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Subtotal:', '280', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subTotalSales != 0) ? number_format($subTotalSales, 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    if ($prevGroup !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(' (' . $prevGroup . ') Sub Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In : ' . $cashierDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Take-Out : ' . $cashierTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Sales: ' . (($cashierTotalSales != 0) ? number_format($cashierTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Total VAT: ' . (($cashierTotalVat != 0) ? number_format($cashierTotalVat, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Net Sales: ' . (($cashierTotalNetSales != 0) ? number_format($cashierTotalNetSales, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevStation !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("({$prevStation}) Sub Total:", '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In: ' . $stationDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Take-Out: ' . $stationTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Sales: ' . (($stationTotalSales != 0) ? number_format($stationTotalSales, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Total VAT: ' . (($stationTotalVat != 0) ? number_format($stationTotalVat, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Net Sales: ' . (($stationTotalNetSales != 0) ? number_format($stationTotalNetSales, 2) : '-'), '200', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Gross Sales:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grossSales, 2), '100', null, false, $border, 'T',  'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Regular:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($regDisc, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Sales:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalSales, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Void Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($voidSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Senior:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($srDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('VAT:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalVat, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Quantity:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalQty, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('PWD:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($pwdDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Net Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalNetSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vat Exempt:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatExempt, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Solo Parent:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($soloDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vatable Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Other Discounts:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($otherDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Discounts', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalDiscount, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();




    $str .= $this->reporter->endreport();
    return $str;
  }

  public function netsales_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $date = date("m/d/Y g:i a");
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];


    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED NET SALES REPORT', '1500', null, false, $border, '', 'C', $font, '12', 'B', 'blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1500', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($modelid == '0') {
      $str .= $this->reporter->col('MODEL:  ALL MODEL', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('MODEL:  ' . strtoupper($model), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($paymentCond == '') {
      $str .= $this->reporter->col('PAYMENT:  ALL PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PAYMENT:  ' . strtoupper($pospayment), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($doc  == '') {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ALL DOC', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ' . strtoupper($doc), '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '270', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '270', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SDisc.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Emp. Disc.', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Net', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Waiter', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function netsales_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = '$checkwh'";
    }

    $query = "    select cntnum.station, h.openby, h.docno,client.clientname as customer,h.dateid,item.barcode,item.itemname,sum(s.isqty) as qty, s.isamt as amt,
    si.discamt as disc, si.sramt, si.empdisc, sum(s.isqty * s.isamt) as totalsales, sum(si.vatamt) as vat,
   SUM((isqty * isamt) - si.vatamt) AS net, si.ordertype, u.username as waiter, sum(s.isqty * s.isamt) + SUM((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales, sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void,
    si.pwdamt, si.soloamt,sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, si.vatex


    from lahead as head

    left join lastock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join client on head.client = client.client
    join item on item.itemid = s.itemid
    left join stockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join useraccess as u on u.userid = h.userid
    left join frontend_ebrands  as brand on brand.brandid = item.brand
    left join client as wh on wh.client = head.wh

     where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0 $filter
     group by cntnum.station, h.openby, h.docno,client.clientname,h.dateid,item.barcode,item.itemname, s.isamt,si.discamt, si.sramt, si.empdisc, si.ordertype, u.username,si.pwdamt, si.soloamt, si.vatex

    union all


   select cntnum.station, h.openby, h.docno,client.clientname as customer,h.dateid,item.barcode,item.itemname,sum(s.isqty) as qty, s.isamt as amt,
    si.discamt as disc, si.sramt, si.empdisc, sum(s.isqty * s.isamt) as totalsales, sum(si.vatamt) as vat,
   SUM((isqty * isamt) - si.vatamt) AS net, si.ordertype, u.username as waiter, sum(s.isqty * s.isamt) + SUM((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales, sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void,
    si.pwdamt, si.soloamt,sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, si.vatex


    from glhead as head

    left join glstock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join client on head.clientid = client.clientid
    join item on item.itemid = s.itemid
    left join hstockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join useraccess as u on u.userid = h.userid
    left join frontend_ebrands  as brand on brand.brandid = item.brand
    left join client as wh on wh.clientid = head.whid

     where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0 $filter
     group by cntnum.station, h.openby, h.docno,client.clientname,h.dateid,item.barcode,item.itemname, s.isamt,si.discamt, si.sramt, si.empdisc, si.ordertype, u.username,si.pwdamt, si.soloamt, si.vatex

     ";



    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function detailed_sales_crewsales($config)
  {

    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->crewsales_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->crewsales_header($config);


    $prevGroup = '';
    $prevDocno = '';
    $prevStation = '';

    $groupQty = 0;
    $groupAmt = 0;
    $subSDisc = 0;
    $subDiscAmt = 0;
    $subPwdAmt = 0;
    $subEmpDisc = 0;
    $subTotalSales = 0;

    $stationTotalSales = 0;
    $stationTotalVat = 0;
    $stationTotalNetSales = 0;

    $cashierTotalSales = 0;
    $cashierTotalVat = 0;
    $cashierTotalNetSales = 0;

    $grossSales = 0;
    $voidSales  = 0;
    $totalQty = 0;
    $vatExempt = 0;
    $vatSales = 0;
    $regDisc = 0;
    $srDisc = 0;
    $pwdDisc = 0;
    $soloDisc = 0;
    $otherDisc = 0;
    $totalDiscount = 0;
    $totalSales = 0;
    $totalVat = 0;
    $totalNetSales = 0;

    $cashierDineInCount = 0;
    $cashierTakeOutCount = 0;
    $stationDineInCount = 0;
    $stationTakeOutCount = 0;


    for ($i = 0; $i < count($data); $i++) {
      $currGroup = $data[$i]['waiter'];
      $currStation = $data[$i]['station'];
      $currGroupKey = empty($currGroup) ? ' ' : $currGroup;
      $currDocno = $data[$i]['docno'];
      $showDocno = ($currDocno !== $prevDocno);

      if ($prevDocno !== '' && $currDocno !== $prevDocno) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '122', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '122', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Subtotal', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($groupQty != 0) ? number_format($groupQty, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($groupAmt != 0) ? number_format($groupAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($subSDisc != 0) ? number_format($subSDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subDiscAmt != 0) ? number_format($subDiscAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subPwdAmt != 0) ? number_format($subPwdAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subEmpDisc != 0) ? number_format($subEmpDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subTotalSales != 0) ? number_format($subTotalSales, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $groupQty = 0;
        $groupAmt = 0;
        $subSDisc = 0;
        $subDiscAmt = 0;
        $subPwdAmt = 0;
        $subEmpDisc = 0;
        $subTotalSales = 0;
      }


      if ($prevStation !== '' && $currStation !== $prevStation) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col("Station ({$prevStation}) Sub Total:", '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-In: ' . $stationDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Take-Out: ' . $stationTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Sales: ' . (($stationTotalSales != 0) ? number_format($stationTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $stationTotalSales = $stationTotalVat = $stationTotalNetSales = 0;
      }


      if ($currGroupKey !== $prevGroup && $prevGroup !== '') {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(' (' . $prevGroup . ') Sub Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-In : ' . $cashierDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Take-Out : ' . $cashierTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Sales: ' . (($cashierTotalSales != 0) ? number_format($cashierTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $cashierTotalSales = $cashierTotalVat = $cashierTotalNetSales = 0;
      }


      if ($currGroupKey !== $prevGroup || $currStation !== $prevStation) {
        $displayStation = ($currStation !== $prevStation) ? "  {$currStation}" : '';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Station: {$displayStation}", null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Waiter: {$currGroupKey}", null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($showDocno ? $currDocno : '', '120', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($showDocno ? $data[$i]['customer'] : '', '122', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($showDocno ? date('Y-m-d', strtotime($data[$i]['dateid'])) : '', '106', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['barcode'], '120', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['itemname'], '120', null, false, '$border', '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['qty'] != 0) ? number_format($data[$i]['qty'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['amt'] != 0) ? number_format($data[$i]['amt'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['lessvat'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['sramt'] != 0) ? number_format($data[$i]['sramt'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['disc'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['pwdamt'] != 0) ? number_format($data[$i]['sramt'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['empdisc'] != 0) ? number_format($data[$i]['empdisc'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['totalsales'] != 0) ? number_format($data[$i]['totalsales'], 2) : '-', '70', null, false, '$border', '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['cost'] != 0) ? number_format($data[$i]['vat'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['totalcost'] != 0) ? number_format($data[$i]['net'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col((isset($data[$i]['ordertype']) && $data[$i]['ordertype'] != 0 ? $data[$i]['ordertype'] : ''), '106', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['openby'], '106', null, false, $border, '', 'C', $font, $fontsize, '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      if ($data[$i]['ordertype'] == 1) {
        $cashierDineInCount++;
        $stationDineInCount++;
      } elseif ($data[$i]['ordertype'] == 2) {
        $cashierTakeOutCount++;
        $stationTakeOutCount++;
      }



      $groupQty += (float)$data[$i]['qty'];
      $groupAmt += (float)$data[$i]['amt'];
      $subSDisc += (float)$data[$i]['sramt'];
      $subDiscAmt += (float)$data[$i]['disc'];
      $subPwdAmt += (float)$data[$i]['pwdamt'];
      $subEmpDisc += (float)$data[$i]['empdisc'];
      $subTotalSales += (float)$data[$i]['totalsales'];
      $stationTotalSales += (float)$data[$i]['totalsales'];
      $stationTotalVat += (float)$data[$i]['vat'];
      $stationTotalNetSales += (float)$data[$i]['net'];
      $cashierTotalSales += (float)$data[$i]['totalsales'];
      $cashierTotalVat += (float)$data[$i]['vat'];
      $cashierTotalNetSales += (float)$data[$i]['net'];


      $grossSales += (float)$data[$i]['totalsales'];
      $voidSales  += (float)$data[$i]['void'];
      $totalQty += (float)$data[$i]['qty'];
      $vatExempt += (float)$data[$i]['vatex'];
      $regDisc += (float)$data[$i]['disc'];
      $srDisc += (float)$data[$i]['sramt'];
      $pwdDisc += (float)$data[$i]['pwdamt'];
      $soloDisc += (float)$data[$i]['soloamt'];
      $otherDisc += (float)$data[$i]['otherdiscount'];
      $totalDiscount += (float)$data[$i]['disc'] + (float)$data[$i]['sramt'] + (float)$data[$i]['pwdamt'] + (float)$data[$i]['soloamt'] + (float)$data[$i]['otherdiscount'];
      $totalSales += (float)$data[$i]['totalsales'];
      $totalVat += (float)$data[$i]['vat'];
      $totalNetSales += (float)$data[$i]['net'];


      $vatSales =  $totalNetSales - $vatExempt;


      $prevDocno = $currDocno;
      $prevGroup = $currGroupKey;
      $prevStation = $currStation;
    }


    if ($prevDocno !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '122', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '122', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Subtotal', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(($groupQty != 0) ? number_format($groupQty, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($groupAmt != 0) ? number_format($groupAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(($subSDisc != 0) ? number_format($subSDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subDiscAmt != 0) ? number_format($subDiscAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subPwdAmt != 0) ? number_format($subPwdAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subEmpDisc != 0) ? number_format($subEmpDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subTotalSales != 0) ? number_format($subTotalSales, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $groupQty = 0;
      $groupAmt = 0;
      $subSDisc = 0;
      $subDiscAmt = 0;
      $subPwdAmt = 0;
      $subEmpDisc = 0;
      $subTotalSales = 0;
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    if ($prevGroup !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(' (' . $prevGroup . ') Sub Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In : ' . $cashierDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Take-Out : ' . $cashierTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Sales: ' . (($cashierTotalSales != 0) ? number_format($cashierTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevStation !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("({$prevStation}) Sub Total:", '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In: ' . $stationDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Take-Out: ' . $stationTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Sales: ' . (($stationTotalSales != 0) ? number_format($stationTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Gross Sales:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grossSales, 2), '100', null, false, $border, 'T',  'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Regular:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($regDisc, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Sales:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalSales, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Void Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($voidSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Senior:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($srDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('VAT:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalVat, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Quantity:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalQty, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('PWD:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($pwdDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Net Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalNetSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vat Exempt:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatExempt, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Solo Parent:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($soloDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vatable Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Other Discounts:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($otherDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Discounts', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalDiscount, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();




    $str .= $this->reporter->endreport();
    return $str;
  }

  public function crewsales_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $date = date("m/d/Y g:i a");
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];


    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CREW DETAILES SALES REPORT', '1500', null, false, $border, '', 'C', $font, '12', 'B', 'blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1500', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($modelid == '0') {
      $str .= $this->reporter->col('MODEL:  ALL MODEL', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('MODEL:  ' . strtoupper($model), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($paymentCond == '') {
      $str .= $this->reporter->col('PAYMENT:  ALL PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PAYMENT:  ' . strtoupper($pospayment), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    if ($doc  == '') {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ALL DOC', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ' . strtoupper($doc), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '122', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '106', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '106', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '106', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '122', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '106', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less SVat.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SDisc.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PwdDisc', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Emp. Disc.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T. Sales', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T. Cost', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('O. Type', '106', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cashier', '106', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function crewsales_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = '$checkwh'";
    }


    $query = "
select cntnum.station,  h.docno, client.clientname as customer, h.dateid, item.barcode, item.itemname,
s.isqty as qty, s.isamt as amt, si.lessvat, si.sramt, si.discamt as disc,  si.empdisc,sum(s.isqty * s.isamt) as totalsales,
s.rrcost as cost,sum(s.isqty * s.rrcost) as totalcost, h.openby,sum(si.vatamt) as vat,
   SUM((isqty * isamt) - si.vatamt) AS net, si.ordertype, u.username as waiter, sum(s.isqty * s.isamt) + SUM((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales, sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void,
    si.pwdamt, si.soloamt,sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, si.vatex

from lahead as head


    left join lastock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join useraccess as u on u.userid = h.userid
    left join client on head.client = client.client
    join item on item.itemid = s.itemid
    left join stockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join client as wh on wh.client = head.wh

where date(h.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0 $filter

group by cntnum.station, h.docno, client.clientname , h.dateid, item.barcode, item.itemname,
s.isqty , s.isamt , si.lessvat, si.sramt, si.discamt, si.pwdamt, si.empdisc, s.rrcost, si.ordertype, h.openby, u.username,si.soloamt,si.vatex

union all




select cntnum.station,  h.docno, client.clientname as customer, h.dateid, item.barcode, item.itemname,
s.isqty as qty, s.isamt as amt, si.lessvat, si.sramt, si.discamt as disc,  si.empdisc,sum(s.isqty * s.isamt) as totalsales,
s.rrcost as cost,sum(s.isqty * s.rrcost) as totalcost, h.openby,sum(si.vatamt) as vat,
   SUM((isqty * isamt) - si.vatamt) AS net, si.ordertype, u.username as waiter, sum(s.isqty * s.isamt) + SUM((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales, sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void,
    si.pwdamt, si.soloamt,sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, si.vatex

from glhead as head


    left join glstock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join useraccess as u on u.userid = h.userid
    left join client on head.clientid = client.clientid
    join item on item.itemid = s.itemid
    left join hstockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join client as wh on wh.clientid = head.whid

where date(h.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0 $filter

group by cntnum.station, h.docno, client.clientname , h.dateid, item.barcode, item.itemname,
s.isqty , s.isamt , si.lessvat, si.sramt, si.discamt, si.pwdamt, si.empdisc, s.rrcost, si.ordertype, h.openby, u.username,si.soloamt,si.vatex

order by openby, docno, dateid

     ";



    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function detailed_sales_qsr($config)
  {
    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->qsr_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->qsr_header($config);


    $prevGroup = '';
    $prevDocno = '';
    $prevStation = '';



    $groupAmt = 0;
    $lessVatamt = 0;
    $subSDisc = 0;
    $subDiscAmt = 0;
    $subPwdAmt = 0;
    $subEmpDisc = 0;
    $subTotalSales = 0;

    $stationTotalSales = 0;
    $stationTotalVat = 0;
    $stationTotalNetSales = 0;

    $cashierTotalSales = 0;
    $cashierTotalVat = 0;
    $cashierTotalNetSales = 0;

    $grossSales = 0;
    $voidSales  = 0;
    $totalQty = 0;
    $vatExempt = 0;
    $vatSales = 0;
    $regDisc = 0;
    $srDisc = 0;
    $pwdDisc = 0;
    $soloDisc = 0;
    $otherDisc = 0;
    $totalDiscount = 0;
    $totalSales = 0;
    $totalVat = 0;
    $totalNetSales = 0;

    $cashierDineInCount = 0;
    $cashierTakeOutCount = 0;
    $stationDineInCount = 0;
    $stationTakeOutCount = 0;


    for ($i = 0; $i < count($data); $i++) {
      $currGroup = $data[$i]['openby'];
      $currStation = $data[$i]['station'];
      $currGroupKey = empty($currGroup) ? ' ' : $currGroup;
      $currDocno = $data[$i]['docno'];
      $showDocno = ($currDocno !== $prevDocno);


      if ($prevDocno !== '' && $currDocno !== $prevDocno) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '122', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '56', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '122', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '56', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Subtotal', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($lessVatamt != 0) ? number_format($lessVatamt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($groupAmt != 0) ? number_format($groupAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($subSDisc != 0) ? number_format($subSDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subDiscAmt != 0) ? number_format($subDiscAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subPwdAmt != 0) ? number_format($subPwdAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subEmpDisc != 0) ? number_format($subEmpDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(($subTotalSales != 0) ? number_format($subTotalSales, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $lessVatamt = 0;
        $groupAmt = 0;
        $subSDisc = 0;
        $subDiscAmt = 0;
        $subPwdAmt = 0;
        $subEmpDisc = 0;
        $subTotalSales = 0;
      }


      if ($prevStation !== '' && $currStation !== $prevStation) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col("Station ({$prevStation}) Sub Total:", '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-In: ' . $stationDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Take-Out: ' . $stationTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Sales: ' . (($stationTotalSales != 0) ? number_format($stationTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $stationTotalSales = $stationTotalVat = $stationTotalNetSales = 0;
      }


      if ($currGroupKey !== $prevGroup && $prevGroup !== '') {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(' (' . $prevGroup . ') Sub Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-In : ' . $cashierDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Take-Out : ' . $cashierTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total Sales: ' . (($cashierTotalSales != 0) ? number_format($cashierTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $cashierTotalSales = $cashierTotalVat = $cashierTotalNetSales = 0;
      }


      if ($currGroupKey !== $prevGroup || $currStation !== $prevStation) {
        $displayStation = ($currStation !== $prevStation) ? "Station: {$currStation}" : '';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("{$displayStation}", null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Cashier: {$currGroupKey}", null, null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($showDocno ? $currDocno : '', '120', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($showDocno ? $data[$i]['customer'] : '', '122', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['cardtype'], '56', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($showDocno ? date('Y-m-d', strtotime($data[$i]['dateid'])) : '', '106', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['barcode'], '120', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['itemname'], '120', null, false, $border, '', 'L', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['qty'] != 0) ? number_format($data[$i]['qty'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['amt'] != 0) ? number_format($data[$i]['amt'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['lessvat'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['sramt'] != 0) ? number_format($data[$i]['sramt'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['disc'] != 0) ? number_format($data[$i]['disc'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['pwdamt'] != 0) ? number_format($data[$i]['sramt'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['empdisc'] != 0) ? number_format($data[$i]['empdisc'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['totalsales'] != 0) ? number_format($data[$i]['totalsales'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['cost'] != 0) ? number_format($data[$i]['vat'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(($data[$i]['totalcost'] != 0) ? number_format($data[$i]['net'], 2) : '-', '70', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col((isset($data[$i]['ordertype']) && $data[$i]['ordertype'] != 0 ? $data[$i]['ordertype'] : ''), '106', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col($data[$i]['waiter'], '106', null, false, $border, '', 'C', $font, $fontsize, '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      if ($data[$i]['ordertype'] == 1) {
        $cashierDineInCount++;
        $stationDineInCount++;
      } elseif ($data[$i]['ordertype'] == 2) {
        $cashierTakeOutCount++;
        $stationTakeOutCount++;
      }



      $lessVatamt += (float)$data[$i]['lessvat'];
      $groupAmt += (float)$data[$i]['amt'];
      $subSDisc += (float)$data[$i]['sramt'];
      $subDiscAmt += (float)$data[$i]['disc'];
      $subPwdAmt += (float)$data[$i]['pwdamt'];
      $subEmpDisc += (float)$data[$i]['empdisc'];
      $subTotalSales += (float)$data[$i]['totalsales'];
      $stationTotalSales += (float)$data[$i]['totalsales'];
      $stationTotalVat += (float)$data[$i]['vat'];
      $stationTotalNetSales += (float)$data[$i]['net'];
      $cashierTotalSales += (float)$data[$i]['totalsales'];
      $cashierTotalVat += (float)$data[$i]['vat'];
      $cashierTotalNetSales += (float)$data[$i]['net'];



      $grossSales += (float)$data[$i]['totalsales'];
      $voidSales  += (float)$data[$i]['void'];
      $totalQty += (float)$data[$i]['qty'];
      $vatExempt += (float)$data[$i]['vatex'];
      $regDisc += (float)$data[$i]['disc'];
      $srDisc += (float)$data[$i]['sramt'];
      $pwdDisc += (float)$data[$i]['pwdamt'];
      $soloDisc += (float)$data[$i]['soloamt'];
      $otherDisc += (float)$data[$i]['otherdiscount'];
      $totalDiscount += (float)$data[$i]['disc'] + (float)$data[$i]['sramt'] + (float)$data[$i]['pwdamt'] + (float)$data[$i]['soloamt'] + (float)$data[$i]['otherdiscount'];
      $totalSales += (float)$data[$i]['totalsales'];
      $totalVat += (float)$data[$i]['vat'];
      $totalNetSales += (float)$data[$i]['net'];


      $vatSales =  $totalNetSales - $vatExempt;


      $prevDocno = $currDocno;
      $prevGroup = $currGroupKey;
      $prevStation = $currStation;
    }

    if ($prevDocno !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '122', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '56', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '122', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '56', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Subtotal', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(($lessVatamt != 0) ? number_format($lessVatamt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($groupAmt != 0) ? number_format($groupAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(($subSDisc != 0) ? number_format($subSDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subDiscAmt != 0) ? number_format($subDiscAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subPwdAmt != 0) ? number_format($subPwdAmt, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subEmpDisc != 0) ? number_format($subEmpDisc, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(($subTotalSales != 0) ? number_format($subTotalSales, 2) : '-', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '106', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $lessVatamt = 0;
      $groupAmt = 0;
      $subSDisc = 0;
      $subDiscAmt = 0;
      $subPwdAmt = 0;
      $subEmpDisc = 0;
      $subTotalSales = 0;
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevGroup !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(' (' . $prevGroup . ') Sub Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In : ' . $cashierDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Take-Out : ' . $cashierTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Sales: ' . (($cashierTotalSales != 0) ? number_format($cashierTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    if ($prevStation !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("({$prevStation}) Sub Total:", '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-In: ' . $stationDineInCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Take-Out: ' . $stationTakeOutCount, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total Sales: ' . (($stationTotalSales != 0) ? number_format($stationTotalSales, 2) : '-'), '100', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Gross Sales:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grossSales, 2), '100', null, false, $border, 'T',  'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Regular:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($regDisc, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Sales:', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalSales, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Void Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($voidSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Senior:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($srDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('VAT:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalVat, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Quantity:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalQty, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('PWD:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($pwdDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Net Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalNetSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vat Exempt:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatExempt, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Solo Parent:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($soloDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vatable Sales:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vatSales, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Other Discounts:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($otherDisc, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Service Charge:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Discounts', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalDiscount, 2), '100', null, false, '', '', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', 10, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();




    $str .= $this->reporter->endreport();
    return $str;
  }

  public function qsr_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $date = date("m/d/Y g:i a");
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];


    $layoutsize = '1500';
    $font = "Century Gothic";
    $fontsize = "7";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('QSR/FINE DINE DETAILED SALES REPORT', '1500', null, false, $border, '', 'C', $font, '12', 'B', 'blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1500', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($modelid == '0') {
      $str .= $this->reporter->col('MODEL:  ALL MODEL', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('MODEL:  ' . strtoupper($model), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($paymentCond == '') {
      $str .= $this->reporter->col('PAYMENT:  ALL PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PAYMENT:  ' . strtoupper($pospayment), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    if ($doc  == '') {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ALL DOC', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DOCUMENT TYPE:  ' . strtoupper($doc), '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '122', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('C.Type', '56', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '106', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less SVat.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SDisc.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PwdDisc', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Emp. Disc.', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T. Sales', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T. Cost', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('O. Type', '50', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Waiter', '106', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function qsr_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = '$checkwh'";
    }

    $query = "
select cntnum.station,  h.docno, h.cardtype, client.clientname as customer, h.dateid, item.barcode, item.itemname,
s.isqty as qty, s.isamt as amt, si.lessvat, si.sramt, si.discamt as disc,  si.empdisc,sum(s.isqty * s.isamt) as totalsales,
s.rrcost as cost,sum(s.isqty * s.rrcost) as totalcost, h.openby,sum(si.vatamt) as vat,
   SUM((isqty * isamt) - si.vatamt) AS net, si.ordertype, u.username as waiter, sum(s.isqty * s.isamt) + SUM((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales, sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void,
    si.pwdamt, si.soloamt,sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, si.vatex

from lahead as head


    left join lastock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join useraccess as u on u.userid = h.userid
    left join client on head.client = client.client
    join item on item.itemid = s.itemid
    left join hstockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join client as wh on wh.client = head.wh

where date(h.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0  $filter

group by cntnum.station, h.docno,h.cardtype, client.clientname , h.dateid, item.barcode, item.itemname,
s.isqty , s.isamt , si.lessvat, si.sramt, si.discamt, si.pwdamt, si.empdisc, s.rrcost, si.ordertype, h.openby, u.username,si.soloamt,si.vatex

union all




select cntnum.station,  h.docno, h.cardtype, client.clientname as customer, h.dateid, item.barcode, item.itemname,
s.isqty as qty, s.isamt as amt, si.lessvat, si.sramt, si.discamt as disc,  si.empdisc,sum(s.isqty * s.isamt) as totalsales,
s.rrcost as cost,sum(s.isqty * s.rrcost) as totalcost, h.openby,sum(si.vatamt) as vat,
   SUM((isqty * isamt) - si.vatamt) AS net, si.ordertype, u.username as waiter, sum(s.isqty * s.isamt) + SUM((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * IF(cntnum.bref = 'SRS' AND h.bref IN ('V','RT'), 1, 0)) AS grossSales, sum((s.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and h.bref='V',-1,0)) as void,
    si.pwdamt, si.soloamt,sum(si.acdisc+ si.vipdisc+ si.empdisc+ si.oddisc+ si.smacdisc) as otherdiscount, si.vatex

from glhead as head


    left join glstock as s on s.trno = head.trno
    left join head as h on h.webtrno = head.trno   and h.docno = s.ref
    left join useraccess as u on u.userid = h.userid
    left join client on head.clientid = client.clientid
    join item on item.itemid = s.itemid
    left join hstockinfo as si on si.trno = s.trno and si.line = s.line
    left join cntnum on cntnum.trno = head.trno
    left join client as wh on wh.clientid = head.whid

where date(h.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and s.void = 0  $filter

group by cntnum.station, h.docno, h.cardtype, client.clientname , h.dateid, item.barcode, item.itemname,
s.isqty , s.isamt , si.lessvat, si.sramt, si.discamt, si.pwdamt, si.empdisc, s.rrcost, si.ordertype,
h.openby, u.username,si.soloamt,si.vatex

order by  openby, docno, dateid
     ";



    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }


  public function detailed_sales_void($config)
  {
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $gr = 0;
    $data = $this->void_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->void_header($config);


    $subQty   = 0;
    $subAmount = 0;
    $subExt   = 0;

    $totalAmt = 0;

    for ($i = 0; $i < count($data); $i++) {


      $subQty    += !empty($data[$i]['qty']) ? $data[$i]['qty'] : 0;
      $subAmount += !empty($data[$i]['amount']) ? $data[$i]['amount'] : 0;
      $subExt    += !empty($data[$i]['ext']) ? $data[$i]['ext'] : 0;


      $totalAmt    += !empty($data[$i]['ext']) ? $data[$i]['ext'] : 0;

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['clientname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['qty']) && $data[$i]['qty'] != 0 ? number_format($data[$i]['qty'], 2) : '-', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['amount']) && $data[$i]['amount'] != 0 ? number_format($data[$i]['amount'], 2) : '-', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['ext']) && $data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, 'border-top:1px solid #000;', '', 'L', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUBTOTAL', '550', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($subQty, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($subAmount, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($subExt, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total Amount', '550', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($totalAmt, 2), '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function void_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];



    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Detailed Sales Void Report', '1000', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, $fontsize, 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Table', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quantity', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Amount', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function void_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }

    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = $checkwh";
    }

    $query = "
select client.clientname,item.itemname, item.barcode, stock.isqty, stock.isamt,
(stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt)
* IF(cntnum.doc = 'CM', -1, 1) AS ext


from lahead as head

left join lastock as stock on stock.trno = head.trno
left join client on client.client = head.client
join item on item.itemid = stock.itemid
left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
left join cntnum on cntnum.trno = head.trno
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join client as wh on wh.client = head.wh

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and stock.void = 1 $filter

union all


select client.clientname,item.itemname, item.barcode, stock.isqty, stock.isamt,
(stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt)
* IF(cntnum.doc = 'CM', -1, 1) AS ext


from glhead as head

left join glstock as stock on stock.trno = head.trno
left join client on client.clientid = head.clientid
join item on item.itemid = stock.itemid
left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
left join cntnum on cntnum.trno = head.trno
left join head as h on h.webtrno = head.trno   and h.docno = stock.ref
left join client as wh on wh.clientid = head.whid

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and cntnum.bref in ('SJS','SRS')
     and stock.void = 1 $filter
     ";



    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }


  public function detailed_sales_costofsales($config)
  {
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $data = $this->costofsales_query($config);
    $this->reportParams =  ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';


    $str .= $this->reporter->beginreport();
    $str .= $this->costofsales_header($config);


    $prevClass = null;


    $subQty = 0;
    $subAmt = 0;



    $grandQty = 0;
    $grandAmt = 0;



    for ($i = 0; $i < count($data); $i++) {
      $currClass = !empty($data[$i]['brand']) ? $data[$i]['brand'] : ' ';


      if ($prevClass === null || $currClass !== $prevClass) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BRAND: ' . $currClass, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['qty']) && $data[$i]['qty'] != 0 ? number_format($data[$i]['qty'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(!empty($data[$i]['amount']) && $data[$i]['amount'] != 0 ? number_format($data[$i]['amount'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $subQty += $data[$i]['qty'];
      $subAmt += $data[$i]['amount'];



      $grandQty += $data[$i]['qty'];
      $grandAmt += $data[$i]['amount'];



      $nextClass = isset($data[$i + 1]) ? (!empty($data[$i + 1]['brand']) ? $data[$i + 1]['brand'] : ' ') : null;
      if ($nextClass !== $currClass) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sub Total : ' . $currClass, '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(!empty($subQty) && $subQty != 0 ? number_format($subQty, 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(!empty($subAmt) && $subAmt != 0 ? number_format($subAmt, 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $subQty = $subAmt =  0;
      }

      $prevClass = $currClass;
    }



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total', '750', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($grandQty) && $grandQty != 0 ? number_format($grandQty, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($grandAmt) && $grandAmt != 0 ? number_format($grandAmt, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->endreport();

    return $str;
  }

  public function costofsales_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $date = date("m/d/Y g:i a");
    $stockgrp = $config['params']['dataparams']['groupid'];
    $stock_groupname = $config['params']['dataparams']['stock_groupname'];
    $brand = $config['params']['dataparams']['brandname'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $model = $config['params']['dataparams']['modelname'];
    $partid = $config['params']['dataparams']['partid'];
    $part = $config['params']['dataparams']['partname'];
    $classid = $config['params']['dataparams']['classid'];
    $class = $config['params']['dataparams']['classic'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $pospayment = $config['params']['dataparams']['pospayment'];


    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRODUCT SUMMARY REPORT', '1206', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    if ($stockgrp == '0') {
      $str .= $this->reporter->col('GROUP:  ALL GROUP', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GROUP:  ' . strtoupper($stock_groupname), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($partid  == '0') {
      $str .= $this->reporter->col('PART:  ALL PART', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART:  ' . strtoupper($part), '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    }
    if ($classid  == '0') {
      $str .= $this->reporter->col('CLASS:  ALL CLASS', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CLASS:  ' . strtoupper($class), '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($brandid == '0') {
      $str .= $this->reporter->col('BRAND:  ALL BRAND', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND:  ' . strtoupper($brand), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    }
    if ($customer  == '') {
      $str .= $this->reporter->col('CUSTOMER:  ALL CLASS', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER:  ' . strtoupper($customer), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '250', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  public function costofsales_query($config)
  {
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    // $dcentername     = $config['params']['dataparams']['dcentername'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $station = $config['params']['dataparams']['station'];
    $cashier = $config['params']['dataparams']['cashier'];
    $wh = $config['params']['dataparams']['whid'];
    $customer = $config['params']['dataparams']['clientname'];
    $doc = $config['params']['dataparams']['posdoctype'];
    $groupid = $config['params']['dataparams']['groupid'];
    $brandid = $config['params']['dataparams']['brandid'];
    $modelid = $config['params']['dataparams']['model'];
    $partid = $config['params']['dataparams']['partid'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];
    $checkbarcode = $config['params']['dataparams']['reporttype'];
    $checkwh = $config['params']['dataparams']['whid'];

    if ($station != '') {
      $filter   .= " and cntnum.station = '$station'";
    }
    if ($cashier != '') {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($wh != '0') {
      $filter .= " and wh.clientname = $wh";
    }
    if ($customer != '') {
      $filter .= " and client.clientname = '$customer'";
    }
    if ($doc != '') {
      $filter .= " and left(h.bref," . strlen($doc) . ") = '$doc'";
    }
    if ($groupid != '0') {
      $filter   .= " and item.groupid = $groupid";
    }

    if ($brandid != '0') {
      $filter   .= " and brand.brandid = $brandid";
    }

    if ($modelid != '0') {
      $filter   .= " and item.model = $modelid";
    }


    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    if ($partid != '0') {
      $filter   .= " and item.part = $partid";
    }

    if ($checkbarcode == 'sdcharoe') {
      $filter   .= " and item.barcode  NOT LIKE '%$$%'";
    }
    if ($checkwh != '0') {
      $filter   .= " and wh.wh = $checkwh";
    }



    $query = "
    
select itemname, barcode, CASE WHEN brand_desc = '' or isnull(brand_desc) then 'Discounts' ELSE brand_desc End as brand, sum(qty) as qty, sum(amt) as amount
from (

select item.itemname, item.barcode, brand.brand_desc, sum(s.iss - s.qty) as qty, SUM(s.amt*(s.iss-s.qty)-si.vatamt) AS amt

from lahead as head

left join lastock as s on s.trno = head.trno
join item on item.itemid = s.itemid
left join cntnum on cntnum.trno = head.trno
left join stockinfo as si on si.trno = s.trno and si.line = s.line
left join frontend_ebrands as brand on brand.brandid = item.brand
left join part_masterfile as pmaster on pmaster.part_id = item.part
left join client on client.client = head.client
left join client as wh on wh.client = head.wh


where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS') AND pmaster.part_name='MENU' $filter
group by item.itemname, item.barcode, brand.brand_desc

union all

select item.itemname, item.barcode, brand.brand_desc, sum(s.iss - s.qty) as qty, SUM(s.amt*(s.iss-s.qty)-si.vatamt) AS amt

from lahead as head

left join lastock as s on s.trno = head.trno
join item on item.itemid = s.itemid
left join cntnum on cntnum.trno = head.trno
left join stockinfo as si on si.trno = s.trno and si.line = s.line
left join frontend_ebrands as brand on brand.brandid = item.brand
left join part_masterfile as pmaster on pmaster.part_id = item.part
left join client on client.client = head.client
left join client as wh on wh.client = head.wh

where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS') AND item.barcode='*' $filter
group by item.itemname, item.barcode, brand.brand_desc


union all

select item.itemname, item.barcode, brand.brand_desc, sum(s.iss - s.qty) as qty, SUM(s.amt*(s.iss-s.qty)-si.vatamt) AS amt

from glhead as head

left join glstock as s on s.trno = head.trno
join item on item.itemid = s.itemid
left join cntnum on cntnum.trno = head.trno
left join hstockinfo as si on si.trno = s.trno and si.line = s.line
left join frontend_ebrands as brand on brand.brandid = item.brand
left join part_masterfile as pmaster on pmaster.part_id = item.part
left join client on client.clientid = head.clientid
left join client as wh on wh.clientid = head.whid


where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS') AND pmaster.part_name='MENU' $filter
group by item.itemname, item.barcode, brand.brand_desc

union all

select item.itemname, item.barcode, brand.brand_desc, sum(s.iss - s.qty) as qty, SUM(s.amt*(s.iss-s.qty)-si.vatamt) AS amt

from glhead as head

left join glstock as s on s.trno = head.trno
join item on item.itemid = s.itemid
left join cntnum on cntnum.trno = head.trno
left join hstockinfo as si on si.trno = s.trno and si.line = s.line
left join frontend_ebrands as brand on brand.brandid = item.brand
left join part_masterfile as pmaster on pmaster.part_id = item.part
left join client on client.clientid = head.clientid
left join client as wh on wh.clientid = head.whid


where date(head.dateid) between '$start' and '$end' and cntnum.center = '$center' and left (cntnum.bref,3) in ('SJS','SRS') AND item.barcode='*' $filter
group by item.itemname, item.barcode, brand.brand_desc

) as t

 group by t.itemname, t.barcode, t.brand_desc
  
 order by t.brand_desc, t.itemname
 ";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }
}//end class
