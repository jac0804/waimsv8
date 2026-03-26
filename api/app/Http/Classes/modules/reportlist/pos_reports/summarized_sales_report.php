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
use Symfony\Component\VarDumper\VarDumper;

class summarized_sales_report
{
  public $modulename = 'POS Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:3500px;';
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
    $fields = ['radioprint', 'start', 'end', 'dcentername', 'prefix'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.readonly', false);
    data_set($col1, 'prefix.readonly', false);

    $fields = ['radioreporttype'];
    $col2 = $this->fieldClass->create($fields);
    if ($companyid == 56) { // homeworks
      data_set($col2, 'radioreporttype.options', [
        // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'Standard', 'value' => '0', 'color' => 'red'],
        ['label' => 'QSR-FINE', 'value' => '1', 'color' => 'red'],
        ['label' => 'Collection', 'value' => '2', 'color' => 'red'],
        ['label' => 'PLU Report W/ Components', 'value' => '3', 'color' => 'red'],
        ['label' => 'PLU Report W/o Components', 'value' => '4', 'color' => 'red'],
        ['label' => 'Daily Sales', 'value' => '5', 'color' => 'red'],
        ['label' => 'Ingredients', 'value' => '6', 'color' => 'red'],
        ['label' => 'Customer Summary', 'value' => '7', 'color' => 'red'],
        ['label' => 'Customer Sales', 'value' => '8', 'color' => 'red'],
        ['label' => 'BIR-Sales Summry', 'value' => '9', 'color' => 'red'],
        ['label' => 'Senior Sales Book', 'value' => '10', 'color' => 'red'],
        ['label' => 'PWD Sales Book', 'value' => '11', 'color' => 'red'],
        ['label' => 'NAAC Sales Book', 'value' => '12', 'color' => 'red'],
        ['label' => 'Solo Parent Book', 'value' => '13', 'color' => 'red'],
        ['label' => 'Medal of Valor Sales Book', 'value' => '14', 'color' => 'red'],
      ]);
    }

    $fields = ['pos_station', 'lookup_cashier', 'brand', 'pospayment', 'stock_groupname', 'part', 'class', 'posdoctype', 'customer_name'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'pos_station_lookup.lookupclass', 'pos_station');
    data_set($col3, 'lookupcashier.lookupclass', 'lookup_cashier');
    data_set($col3, 'brand.lookupclass', 'brand');
    data_set($col3, 'pospayment.lookupclass', 'pospayment');
    data_set($col3, 'lookupgroup_stock.lookupclass', 'stockgrp');
    data_set($col3, 'lookuppart.lookupclass', 'part');
    data_set($col3, 'lookupclass_stock.lookupclass', 'class');
    data_set($col3, 'pos_doctype_lookup.lookupclass', 'posdoctype');

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
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
      '0' as reporttype,
      '0' as clientid,
      '' as customer,
      '' as pos_station,
      '0' as groupid,
      '' as stock_groupname,
      '0' as brandid,
      '' as brandname,
      '' as stationname,
      '' as clientname,
      '' as lookup_cashier,
      '0' as partid,
      '' as partname,
      '0' as classid,
      '' as classic,
      '' as pospayment,
      '' as paymentcond,
      '' as posdoctype
      ";
    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $str = $this->reportplotting($config);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($companyid == 56) { //MIS
      switch ($reporttype) {
        case '0': // standard
          return $this->summarized_salesreport_standard($config);
          break;
        case '1': // standard
          return $this->summarized_salesreport_qsrfine($config);
          break;
        case '2': // Collection Report
          return $this->summarized_salesreport_collection($config);
          break;
        case '3': // PLU W/ Components
          return $this->summarized_salesreport_plu_wcomponets($config);
          break;
        case '4': //  PLU W/o Components
          return $this->summarized_salesreport_plu_wo_components($config);
          break;
        case '5': // daily sales
          return $this->summarized_salesreport_dailysales($config);
          break;
        case '6': // Solo Parent Sales Book/ Report
          return $this->summarized_salesreport_ingredient($config);
          break;
        case '7': // Customer Summary Report
          return $this->summarized_salesreport_cssummary($config);
          break;
        case '8': // customer sales
          return $this->summarized_salesreport_customer_sales($config);
          break;
        case '9': // BIR sales
          return $this->summarized_salesreport_bir_sales($config);
          break;
        case '10': // SR sales book
          return $this->summarized_salesreport_sr_sales($config);
          break;
        case '11': // PWD sales book
          return $this->summarized_salesreport_pwd_sales($config);
          break;
        case '12': // naac sales book
          return $this->summarized_salesreport_naac_sales($config);
          break;
        case '14': // Valor sales book
          return $this->summarized_salesreport_medal_valor($config);
          break;
        default:
          return $this->summarized_salesreport_layout($config);
          break;
      }
    }
  }

  // created nov 27
  // QUERY
  public function summarized_salesreport_clsalesquery($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = $partid";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select 'MEMBER' as grp, x.*
              from (
                  select client, cname,
                         sum(gross) as gross, sum(disc) as disc, sum(discamt) as discamt,
                         sum(tax) as tax, sum(ntax) as ntax,
                         sum(void) as void, sum(rtn) as rtn, sum(net) as net
                  from (
                      select client.client, lahead.clientname as cname,
                             round(sum((stock.isamt * stock.isqty) * if(cntnum.doc<>'CM',1,0)),2) as gross,
                             sum(si.pwdamt + si.sramt + si.soloamt) as disc,
                             sum(case when head.bref in ('RT','V') then si.vatamt + si.discamt + si.valoramt + si.acdisc + si.empdisc + si.vipdisc + si.oddisc + si.smacdisc else 0 end) as discamt,
                             sum(case when head.bref not in ('RT','V') then si.vatamt else 0 end) as tax,
                             sum(si.lessvat) as ntax,
                             sum((stock.ext - si.lessvat - si.pwdamt - si.sramt - si.soloamt) * if(cntnum.bref='SRS' and head.bref='V',-1,0)) as void,
                             sum(case when head.bref='RT' then- (stock.ext - si.lessvat - si.pwdamt - si.sramt - si.soloamt) else 0 end) as rtn,
                             sum(si.nvat + si.vatex - si.acdisc) as net
                      from lahead
                      left join lastock stock on lahead.trno = stock.trno
                      left join head on head.webtrno = lahead.trno and head.docno = stock.ref
                      left join client on lahead.client = client.client
                      join item on stock.itemid = item.itemid
                      left join cntnum on lahead.trno = cntnum.trno
                      join stockinfo si on stock.trno = si.trno and si.line = stock.line
                      where left(cntnum.bref,3) in ('SJS','SRS')
                        and cntnum.center = '$center'
                        and date(lahead.dateid) between '$start' and '$end'
                        and client.client <> 'WALK-IN'
                        $filter
                      group by client.client, lahead.clientname, cntnum.bref
                  
                      union all
                  
                      select client.client, glhead.clientname as cname,
                             round(sum((stock.isamt * stock.isqty) * if(cntnum.doc<>'CM',1,0)),2) as gross,
                             sum(si.pwdamt + si.sramt + si.soloamt) as disc,
                             sum(case when head.bref in ('RT','V') then si.vatamt + si.discamt + si.valoramt + si.acdisc + si.empdisc + si.vipdisc + si.oddisc + si.smacdisc else 0 end) as discamt,
                             sum(case when head.bref not in ('RT','V') then si.vatamt else 0 end) as tax,
                             sum(si.lessvat) as ntax,
                             sum((stock.ext - si.lessvat - si.pwdamt - si.sramt - si.soloamt) * if(cntnum.bref='SRS' and head.bref='V',-1,0)) as void,
                             sum(case when head.bref='RT' then -(stock.ext - si.lessvat - si.pwdamt - si.sramt - si.soloamt) else 0 end) as rtn,
                             sum(si.nvat + si.vatex - si.acdisc) as net
                      from glhead
                      left join glstock stock on glhead.trno = stock.trno
                      left join head on head.webtrno = glhead.trno and head.docno = stock.ref
                      left join cntnum on glhead.trno = cntnum.trno
                      left join client on glhead.clientid = client.clientid
                      join item on stock.itemid = item.itemid
                      join hstockinfo si on stock.trno = si.trno and si.line = stock.line
                      where left(cntnum.bref,3) in ('SJS','SRS')
                        and cntnum.center = '001'
                        and date(glhead.dateid) between '$start' and '$end'
                        and client.client <> 'WALK-IN'
                        $filter
                      group by client.client, glhead.clientname, cntnum.bref
                  ) cs
                  group by cs.client, cs.cname
              ) x
                  
              union all
                  
              select 'NON-MEMBER' as grp, x.*
              from (
                  select client, cname,
                         sum(gross) as gross, sum(disc) as disc, sum(discamt) as discamt,
                         sum(tax) as tax, sum(ntax) as ntax,
                         sum(void) as void, sum(rtn) as rtn, sum(net) as net
                  from (
                      select client.client, lahead.clientname as cname,
                             round(sum((stock.isamt * stock.isqty) * if(cntnum.doc<>'CM',1,0)),2) as gross,
                             sum(si.pwdamt + si.sramt + si.soloamt) as disc,
                             sum(case when cntnum.bref in ('RT','V') then si.vatamt + si.discamt + si.valoramt + si.acdisc + si.empdisc + si.vipdisc + si.oddisc + si.smacdisc else 0 end) as discamt,
                             sum(case when cntnum.bref not in ('RT','V') then si.vatamt else 0 end) as tax,
                             sum(si.lessvat) as ntax,
                             sum((stock.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and head.bref='V',-1,0)) as void,
                             sum(case when head.bref='RT' then -(stock.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) else 0 end) as rtn,
                             sum(si.nvat + si.vatex - si.acdisc) as net
                      from lahead
                      left join lastock stock on lahead.trno = stock.trno
                      left join head on head.webtrno = lahead.trno and head.docno = stock.ref
                      left join client on lahead.client = client.client
                      join item on stock.itemid = item.itemid
                      left join cntnum on lahead.trno = cntnum.trno
                      join stockinfo si on stock.trno = si.trno and si.line = stock.line
                      where left(cntnum.bref,3) in ('SJS','SRS')
                        and cntnum.center = '001'
                        and date(lahead.dateid) between '$start' and '$end'
                        and client.client = 'WALK-IN'
                        $filter
                      group by client.client, lahead.clientname, cntnum.bref
                  
                      union all
                  
                      select client.client, glhead.clientname as cname,
                             round(sum((stock.isamt * stock.isqty) * if(cntnum.doc<>'CM',1,0)),2) as gross,
                             sum(si.pwdamt + si.sramt + si.soloamt) as disc,
                             sum(case when cntnum.bref in ('RT','V') then si.vatamt + si.discamt + si.valoramt + si.acdisc + si.empdisc + si.vipdisc + si.oddisc + si.smacdisc else 0 end) as discamt,
                             sum(case when cntnum.bref not in ('RT','V') then si.vatamt else 0 end) as tax,
                             sum(si.lessvat) as ntax,
                             sum((stock.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) * if(cntnum.bref='SRS' and head.bref='V',-1,0)) as void,
                             sum(case when head.bref='RT' then -(stock.ext - si.lessvat + si.pwdamt + si.sramt + si.soloamt) else 0 end) as rtn,
                             sum(si.nvat + si.vatex - si.acdisc) as net
                      from glhead
                      left join glstock stock on glhead.trno = stock.trno
                      left join head on head.webtrno = glhead.trno and head.docno = stock.ref
                      left join cntnum on glhead.trno = cntnum.trno
                      left join client on glhead.clientid = client.clientid
                      join item on stock.itemid = item.itemid
                      join hstockinfo si on stock.trno = si.trno and si.line = stock.line
                      where left(cntnum.bref,3) in ('SJS','SRS')
                        and cntnum.center = '001'
                        and date(glhead.dateid) between '$start' and '$end'
                        and client.client = 'WALK-IN'
                        $filter
                      group by client.client, glhead.clientname, cntnum.bref
                  ) cs
                  group by cs.client, cs.cname
              ) x
                  
              order by grp, cname
              
              ";
    //var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created dec 1
  public function summarized_salesreport_standardqry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $station = $config['params']['dataparams']['pos_station'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $doctype = $config['params']['dataparams']['posdoctype'];

    $filter = '';
    $leftjoin = "";

    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($brandid != "0") {
      $filter .= " and item.brand = $brandid";
    }
    if ($groupid != "0") {
      $filter .= " and item.groupid = $groupid";
    }
    if ($partid != "0") {
      $filter .= " and item.part = $partid";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($doctype != "") {
      $filter .= " and h.bref = '$doctype'";
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select station, cashier, dateid, postdate, docno, customer, cname, sum(gross) as gross, sum(lessvat) as lessvat, sum(disc) as disc,
           sum(otherdisc) as otherdisc, sum(sccharge) as sccharge, sum(totalsales) as totalsales
    from (
      select cntnum.bref, h.station, h.openby as cashier, h.dateid, cntnum.postdate, h.docno, client.client as customer, lahead.clientname as cname,
             sum(stock.isqty * stock.isamt) as gross,
             sum(si.lessvat) as lessvat,
             sum(si.pwdamt+si.sramt+si.soloamt+si.acdisc+si.valoramt) as disc,
             sum(si.discamt+si.vipdisc+si.empdisc+si.oddisc+si.smacdisc) as otherdisc,
             0 sccharge,
             round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as totalsales
      from lahead
      left join lastock stock on stock.trno = lahead.trno
      left join head as h on h.webtrno = lahead.trno and h.docno = stock.ref
      left join cntnum on cntnum.trno = lahead.trno
      left join client on client.client = lahead.client
      join item on item.itemid = stock.itemid
      join stockinfo as si on si.trno = stock.trno and si.line=stock.line
      where cntnum.bref in ('SJS','SRS') and cntnum.center='$center' and date(lahead.dateid) between '$start' and '$end'
      $filter
      
      group by h.dateid, h.station, h.openby, cntnum.bref, cntnum.postdate, h.docno, client.client, lahead.clientname
    
      union all
    
      select cntnum.bref, h.station, h.openby as cashier, h.dateid, cntnum.postdate, h.docno, client.client as customer, glhead.clientname as cname,
             sum(stock.isqty * stock.isamt) as gross,
             sum(si.lessvat) as lessvat,
             sum(si.pwdamt + si.sramt + si.soloamt + si.acdisc + si.valoramt) as disc,
             sum(si.discamt + si.vipdisc + si.empdisc + si.oddisc + si.smacdisc) as otherdisc,
             sum(ifnull((select sum(s.ext) from glstock as s join item as i on i.itemid = s.itemid where i.barcode='$' and s.trno=glhead.trno),0)) sccharge,
             round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as totalsales
      from glhead
      left join glstock stock on glhead.trno=stock.trno
      left join head as h on h.webtrno = glhead.trno and h.docno = stock.ref
      left join cntnum on cntnum.trno = glhead.trno
      left join client on client.clientid = glhead.clientid
      join item on item.itemid = stock.itemid
      join hstockinfo si on stock.trno = si.trno and si.line = stock.line
      where cntnum.bref in ('SJS','SRS') and cntnum.center='$center' and date(glhead.dateid) between '$start' and '$end'
      $filter      
      group by h.dateid, h.station, h.openby, cntnum.bref, cntnum.postdate, h.docno, client.client, glhead.clientname
    ) s
    group by s.dateid, s.station, s.cashier, s.bref, s.postdate, s.docno, s.customer, s.cname
    order by s.station, s.cashier";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created dec 3
  public function summarized_salesreport_dailysalesqry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
      $leftjoin .= " left join client on lahead.client=client.client";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
      $leftjoin .= " left join head as h on h.webtrno = glhead.trno and h.docno = stock.ref";
    }
    if ($brandid != "0") {
      $filter .= " and item.brand = '$brandid'";
      $leftjoin .= " join item on stock.itemid=item.itemid";
    }
    if ($groupid != "0") {
      $filter .= " and item.groupid = '$groupid'";
      $leftjoin .= " join item on stock.itemid=item.itemid";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
      $leftjoin .= " join item on stock.itemid=item.itemid";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
      $leftjoin .= " join item on stock.itemid=item.itemid";
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select dateid,station,
                     min(begin_si) as begin_si,max(end_si) as end_si,
                     sum(gross) gross, sum(disc) disc, sum(srdisc) srdisc, sum(sperday) sperday
              from(
              select date(lahead.dateid) dateid,cntnum.station,
                      ( select h.docno from head as h where left(h.docno,2) ='SI'
                      and h.station = cntnum.station and date(h.dateid) = date(lahead.dateid) order by h.docno asc limit 1 ) as begin_si,
                      ( select h.docno from head as h where left(h.docno,2) = 'SI'
                      and h.station = cntnum.station and date(h.dateid) = date(lahead.dateid) order by h.docno desc limit 1 ) as end_si,
                      sum(stock.isqty * stock.isamt) as gross,
                      sum(si.discamt + si.acdisc + si.valoramt + si.vipdisc + si.empdisc + si.oddisc + si.smacdisc) disc,
                      sum(si.sramt + si.soloamt + si.pwdamt) srdisc,
                      round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as sperday
              from lahead
              left join lastock stock on lahead.trno=stock.trno
              join stockinfo si on stock.trno=si.trno and si.line=stock.line
              left join cntnum on lahead.trno=cntnum.trno
              where cntnum.bref in ('SJS','SRS', 'SI') and cntnum.center='$center' and lahead.dateid between '$start' and '$end'
              $filter

              group by lahead.dateid, cntnum.station

              union all

              select date(glhead.dateid),cntnum.station,
                      ( select h.docno from head as h where left(h.docno,2) ='SI'
                      and h.station = cntnum.station and date(h.dateid) = date(glhead.dateid) order by h.docno asc limit 1 ) as begin_si,
                      ( select h.docno from head as h where left(h.docno,2) = 'SI'
                      and h.station = cntnum.station and date(h.dateid) = date(glhead.dateid) order by h.docno desc limit 1 ) as end_si,
                      sum(stock.isqty * stock.isamt) as gross,
                      sum(si.discamt + si.acdisc + si.valoramt + si.vipdisc + si.empdisc + si.oddisc + si.smacdisc),
                      sum(si.sramt + si.soloamt + si.pwdamt),
                      round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as sperday
              from glhead
              left join glstock stock on glhead.trno=stock.trno
              join hstockinfo si on stock.trno=si.trno and si.line=stock.line
              left join cntnum on glhead.trno=cntnum.trno
              where cntnum.bref in ('SJS','SRS', 'SI') and cntnum.center='$center' and glhead.dateid between '$start' and '$end'
              $filter
               
              group by glhead.dateid, cntnum.station) dsales
              group by dateid,station, dsales.begin_si, dsales.end_si
              order by dateid,station, begin_si, end_si";
    // var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created dec 4
  public function summarized_salesreport_ingredientqry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select code, name, cost, sum(qty) as qty, uom, sum(cost * qty) as total

              from (select item.barcode as code, item.itemname as name, stock.isamt as cost,
              sum(case when lahead.doc NOT IN ('CM') then stock.isqty else 0 end) as qty, item.uom

              from lahead
              left join lastock as stock on lahead.trno = stock.trno
              join item on stock.itemid = item.itemid
              left join cntnum on lahead.trno = cntnum.trno
              left join head on lahead.trno = head.trno
              left join branchstation as branch on head.station = branch.station
              left join client on lahead.client = client.client
              left join stockgrp_masterfile as stockgrp on item.groupid = stockgrp.stockgrp_id
              left join frontend_ebrands as brands on item.brand = brands.brandid

              where left(cntnum.bref, 3) in ('SJS', 'SRS') and stock.iscomponent = 1 and cntnum.center = '$center' and date(lahead.dateid) between '$start' and '$end'
              $filter

              group by barcode, name, cost, uom

              union all

              select item.barcode as code, item.itemname as name, stock.isamt as cost,
              sum(case when glhead.doc NOT IN ('CM') then stock.isqty else 0 end) as qty, item.uom

              from glhead
              left join glstock as stock on glhead.trno = stock.trno
              join item on stock.itemid = item.itemid
              left join cntnum on glhead.trno = cntnum.trno
              left join head on glhead.trno = head.trno
              left join branchstation as branch on head.station = branch.station
              left join client on glhead.clientid = client.clientid
              left join stockgrp_masterfile as stockgrp on item.groupid = stockgrp.stockgrp_id
              left join frontend_ebrands as brands on item.brand = brands.brandid

              where left(cntnum.bref, 3) in ('SJS', 'SRS') and stock.iscomponent = 1 and cntnum.center = '$center' and date(glhead.dateid) between '$start' and '$end'
              $filter

              group by barcode, name, cost, uom) as x

              group by x.code, x.name, x.cost, x.uom";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created dec 6
  public function summarized_salesreport_collectionqry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $doctype = $config['params']['dataparams']['posdoctype'];

    $filter = '';
    $leftjoin = "";
    $leftjoin2 = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
      $leftjoin .= " left join client on client.client = lahead.client";
      $leftjoin2 .= " left join client on client.clientid = glhead.clientid";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($doctype != "") {
      $filter .= " and h.bref = '$doctype'";
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select yourref, station, cashier, postdate, docno, cname, sum(isamt) as isamt, sum(lessvat) as lessvat, sum(srdisc) as srdisc, sum(disc) as disc,
              sum(otherdisc) as otherdisc, sum(ttlsales) as ttlsales,
              cash, card, cheque, cr, lp, voucher, debit, smac, eplus, onlinedeals

              from (select h.yourref, h.station, h.openby as cashier, h.postdate, h.docno,
              lahead.clientname as cname,
              sum(stock.isamt * stock.isqty) as isamt, sum(si.lessvat) as lessvat,

              sum(si.sramt + si.pwdamt + si.soloamt + si.acdisc) as srdisc, sum(si.discamt) as disc,
              sum(si.vipdisc + si.empdisc + si.smacdisc + si.valoramt + si.oddisc) as otherdisc,
              round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ttlsales,

              h.cash, h.card, h.cheque, h.cr, h.lp, h.voucher, h.debit, h.smac, h.eplus, h.onlinedeals

              from lahead
              left join lastock as stock on lahead.trno = stock.trno
              left join head as h on h.webtrno = lahead.trno and h.docno = stock.ref
              join item on item.itemid = stock.itemid
              left join cntnum on cntnum.trno = lahead.trno
              join hstockinfo as si on si.trno = stock.trno and stock.line = si.line
              $leftjoin

              where left(cntnum.bref, 3) in ('SJS', 'SRS') and cntnum.center = '$center' and date(lahead.dateid) between '$start' and '$end'
              $filter

              group by lahead.clientname, cntnum.bref, h.postdate, h.docno, h.station, h.openby, h.yourref,
              h.cash, h.card, h.cheque, h.cr, h.lp, h.voucher, h.debit, h.smac, h.eplus, h.onlinedeals

              union all

              select h.yourref, h.station, h.openby as cashier, h.postdate, h.docno,
              glhead.clientname as cname, sum(stock.isamt * stock.isqty) as isamt, sum(si.lessvat) as lessvat,
              sum(si.sramt + si.pwdamt + si.soloamt + si.acdisc) as srdisc,
              sum(si.discamt) as disc,
              sum(si.vipdisc + si.empdisc + si.smacdisc + si.valoramt + si.oddisc) as otherdisc,
              round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ttlsales,

              h.cash, h.card, h.cheque, h.cr, h.lp, h.voucher, h.debit, h.smac, h.eplus, h.onlinedeals

              from glhead as glhead
              left join glstock as stock on glhead.trno = stock.trno
              left join head as h on h.webtrno = glhead.trno and h.docno = stock.ref
              left join cntnum on cntnum.trno = glhead.trno
              join item on item.itemid = stock.itemid
              join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
              $leftjoin2

              where left(cntnum.bref, 3) in ('SJS', 'SRS') and cntnum.center = '$center' and date(glhead.dateid) between '$start' and '$end'
              $filter

              group by glhead.clientname, cntnum.bref, h.postdate, h.docno, h.station, h.openby, h.yourref,
              h.cash, h.card, h.cheque, h.cr, h.lp, h.voucher, h.debit, h.smac, h.eplus, h.onlinedeals) as x

              group by x.postdate, x.docno, x.cname, x.station, x.cashier, x.yourref,
              x.cash, x.card, x.cheque, x.cr, x.lp, x.voucher, x.debit, x.smac, x.eplus, x.onlinedeals

              order by x.station, x.cashier";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // plu (create JAN 16 2026) query
  public function summarized_salesreport_plu_wcomponets_query($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    $query = "select i.barcode,i.itemname,sum(stock.isqty) AS isqty,ic.cl_name,i.part,
              sum(stock.ext-info.lessvat-info.sramt-info.pwdamt-info.soloamt) as ext,brand.brand_desc

              FROM glhead AS head
              LEFT JOIN glstock AS stock ON stock.trno=head.trno
              LEFT JOIN item AS i ON i.itemid=stock.itemid
              left JOIN item_class AS ic ON i.class=ic.cl_id
              left join cntnum as num on num.trno = head.trno 
              JOIN hstockinfo AS info ON info.trno=stock.trno AND info.line=stock.line
              JOIN frontend_ebrands AS brand ON i.brand=brand.brandid
              WHERE stock.iscomponent =1 and num.center = $center and num.doc in ('SRS','SJ') and date(head.dateid) between '$start' and '$end' $filter
              GROUP BY ic.cl_name,i.barcode,itemname,i.part,brand.brand_desc";


    return $this->coreFunctions->opentable($query);
  }

  // created jan 16
  public function summarized_salesreport_plu_wo_components_qry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $doctype = $config['params']['dataparams']['posdoctype'];
    $paylabel = $config['params']['dataparams']['pospayment'];
    $paymentCond = $config['params']['dataparams']['paymentcond'];

    $filter = '';
    $leftjoin = "";
    $leftjoin2 = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
      $leftjoin .= " left join client on client.client = lahead.client";
      $leftjoin2 .= " left join client on client.clientid = glhead.clientid";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and item.brand = '$brandid'";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($doctype != "") {
      $filter .= " and h.bref = '$doctype'";
    }
    // if ($paylabel !== "") {
    //   $filter .= " and h.yourref = '$paylabel'";
    // }
    if ($paymentCond !== "") {
      $filter .= " $paymentCond";
    }

    $query = "select cl_name, barcode, itemname,qty,ext from (
              select cl_name, item.barcode,item.itemname, sum(stock.isqty) as qty, item.class,
              round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext
              from item
              left join lastock as stock on stock.itemid=item.itemid
              left join lahead as head on head.trno=stock.trno
              left join head as h on h.webtrno = head.trno and h.docno = stock.ref
              left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
              left join cntnum on cntnum.trno=head.trno
              left join item_class as class on class.cl_id = item.class
              where cntnum.center = '$center' and cntnum.bref in ('SJS','SRS') and date(head.dateid) between '$start' and '$end'
              and stock.iscomponent=0
              $filter
              group by cl_name, barcode, item.itemname, item.class

              union all

              select cl_name, item.barcode,item.itemname, sum(stock.isqty) as qty, item.class,
              round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext
              from item
              left join glstock as stock on stock.itemid=item.itemid
              left join glhead as head on head.trno=stock.trno
              left join head as h on h.webtrno = head.trno and h.docno = stock.ref
              left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
              left join cntnum on cntnum.trno=head.trno
              left join item_class as class on class.cl_id = item.class
              where cntnum.center = '$center' and cntnum.bref in ('SJS','SRS') and date(head.dateid) between '$start' and '$end'
              and stock.iscomponent=0
              $filter
              group by cl_name, barcode, item.itemname, item.class
              ) as plu order by class, barcode";
    //var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created dec 10
  public function summerized_salesreport_qsrfineqry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $doctype = $config['params']['dataparams']['posdoctype'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and item.brand = '$brandid'";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($doctype != "") {
      $filter .= " and head.bref = '$doctype'";
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select station, openby, docno, billno, cname, ordertype, sum(empdisc) as empdisc, sum(ttlsales) as ttlsales

              from (select branch.station, head.openby, head.docno, head.billnumber as billno, lahead.clientname as cname, head.ordertype,
              sum(si.empdisc) as empdisc,
              sum(stock.ext) as ttlsales

              from lahead
              left join lastock as stock on lahead.trno = stock.trno
              left join head on stock.trno = head.trno
              left join client on lahead.client = client.client
              join item on stock.itemid = item.itemid
              left join stockgrp_masterfile as stockgrp on item.groupid = stockgrp.stockgrp_id
              left join cntnum on lahead.trno = cntnum.trno
              left join branchstation as branch on head.station = branch.station
              join stockinfo as si on stock.trno = si.trno and si.line = stock.line

              where cntnum.bref in ('SJS', 'SRS') and cntnum.center = '$center' and date(lahead.dateid) between '$start' and '$end'
              $filter

              group by branch.station, head.openby, head.docno, head.billnumber, lahead.clientname, cntnum.bref, head.ordertype

              union all

              select branch.station, head.openby, head.docno, head.billnumber as billno, glhead.clientname as cname, head.ordertype,
              sum(si.empdisc) as empdisc,
              sum(stock.ext) as ttlsales

              from glhead
              left join glstock as stock on glhead.trno = stock.trno
              left join head on stock.trno = head.trno
              left join client on glhead.clientid = client.clientid
              join item on stock.itemid = item.itemid
              left join stockgrp_masterfile as stockgrp on item.groupid = stockgrp.stockgrp_id
              left join cntnum on glhead.trno = cntnum.trno
              left join branchstation as branch on head.station = branch.station
              join hstockinfo as si on stock.trno = si.trno and si.line = stock.line

              where cntnum.bref in ('SJS', 'SRS') and cntnum.center = '$center' and date(glhead.dateid) between '$start' and '$end'
              $filter

              group by branch.station, head.openby, head.docno, head.billnumber, glhead.clientname, cntnum.bref, head.ordertype) as x

              group by x.station, x.openby, x.docno, x.billno, x.cname, x.ordertype

              order by x.station, x.openby";
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created jan 7
  public function summarized_salesreport_csummaryqry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $doctype = $config['params']['dataparams']['posdoctype'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and c.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and item.brand = $brandid";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and item.groupid = $groupid";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = $partid";
      $leftjoin .= " join item on stock.itemid = item.itemid";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
      $leftjoin .= " join item on stock.itemid = item.itemid";
    }
    if ($doctype != "") {
      $filter .= " and h.bref = '$doctype'";
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select c.client, lh.clientname, cnum.postdate,
              (case when cnum.bref='RT' then 'RETURN' when cnum.bref='CI' then 'CHARGE' else 'SALES' end) as type,
              h.docno, round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cnum.doc = 'CM', -1, 1)),2) as totalamt

              from lahead as lh
              left join lastock as stock on stock.trno = lh.trno
              left join head as h on h.webtrno = lh.trno and h.docno = stock.ref
              left join cntnum cnum on cnum.trno = lh.trno
              left join client c on c.client = lh.client
              left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line

              where cnum.bref in ('SJS','SRS') and cnum.center = '$center' and date(lh.dateid) between '$start' and '$end'
              $filter
              $leftjoin

              group by lh.clientname, c.client, cnum.trno, cnum.postdate, cnum.bref, h.docno

              union all

              select c.client, gl.clientname, cnum.postdate,
              (case when cnum.bref='RT' then 'RETURN' when cnum.bref='CI' then 'CHARGE' else 'SALES' end) as type,
              h.docno, round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cnum.doc = 'CM', -1, 1)),2) as totalamt

              from glhead as gl
              left join glstock as stock on stock.trno = gl.trno
              left join head as h on h.webtrno = gl.trno and h.docno = stock.ref
              left join cntnum cnum on cnum.trno = gl.trno
              left join client c on c.clientid = gl.clientid
              left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line

              where cnum.bref in ('SJS','SRS') and cnum.center = '$center' and date(gl.dateid) between '$start' and '$end'
              $filter
              $leftjoin

              group by gl.clientname, c.client, cnum.trno, cnum.postdate, cnum.bref, h.docno
              order by client, clientname, postdate asc
              ";
    // var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // created jan 13
  public function summarized_salesreport_bir_sales_qry($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date(
      "Y-m-d",
      strtotime($config['params']['dataparams']['start'])
    );
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];
    $doctype = $config['params']['dataparams']['posdoctype'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and c.clientid = '$clientid'";
    }
    if (
      $cashier != ""
    ) {
      $filter .= " and h.openby = '$cashier'";
    }
    if (
      $brandid != "0"
    ) {
      $filter .= " and item.brand = $brandid";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if (
      $groupid != "0"
    ) {
      $filter .= " and item.groupid = $groupid";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if (
      $partid != "0"
    ) {
      $filter .= " and item.part = $partid";
      $leftjoin .= " join item on stock.itemid = item.itemid";
    }
    if (
      $classid != "0"
    ) {
      $filter .= " and item.class=" . $classid;
      $leftjoin .= " join item on stock.itemid = item.itemid";
    }
    if (
      $doctype != ""
    ) {
      $filter .= " and h.bref = '$doctype'";
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select j.dateid, j.station, j.excessgc,
              (select h.docno from head h where left(h.docno,2)='SI' and h.station=j.station and h.dateid = j.dateid
              order by h.docno asc limit 1) as begin_si,
              (select h.docno from head h where left(h.docno,2)='SI' and h.station=j.station and h.dateid = j.dateid
              order by h.docno desc limit 1) as end_si,
              ifnull((select sum(jrnl.amt+jrnl.disc+jrnl.discsr+jrnl.pwdamt+jrnl.empdisc+jrnl.vipdisc+jrnl.oddisc+jrnl.smacdisc+jrnl.acdisc+jrnl.soloamt+jrnl.returnamt+jrnl.voidamt-jrnl.maxsales) from journal jrnl
              where jrnl.dateid<=date_sub(j.dateid,interval 1 day)),0) as startamt,
              ifnull((select sum(jrnl.amt+jrnl.disc+jrnl.discsr+jrnl.pwdamt+jrnl.empdisc+jrnl.vipdisc+jrnl.oddisc+jrnl.smacdisc+jrnl.acdisc+jrnl.soloamt+jrnl.returnamt+jrnl.voidamt-jrnl.maxsales) from journal jrnl
              where jrnl.dateid<=j.dateid),0) as endamt,


              j.discsr as sramt, j.pwdamt, j.acdisc, j.soloamt,
              j.valoramt, (j.disc+j.empdisc+j.vipdisc+j.oddisc+j.smacdisc) as otherdisc, j.returnamt as rt,
              j.voidamt void, (j.amt+j.disc+j.discsr+j.pwdamt+j.empdisc+j.vipdisc+j.oddisc+j.smacdisc+j.acdisc+j.soloamt) as compgross,
              j.gross, j.nvat, j.vatamt,
              if(vatex<>0,(vatex+discsr+pwdamt+soloamt),vatex) as vatex,

              ifnull((select sum(s.lessvat) from head as h left join hstockinfo as s on s.trno = h.trno
              left join cntnum as c on c.trno = h.trno where s.sramt <> 0 and s.lessvat > 0 and c.station = j.station
              and h.dateid = j.dateid),0) as adjscvat,

              ifnull((select sum(s.lessvat) from head as h left join hstockinfo as s on s.trno = h.trno
              left join cntnum as c on c.trno = h.trno where s.pwdamt <> 0 and s.lessvat > 0 and c.station = j.station
              and h.dateid = j.dateid),0) as adjpwdvat,

              ifnull((select sum(s.lessvat) from head as h left join hstockinfo as s on s.trno = h.trno
              left join cntnum as c on c.trno = h.trno where (s.pwdamt + s.pwdamt) <> 0 and s.lessvat > 0 and c.station = j.station
              and h.dateid = j.dateid),0) as adjotrvat,

              ifnull((select sum(s.lessvat) from head as h left join hstockinfo as s on s.trno = h.trno
              left join cntnum as c on c.trno = h.trno where s.lessvat < 0 and c.station = j.station
              and h.dateid = j.dateid),0) as adjrtvat,

              (select count(dateid) from journal where dateid <= j.dateid) as zcounter,
              (select pvalue from profile where doc='rx' and psection='tin' limit 1) as tin,
              (select pvalue from profile where doc='pos' and psection='swname' limit 1) as swname,
              (select pvalue from profile where doc='pos' and psection='min' limit 1) as min,
              (select pvalue from profile where doc='pos' and psection='sn' limit 1) as serial,
              0 as reset,
              0 as resetctr,

              ifnull((select sum(s.vatex) from hstockinfo as s left join glhead as h on h.trno=s.trno
              where h.dateid= j.dateid and s.isdiplomat = 1),0) as zerorated,

              ifnull((select sum(s.valoramt+s.acdisc) from hstockinfo as s left join glhead as h on h.trno=s.trno
              where h.dateid= j.dateid and s.vatex),0) as naacvalor

              from journal as j
              join (select distinct station from cntnum where center='$center') cnum
              on cnum.station = j.station and j.dateid between '$start' and '$end'

              group by j.dateid, j.station, j.excessgc, j.discsr, j.pwdamt, j.acdisc, j.soloamt, j.valoramt, j.disc, j.empdisc,
              j.vipdisc, j.oddisc, j.smacdisc, j.returnamt, j.voidamt, j.amt, j.gross, j.nvat, j.vatamt, j.vatex
              order by j.dateid, j.station, begin_si, end_si
              ";
    // var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // sr sales (create JAN 15 2026) query
  public function summarized_salesreport_sr_sales_query($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['customer'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $filter2 = '';
    $filter3 = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter2 .= " and c.clientid = '$clientid'";
    }
    if ($client != "0") {
      $filter3 .= " and c.client = '$client'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select sr.dateid, sr.clientname, sr.acctno, sr.tin, sr.docno, sum(gross) as gross, sum(sr.nvat) as nvat,
              sum(sr.vatamt + sr.lessvat) as vatamt, sum(sr.vatexmpt) as vatexmpt, sum(sr.sramt) as sramt, sum(sr.net) as net

              from (select h.dateid, c.clientname, h.acctno, h.docno, (stock.isamt * stock.isqty) as gross, si.nvat,
              si.vatamt, si.vatex, si.sramt, (si.sramt + si.vatex) as vatexmpt, ifnull(sum(si.lessvat),0) as lessvat,
              ifnull(station.tin, '') as tin, (stock.ext - si.lessvat + si.sramt + si.soloamt + si.pwdamt) as net

              from lahead as la
              left join lastock as stock on stock.trno = la.trno
              left join head as h on h.webtrno = la.trno and h.docno = stock.ref
              left join client as c on c.client = la.client
              left join cntnum as cnum on cnum.trno = la.trno
              left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
              left join branchstation as station on station.station = h.station

              where cnum.bref in ('SJS', 'SRS') and si.sramt <> 0 and cnum.center = '$center' and h.dateid between '$start' and '$end'
              $filter
              $filter3

              group by h.dateid, c.clientname, h.acctno, station.tin, h.docno, stock.isamt, stock.isqty, si.nvat,
              si.vatamt, si.vatex, si.sramt, stock.ext, si.lessvat, si.soloamt, si.pwdamt

              union all

              select h.dateid, c.clientname, h.acctno, h.docno, (stock.isamt * stock.isqty) as gross, si.nvat,
              si.vatamt, si.vatex, si.sramt, (si.sramt + si.vatex) as vatexmpt, ifnull(sum(si.lessvat),0) as lessvat,
              ifnull((station.tin), '') as tin, (stock.ext - si.lessvat + si.sramt + si.soloamt + si.pwdamt) as net

              from glhead as gl
              left join glstock as stock on stock.trno = gl.trno
              left join head as h on h.webtrno = gl.trno and h.docno = stock.ref
              left join client as c on c.clientid = gl.clientid
              left join cntnum as cnum on cnum.trno = gl.trno
              left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
              left join branchstation as station on station.station = h.station

              where cnum.bref in ('SJS', 'SRS') and si.sramt <> 0 and cnum.center = '$center' and h.dateid between '$start' and '$end'
              $filter
              $filter2

              group by h.dateid, c.clientname, h.acctno, station.tin, h.docno, stock.isamt, stock.isqty, si.nvat,
              si.vatamt, si.vatex, si.sramt, stock.ext, si.lessvat, si.soloamt, si.pwdamt) as sr

              group by sr.dateid, sr.clientname, sr.acctno, sr.tin, sr.docno;";
    // var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // sr sales (create JAN 15 2026) query
  public function summarized_salesreport_pwd_sales_query($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['customer'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $filter2 = '';
    $filter3 = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter2 .= " and c.clientid = '$clientid'";
    }
    if ($client != "0") {
      $filter3 .= " and c.client = '$client'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select pwd.dateid, pwd.clientname, pwd.acctno, pwd.tin, pwd.docno, sum(gross) as gross, sum(pwd.nvat) as nvat,
              sum(pwd.vatamt + pwd.lessvat) as vatamt, sum(pwd.vatexmpt) as vatexmpt, sum(pwd.pwdamt) as pwdamt, sum(pwd.net) as net

              from (select h.dateid, c.clientname, h.acctno, h.docno, (stock.isamt * stock.isqty) as gross, si.nvat,
              si.vatamt, si.vatex, si.pwdamt, (si.pwdamt + si.vatex) as vatexmpt, ifnull(sum(si.lessvat),0) as lessvat,
              ifnull(station.tin, '') as tin, (stock.ext - si.lessvat + si.sramt + si.soloamt + si.pwdamt) as net

              from lahead as la
              left join lastock as stock on stock.trno = la.trno
              left join head as h on h.webtrno = la.trno and h.docno = stock.ref
              left join client as c on c.client = la.client
              left join cntnum as cnum on cnum.trno = la.trno
              left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
              left join branchstation as station on station.station = h.station

              where cnum.bref in ('SJS', 'SRS') and si.pwdamt <> 0 and cnum.center = '$center' and h.dateid between '$start' and '$end'
              $filter
              $filter3
              group by h.dateid, c.clientname, h.acctno, station.tin, h.docno, stock.isamt, stock.isqty, si.nvat,
              si.vatamt, si.vatex, si.sramt, stock.ext, si.lessvat, si.soloamt, si.pwdamt

              union all

              select h.dateid, c.clientname, h.acctno, h.docno, (stock.isamt * stock.isqty) as gross, si.nvat,
              si.vatamt, si.vatex, si.sramt, (si.sramt + si.vatex) as vatexmpt, ifnull(sum(si.lessvat),0) as lessvat,
              ifnull((station.tin), '') as tin, (stock.ext - si.lessvat + si.sramt + si.soloamt + si.pwdamt) as net

              from glhead as gl
              left join glstock as stock on stock.trno = gl.trno
              left join head as h on h.webtrno = gl.trno and h.docno = stock.ref
              left join client as c on c.clientid = gl.clientid
              left join cntnum as cnum on cnum.trno = gl.trno
              left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
              left join branchstation as station on station.station = h.station

              where cnum.bref in ('SJS', 'SRS') and si.pwdamt <> 0 and cnum.center = '$center' and h.dateid between '$start' and '$end'
              $filter
              $filter2
              group by h.dateid, c.clientname, h.acctno, station.tin, h.docno, stock.isamt, stock.isqty, si.nvat,
              si.vatamt, si.vatex, si.sramt, stock.ext, si.lessvat, si.soloamt, si.pwdamt) as pwd

              group by pwd.dateid, pwd.clientname, pwd.acctno, pwd.tin, pwd.docno";
    // var_dump($query);
    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  // naac sales (create JAN 15 2026) query
  public function summarized_salesreport_naac_sales_query($config)
  {
    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select head.trno,date(head.dateid) as dateid, client.client, head.clientname, head.acctno, stock.ref as docno,
              sum(stock.isqty*stock.isamt) as gross,
              sum(info.acdisc) as salesdisc,SUM(stock.ext) AS amt

              from glhead as head 
              left join cntnum as num on num.trno = head.trno 
              left join client on client.clientid = head.clientid
              LEFT JOIN glstock AS stock ON stock.trno = head.trno
              JOIN hstockinfo AS info ON info.trno=stock.trno AND stock.line = info.line
              WHERE num.doc in ('SRS','SJ') and info.acdisc <> 0 AND head.clientname <> '' and num.center = $center and date(head.dateid) between '$start' and '$end' $filter
              GROUP BY head.docno,head.trno,head.clientname,head.acctno,head.dateid,client.client,stock.ref,info.acdisc
              union all
              select head.trno,date(head.dateid) as dateid, client.client, head.clientname, head.acctno, stock.ref as docno,
              sum(stock.isqty*stock.isamt) as gross,
              sum(info.acdisc) as salesdisc,SUM(stock.ext) AS amt

              from lahead as head 
              left join cntnum as num on num.trno = head.trno 
              left join client on client.client = head.client
              LEFT JOIN lastock AS stock ON stock.trno = head.trno
              JOIN stockinfo AS info ON info.trno=stock.trno AND stock.line = info.line
              WHERE num.doc in ('SRS','SJ') and info.acdisc <> 0 AND head.clientname <> '' and num.center = $center and date(head.dateid) between '$start' and '$end' $filter
              GROUP BY head.docno,head.trno,head.clientname,head.acctno,head.dateid,client.client,stock.ref,info.acdisc
              ORDER BY dateid,docno ";
    return $this->coreFunctions->opentable($query);
  }

  // medal of valor (create JAN 12 2026) query
  public function summarized_salesreport_medal_valorquery($config)
  {

    $center = $config['params']['dataparams']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $station = $config['params']['dataparams']['pos_station'];
    $stationname = $config['params']['dataparams']['stationname'];
    $clientid = $config['params']['dataparams']['clientid'];
    $cashier = $config['params']['dataparams']['lookup_cashier'];
    $brandid = $config['params']['dataparams']['brandid'];
    $groupid = $config['params']['dataparams']['groupid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $partid = $config['params']['dataparams']['partid'];
    $classid = $config['params']['dataparams']['classid'];
    $classname = $config['params']['dataparams']['classic'];

    $filter = '';
    $leftjoin = "";

    if ($station != "") {
      $filter .= " and cntnum.station = '$station'";
    }
    if ($clientid != "0") {
      $filter .= " and client.clientid = '$clientid'";
    }
    if ($cashier != "") {
      $filter .= " and h.openby = '$cashier'";
    }
    if ($brandid != "0") {
      $filter .= " and brand.brandid = '$brandid'";
      $leftjoin .= "left join frontend_ebrands as brands on item.brand = brands.brandid";
    }
    if ($groupid != "0") {
      $filter .= " and stockgrp.stockgrp_id = '$groupid'";
      $leftjoin .= " left join model_masterfile as modelgrp on modelgrp.model_id = item.model";
    }
    if ($partid != "0") {
      $filter .= " and item.part = '$partid'";
    }
    if ($classid != "0") {
      $filter .= " and item.class=" . $classid;
    }
    if ($paymentmode !== "") {
      $filter .= " and h.yourref = '$paymentmode'";
    }

    $query = "select head.trno,date(head.dateid) as dateid, client.client, head.clientname, head.acctno, stock.ref as docno,
              sum(stock.isqty*stock.isamt) as gross,
              sum(info.valoramt) as salesdisc,SUM(stock.ext) AS amt

              from glhead as head 
              left join cntnum as num on num.trno = head.trno 
              left join client on client.clientid = head.clientid
              LEFT JOIN glstock AS stock ON stock.trno = head.trno
              JOIN hstockinfo AS info ON info.trno=stock.trno AND stock.line = info.line
              WHERE num.doc in ('SRS','SJ') and info.valoramt <> 0 AND head.clientname <> '' and num.center = $center and date(head.dateid) between '$start' and '$end' $filter
              GROUP BY head.docno,head.trno,head.clientname,head.acctno,head.dateid,client.client,stock.ref,info.valoramt
              union all
              select head.trno,date(head.dateid) as dateid, client.client, head.clientname, head.acctno, stock.ref as docno,
              sum(stock.isqty*stock.isamt) as gross,
              sum(info.valoramt) as salesdisc,SUM(stock.ext) AS amt

              from lahead as head 
              left join cntnum as num on num.trno = head.trno 
              left join client on client.client = head.client
              LEFT JOIN lastock AS stock ON stock.trno = head.trno
              JOIN stockinfo AS info ON info.trno=stock.trno AND stock.line = info.line
              WHERE num.doc in ('SRS','SJ') and info.valoramt <> 0 AND head.clientname <> '' and num.center = $center and date(head.dateid) between '$start' and '$end' $filter
              GROUP BY head.docno,head.trno,head.clientname,head.acctno,head.dateid,client.client,stock.ref,info.valoramt
              ORDER BY dateid,docno ";
    return $this->coreFunctions->opentable($query);
  }

  public function summarized_salesreport_query($config)
  {
    // QUERY
    $center = $config['params']['dataparams']['center'];
    $start  = $config['params']['dataparams']['start'];
    $end    = $config['params']['dataparams']['end'];
    $prefix = $config['params']['dataparams']['prefix'];
    $filter = "";

    if ($prefix != '') {
      $filter = " and cntnum.doc in ('$prefix')";
    }

    $query = "select cntnum.doc,head.dateid,head.docno as hodocno,case cntnum.doc when 'CM' then stock.qty else 0 end as ret,
    case cntnum.doc when 'SJ' then stock.iss else 0 end as sold,0 as amt,
    case cntnum.doc when 'CM' then stock.ext*-1 else (case item.barcode when '' then stock.ext-1 else stock.ext end) end as ext,
    (si.sramt+si.pwdamt+si.discamt) as disc,si.nvat,si.vatex,si.lessvat,stock.isamt,stock.isqty,si.vatamt,
    concat(left(stock.ref,3),right(stock.ref,6)) as docno,head.yourref,head.dateid,'' as begsi,'' as endsi,
    0 as cash,0 as cheque,0 as card,0 as lp,0 as voucher,0 as debit,0 as smac,0 as eplus,
    0 as onlinedeals,0 as amt,br.clientname as branch,head.clientname,client.client,cntnum.station
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
    left join item on item.itemid=stock.itemid
    left join cntnum on cntnum.trno=head.trno
    left join client as br on br.clientid=head.branch
    left join client on client.clientid=head.clientid
    where 1=1 " . $filter . " and left(cntnum.bref,3) in ('SJS','SRS') and head.dateid between '$start' and '$end'
    group by  cntnum.doc,head.dateid,head.docno,stock.qty,stock.iss,stock.ext,stock.ext,item.barcode,si.sramt,si.pwdamt,si.discamt,
    si.nvat,si.vatex,si.lessvat,stock.isamt,stock.isqty,si.vatamt,head.yourref,head.dateid,
    br.clientname,head.clientname,head.branch,head.clientname,stock.ref,stock.line,client.client,cntnum.station
    order by branch,dateid,stock.ref";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $data;
  }

  public function summarized_salesreport_lastquery($config)
  {

    $center = $config['params']['dataparams']['center'];
    // QUERY
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $prefix    = $config['params']['dataparams']['prefix'];
    $filter   = "";

    $query = "select sum(cash) as cash,sum(cheque) as cheque,sum(card) as card,sum(lp) as lp,sum(voucher) as voucher,
    sum(debit) as debit,sum(smac) as smac,sum(eplus) as eplus,sum(onlinedeals) as onlinedeals,sum(amt) as amt,begsi,endsi from (
      
       select sum(cash) as cash,sum(cheque) as cheque,sum(card) as card,sum(lp) as lp,sum(voucher) as voucher,
    sum(debit) as debit,sum(smac) as smac,sum(eplus) as eplus,sum(onlinedeals) as onlinedeals,sum(amt) as amt,
    (select docno from head as h where dateid between '$start ' and '$end' and doc='BP' and bref='SI' and h.branch = head.branch order by docno limit 1) as begsi,
    (select docno from head as h where dateid between '$start ' and '$end' and doc='BP' and bref ='SI' and h.branch = head.branch order by docno desc limit 1) as endsi
    from head where dateid between '$start ' and '$end' and doc='BP' group by begsi,endsi
    union all
    select sum(cash) as cash,sum(cheque) as cheque,sum(card) as card,sum(lp) as lp,sum(voucher) as voucher,
    sum(debit) as debit,sum(smac) as smac,sum(eplus) as eplus,sum(onlinedeals) as onlinedeals,sum(amt) as amt,
    (select docno from hhead as h where dateid between '$start ' and '$end' and doc='BP' and bref='SI' and h.branch = head.branch order by docno limit 1) as begsi,
    (select docno from hhead as h where dateid between '$start ' and '$end' and doc='BP' and bref ='SI' and h.branch = head.branch order by docno desc limit 1) as endsi
    from hhead as head where dateid between '$start ' and '$end' and doc='BP' group by begsi,endsi
    
    ) as A where begsi is not null
    group by begsi,endsi";

    $data = json_decode(json_encode($this->coreFunctions->opentable($query)));

    return $data;
  }


  private function summarized_salesreport_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    $data = $this->summarized_salesreport_query($config);
    $str = '';
    $layoutsize = '1400';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
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
    $str .= $this->reporter->col('POS SALES SUMMARY ', '1000', null, false, $border, '', '', $font, '20', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('POS STATION : ', '1000', null, false, $border, '', '', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['station']) ? $data[0]['station'] : ''), '26000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('' . date('m/d/Y', strtotime($start)) . '-' . date('m/d/Y', strtotime($end)), '1200', null, false, $border, '', '', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->pagenumber('Page', '200', null, false, $border, '', 'R', $font, $fontsizehead, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Document #', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Branch Name', '220', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '210', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('# of Items sold', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('# of Items return', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Sales', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Disc', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Net of Disc', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vatable Sales', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Exempt', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('12% Vat', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function summarized_salesreport_layout($config)
  {
    $data = $this->summarized_salesreport_query($config);
    $lastdata = $this->summarized_salesreport_lastquery($config);
    $this->reporter->linecounter = 0;
    $count = 35;
    $page = 35;
    $str = '';
    $layoutsize = '1400';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsize13 = 13;
    $border = "1px solid ";
    $vatex = 0;
    $disc = 0;
    $gr = 0;
    $totalsold = 0;
    $totalret = 0;
    $totalgr = 0;
    $totaldisc = 0;
    $totalsalesnetdisc = 0;
    $totalvatablesale = 0;
    $totalvatexempt = 0;
    $totalvat = 0;
    $totalcash = 0;
    $totalcard = 0;
    $totalcheque = 0;
    $totallp = 0;
    $totalvoucher = 0;
    $totaldebit = 0;
    $totalsmac = 0;
    $totaleplus = 0;
    $totalodeals = 0;
    $totalam = 0;
    $beg = "";
    $en = "";

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $this->reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;margin-left:10px;');
    $str .= $this->summarized_salesreport_header($config);

    for ($i = 0; $i < count($data); $i++) {
      $gr = $data[$i]['ext'] + $data[$i]['disc'];
      $str .= $this->reporter->addline(); // increment linecounter
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['dateid'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['hodocno'], '140', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['branch'], '220', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      if ($data[$i]['clientname'] == '') {
        $str .= $this->reporter->col($data[$i]['client'], '210', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data[$i]['clientname'], '210', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col(number_format($data[$i]['sold'], 2), '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ret'], 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(number_format($gr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['disc'] < 1 ? '-' : number_format($data[$i]['disc'], 2)), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['nvat'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['vatex'] < 1 ? '-' :  number_format($data[$i]['vatex'], 2)), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['vatamt'], 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');


      $str .= $this->reporter->endrow();

      $totalsold = $totalsold + $data[$i]['sold'];
      $totalret = $totalret + $data[$i]['ret'];
      $totalgr = $totalgr + $gr;
      $totaldisc =  $totaldisc + $data[$i]['disc'];
      $totalsalesnetdisc =  $totalsalesnetdisc + $data[$i]['ext'];
      $totalvatablesale =  $totalvatablesale + $data[$i]['nvat'];
      $totalvatexempt =  $totalvatexempt + $data[$i]['vatex'];
      $totalvat = $totalvat + $data[$i]['vatamt'];

      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->summarized_salesreport_header($config);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '210', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsold, 2), '110', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalret, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgr, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldisc, 2),  '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsalesnetdisc, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvatablesale, 2),  '100', null, false, $border, 't', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvatexempt, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvat, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    foreach ($lastdata as $key => $value) {

      $totalcash = $value->cash;
      $totalcard = $value->card;
      $totalcheque = $value->cheque;
      $totallp = $value->lp;
      $totalvoucher = $value->voucher;
      $totaldebit = $value->debit;
      $totalsmac = $value->smac;
      $totaleplus = $value->eplus;
      $totalodeals = $value->onlinedeals;
      $totalam = $value->amt;
      $beg = $value->begsi;
      $en = $value->endsi;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('BEGINNING SI :', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col($beg, '300', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('SALES SUMMARY', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '280', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('ENDING SI :', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col($en, '300', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Cash :', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcash, 2), '350', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Debit: ', '140', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldebit, 2), '280', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('# OF ITEMS SOLD :', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsold, 2), '300', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Card:', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcard, 2), '350', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total SMAC: ', '140', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsmac, 2), '280', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('# OF ITEMS RETURN  :', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalret, 2), '300', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Cheque  :', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcheque, 2), '350', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total E-PLUS: ', '140', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totaleplus, 2), '280', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Loyalty Points: ', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totallp, 2), '350', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Online Deals: ', '140', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalodeals, 2), '280', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Voucher:', '150', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvoucher, 2), '350', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col('Total Amount :', '140', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalam, 2), '280', null, false, $border, '', 'L', $font, '12px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }

  // DEC 01
  // Summarized Sales Standard Header
  public function summarized_salesreport_standardheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $gnrtdate = date('m-d-Y H:i:s A');
    // $system = '';
    $srno = '';
    $machineid = '';
    $postrmnl = '';
    // $username = '';

    $str = '';
    $layoutsize = '1200';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";
    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '3px solid', '', 'C', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('VAT REG TIN:' . $headerdata[0]->tin, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Summarized Sales Report', null, null, false, $border, '', 'C', $font, '12', 'B', 'blue', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATION', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CASHIER', '110', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '110', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '170', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '110', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Amount', '110', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less VAT', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SC/PWD/Solo Parent/NAAC', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Other Disc', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Service Charge', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total sales', '110', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  // SUMMARIZED SALES - STANDARD
  public function summarized_salesreport_standard($config)
  {
    $layoutsize = '1200';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    // $str = '';
    $data = $this->summarized_salesreport_standardqry($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }
    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_standardheader($config);

    // // computation of grand total
    $ttlgross = 0;
    $ttllessvat = 0;
    $ttldisc = 0;
    $ttlotherdisc = 0;
    $ttlsccharge = 0;
    $ttlsales = 0;

    $currentStation = null;
    $currentCashier = null;

    $subStationGross = 0;
    $subStationLessVat = 0;
    $subStationDisc = 0;
    $subStationOtherDisc = 0;
    $subStationSccharge = 0;
    $subStationTotal = 0;

    $subCashierGross = 0;
    $subCashierLessVat = 0;
    $subCashierDisc = 0;
    $subCashierOtherDisc = 0;
    $subCashierSccharge = 0;
    $subCashierTotal = 0;

    for ($i = 0; $i < count($data); $i++) {

      // Original values for display
      $station_raw = $data[$i]['station'];
      $cashier_raw = $data[$i]['cashier'];

      $plotStation = ($i === 0 || strtoupper($data[$i]['station']) !== strtoupper($data[$i - 1]['station'])) ? $data[$i]['station'] : '';

      $plotCashier = ($i === 0 || strtoupper($data[$i]['cashier']) !== strtoupper($data[$i - 1]['cashier'])) ? $data[$i]['cashier'] : '';


      // Normalize for grouping
      $station = strtoupper($station_raw);
      $cashier = strtoupper($cashier_raw);

      // cashier subtotal
      if ($currentCashier !== null && $currentCashier !== $cashier) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize);
        $str .= $this->reporter->col('', '180', null, false, $border, '', 'C', $font, $fontsize);
        $str .= $this->reporter->col("<b>(<i>{$currentCashier}</i>) subtotal</b>", '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subCashierGross != 0 ? number_format($subCashierGross, 2) : '-', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subCashierLessVat != 0 ? number_format($subCashierLessVat, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subCashierDisc != 0 ? number_format($subCashierDisc, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subCashierOtherDisc != 0 ? number_format($subCashierOtherDisc, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subCashierOtherDisc != 0 ? number_format($subCashierOtherDisc, 2) : '-', '100', null, false, $border, 'TB');
        $str .= $this->reporter->col($subCashierTotal != 0 ? number_format($subCashierTotal, 2) : '-', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // reset cashier subtotals
        $subCashierGross = $subCashierLessVat = $subCashierDisc = $subCashierOtherDisc = $subCashierOtherDisc =  $subCashierTotal = 0;
      }

      // station subtotal
      if ($currentStation !== null && $currentStation !== $station) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '');
        $str .= $this->reporter->col('', '180', null, false, $border, '');
        $str .= $this->reporter->col("<b>(<i>" . strtoupper($currentStation) . "</i>) subtotal</b>", '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subStationGross != 0 ? number_format($subStationGross, 2) : '-', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subStationLessVat != 0 ? number_format($subStationLessVat, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subStationDisc != 0 ? number_format($subStationDisc, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subStationOtherDisc != 0 ? number_format($subStationOtherDisc, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subStationSccharge != 0 ? number_format($subStationSccharge, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col($subStationTotal != 0 ? number_format($subStationTotal, 2) : '-', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // reset station subtotals
        $subStationGross = $subStationLessVat = $subStationDisc = $subStationOtherDisc = $subStationSccharge = $subStationTotal = 0;
      }

      // update group trackers
      $currentStation = $station;
      $currentCashier = $cashier;

      // report details
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($plotStation, '80', null, false, $border, '', 'L', $font, $fontsize, 'B');
      // $str .= $this->reporter->col($data[$i]['station'], '80', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($plotCashier, '110', null, false, $border, '', 'C', $font, $fontsize, 'B');
      $str .= $this->reporter->col($data[$i]['postdate'], '110', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['docno'], '170', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['customer'], '110', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['gross'] != 0 ? number_format($data[$i]['gross'], 2) : '-', '110', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['lessvat'] != 0 ? number_format($data[$i]['lessvat'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['disc'] != 0 ? number_format($data[$i]['disc'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['otherdisc'] != 0 ? number_format($data[$i]['otherdisc'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['sccharge'] != 0 ? number_format($data[$i]['sccharge'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col($data[$i]['totalsales'] != 0 ? number_format($data[$i]['totalsales'], 2) : '-', '110', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //get subtotals
      $subStationGross  += $data[$i]['gross'];
      $subStationLessVat += $data[$i]['lessvat'];
      $subStationDisc   += $data[$i]['disc'];
      $subStationOtherDisc   += $data[$i]['otherdisc'];
      $subStationSccharge   += $data[$i]['sccharge'];
      $subStationTotal  += $data[$i]['totalsales'];

      $subCashierGross  += $data[$i]['gross'];
      $subCashierLessVat += $data[$i]['lessvat'];
      $subCashierDisc   += $data[$i]['disc'];
      $subCashierOtherDisc   += $data[$i]['otherdisc'];
      $subCashierSccharge   += $data[$i]['sccharge'];
      $subCashierTotal  += $data[$i]['totalsales'];

      // grand totals
      $ttlgross += $data[$i]['gross'];
      $ttllessvat += $data[$i]['lessvat'];
      $ttldisc += $data[$i]['disc'];
      $ttlotherdisc += $data[$i]['otherdisc'];
      $ttlsccharge += $data[$i]['sccharge'];
      $ttlsales += $data[$i]['totalsales'];
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // FINAL CASHIER SUBTOTAL
    if ($currentCashier !== null) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col('', '180', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col("<b>(<i>{$currentCashier}</i>) subtotal</b>", '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subCashierGross != 0 ? number_format($subCashierGross, 2) : '-', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subCashierLessVat != 0 ? number_format($subCashierLessVat, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subCashierDisc != 0 ? number_format($subCashierDisc, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subCashierOtherDisc != 0 ? number_format($subCashierOtherDisc, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subCashierSccharge != 0 ? number_format($subCashierSccharge, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subCashierTotal != 0 ? number_format($subCashierTotal, 2) : '-', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    // FINAL STATION SUBTOTAL
    if ($currentStation !== null) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '');
      $str .= $this->reporter->col('', '180', null, false, $border, '');
      $str .= $this->reporter->col("<b>(<i>" . strtoupper($currentStation) . "</i>) subtotal</b>", '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subStationGross != 0 ? number_format($subStationGross, 2) : '-', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subStationLessVat != 0 ? number_format($subStationLessVat, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subStationDisc != 0 ? number_format($subStationDisc, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subStationOtherDisc != 0 ? number_format($subStationOtherDisc, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subStationSccharge != 0 ? number_format($subStationSccharge, 2) : '-', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col($subStationTotal != 0 ? number_format($subStationTotal, 2) : '-', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    // GRAND TOTAL
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[$i]['cname'], '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total:', '100', null, false, '2px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '170', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($ttlgross != 0 ? number_format($ttlgross, 2) : '-', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttllessvat != 0 ? number_format($ttllessvat, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttldisc != 0 ? number_format($ttldisc, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlotherdisc != 0 ? number_format($ttlotherdisc, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlsccharge != 0 ? number_format($ttlsccharge, 2) : '-', '100', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlsales != 0 ? number_format($ttlsales, 2) : '-', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid', 'TB', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px solid', 'TB', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by:', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Received by:', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approved by:', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // dec 9 
  // QSR FINE Header
  public function summarized_salesreport_qsrfineheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $brandname = $config['params']['dataparams']['brandname'];
    // $doc = $config['params']['dataparams']['docno'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->name, null, '70', false, '3px solid', '', 'C', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES SUMMARY REPORT - QSR/FINEDINE', null, null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($brandname == '') {
      $str .= $this->reporter->col('<b>BRAND: </b>' . 'ALL BRAND', '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>BRAND: </b>' . strtoupper($brandname), '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>DOC: </b>', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE COVERED: ' . $start . ' to ' . $end, null, null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, '10', false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '150', null, false, '3px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Bill Number', '140', null, false, '3px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '160', null, false, '3px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ORDER TYPE', '140', null, false, '3px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Emp. Disc.', '140', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL SALES', '150', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  // SUMMARIZED SALES - QSR/FINE
  public function summarized_salesreport_qsrfine($config)
  {
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $data = $this->summerized_salesreport_qsrfineqry($config);

    // if (empty($data)) {
    //   return $this->othersClass->emptydata($config);
    // }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_qsrfineheader($config);

    $currentStation = null;
    $currentCashier = null;

    // for counting the dine in and take out
    // for cashier
    $subCashierDineIn = 0;
    $subCashierTakeOut = 0;

    // for station
    $subStationDineIn = 0;
    $subStationTakeOut = 0;

    // for subtotal of cashier and station
    $subStationTotal = 0;
    $subCashierTotal = 0;

    $grandtotal = 0;

    for ($i = 0; $i < count($data); $i++) {
      $station  = strtoupper($data[$i]['station']);
      $cashier  = strtoupper($data[$i]['openby']);

      // cashier subtotal
      if ($currentCashier !== null && $currentCashier !== $cashier) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T');
        $str .= $this->reporter->col("<b><i>({$currentCashier})</i></b>" . '<b>Subtotal</b>', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-in: ' . $subCashierDineIn, '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Take-Out: ' . $subCashierTakeOut, '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($subCashierTotal, 2), '250', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // reset cashier subtotals
        $subCashierTotal = 0;
        $subCashierDineIn = 0;
        $subCashierTakeOut = 0;
      }

      // station subtotal
      if ($currentStation !== null && $currentStation !== $station) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '');
        $str .= $this->reporter->col("<b><i>({$currentStation})</i></b>" . '<b>Subtotal</b>', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Dine-in: ' . $subStationDineIn, '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Take-Out: ' . $subStationTakeOut, '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($subStationTotal, 2), '250', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // reset station subtotals
        $subStationTotal = 0;
        $subStationDineIn = 0;
        $subStationTakeOut = 0;
      }

      // print station header
      if ($currentStation !== $station) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STATION: ' . $data[$i]['station'], '', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $currentStation = $station;
        $currentCashier = null; // reset cashier for each new station
      }

      // print cashier header
      if ($currentCashier !== $cashier) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CASHIER: ' . $data[$i]['openby'], '', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $currentCashier = $cashier;
      }

      // update group trackers
      $currentStation = $station;
      $currentCashier = $cashier;

      // setting the text value of order type
      $ordertype_val = $data[$i]['ordertype'];

      if ($ordertype_val === "0" || $ordertype_val === 0) {
        $ordertype_text = "DINE-IN";
      } elseif ($ordertype_val === "1" || $ordertype_val === 1) {
        $ordertype_text = "TAKE-OUT";
      } elseif ($ordertype_val === "2" || $ordertype_val === 2) {
        $ordertype_text = "DELIVERY";
      } else {
        $ordertype_text = ""; // blank if null or unknown
      }

      // for counting of DINE IN and TAKE OUT 
      if ($ordertype_text === "DINE-IN") {
        $subCashierDineIn++;
        $subStationDineIn++;
      } elseif ($ordertype_text === "TAKE-OUT") {
        $subCashierTakeOut++;
        $subStationTakeOut++;
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '120', '10', false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['docno'], '150', '10', false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['billno'], '140', '10', false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['cname'], '160', '10', false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ordertype_text, '140', '10', false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['empdisc'], 2), '140', '10', false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ttlsales'], 2), '150', '10', false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // sub totals for cashier and station
      $subStationTotal  += $data[$i]['ttlsales'];
      $subCashierTotal  += $data[$i]['ttlsales'];

      $grandtotal += $data[$i]['ttlsales'];
    }

    // FINAL CASHIER SUBTOTAL
    if ($currentCashier !== null) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, $border, 'T');
      $str .= $this->reporter->col("<b><i>({$currentCashier})</i></b>" . '<b>Subtotal</b>', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-in: ' . $subCashierDineIn, '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Take-Out: ' . $subCashierTakeOut, '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col(number_format($subCashierTotal, 2), '250', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    // FINAL STATION SUBTOTAL
    if ($currentStation !== null) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, $border, '');
      $str .= $this->reporter->col("<b><i>({$currentStation})</i></b>" . '<b>Subtotal</b>', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Dine-in: ' . $subStationDineIn, '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col('Take-Out: ' . $subStationTakeOut, '250', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col(number_format($subStationTotal, 2), '250', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total:', '500', '10', false, '2px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '500', '10', false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '2.5px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '2.5px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '25', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prepared by:', '125', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Checked by:', '125', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approved by:', '125', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Posted by:', '125', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '35', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '3px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '3px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '115', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '3px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '135', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '3px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // dec3
  // Summarized Sales Daily Sales Header
  public function summarized_salesreport_dailysalesheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    // $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    // $gnrtdate = date('m-d-Y H:i:s A');

    $str = '';
    $layoutsize = '800';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";
    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES SUMMARY - SALES FOR THE DAY', null, null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . '  to  ' . $end, null, null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, '10', false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, '2px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Beginning SI', '130', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ending SI', '130', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Sales', '110', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount', '100', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SC/Solo/Pwd Discount', '100', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales For the Day', '130', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  // SUMMARIZED SALES - DAILY SALES
  public function summarized_salesreport_dailysales($config)
  {
    $str = '';
    $layoutsize = '800';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_dailysalesheader($config);
    $data = $this->summarized_salesreport_dailysalesqry($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $station = '';

    for ($i = 0; $i < count($data); $i++) {

      // Detect station change
      $plotStation = ($i === 0 || $data[$i]['station'] !== $data[$i - 1]['station']) ? $data[$i]['station'] : '';
      if ($plotStation !== '') {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($plotStation, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['begin_si'], '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['end_si'], '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['gross'] == 0 ? '-' : number_format($data[$i]['gross'], 2)), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['disc'] == 0 ? '-' : number_format($data[$i]['disc'], 2)), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['srdisc'] == 0 ? '-' : number_format($data[$i]['srdisc'], 2)), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['sperday'] == 0 ? '-' : number_format($data[$i]['sperday'], 2)), '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by:', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Received by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approved by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '105', null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '175', null, false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // DEC4 
  // SUMMARIZED INGREDIENT CONSUMPTION
  public function summarized_salesreport_ingredientheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $gnrtdate = date('m-d-Y H:i:s A');

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";
    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);


    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    // // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '3px solid', '', 'C', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INGREDIENT CONSUMPTION', null, '20', false, $border, '', 'L', $font, '14', 'B', '', '');
    $str .= $this->reporter->col('Print Date:' . $gnrtdate, null, '20', false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . 'TO' . $end, null, '20', false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', null, '20', false, $border, '', 'R', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, '20', false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, '20', false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '140', null, false, '2px dotted', 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('NAME', '330', null, false, '2px dotted', 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('COST', '120', null, false, '2px dotted', 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('QTY', '120', null, false, '2px dotted', 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('UOM', '120', null, false, '2px dotted', 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '120', null, false, '2px dotted', 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  // SUMMARIZED INGREDIENT CONSUMPTION
  public function summarized_salesreport_ingredient($config)
  {
    $str = '';
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_ingredientheader($config);
    $data = $this->summarized_salesreport_ingredientqry($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $grandtotal = 0;

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['code'], '150', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['name'], '330', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['cost'] == 0 ? '-' : number_format($data[$i]['cost'], 2)), '130', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['qty'] == 0 ? '-' : number_format($data[$i]['qty'], 2)), '130', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['uom'], '130', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data[$i]['total'] == 0 ? '-' : number_format($data[$i]['total'], 2)), '130', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $grandtotal += $data[$i]['total'];
    }

    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '3px solid', 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL:', '150', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '330', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '2px dotted', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '130', null, false, '2px dotted', '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // created dec 10 
  // CUSTOMER SUMMARY HEADER
  public function summarized_salesreport_cssummaryheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $gnrtdate = date('m-d-Y H:i:s A');

    $str = '';
    $layoutsize = '800';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";
    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER SALES SUMMARY REPORT', null, '30', false, $border, '14', 'C', 'Times New Roman', '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Start Date', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($start, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '500', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('End Date', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($end, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '500', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Print Date', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($gnrtdate, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '500', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  // CUSTOMER SUMMARY REPORT
  public function summarized_salesreport_cssummary($config)
  {
    $str = '';
    $layoutsize = '800';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_cssummaryheader($config);
    $data = $this->summarized_salesreport_csummaryqry($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $currentClient = null;
    $currentCustomer = null;
    $clientTotal = 0;
    $customerTotal = 0;

    for ($i = 0; $i < count($data); $i++) {

      // condition if client change
      if ($currentClient !== $data[$i]['client']) {

        // close previous CUSTOMER total
        if ($currentCustomer !== null) {
          $str .= $this->reporter->begintable($layoutsize)
            . $this->reporter->startrow()
            . $this->reporter->col('', '200', null, false, '2px solid', 'LB')
            . $this->reporter->col('', '200', null, false, '2px solid', 'LB')
            . $this->reporter->col('', '200', null, false, '2px solid', 'LBR')
            . $this->reporter->col($customerTotal ? number_format($customerTotal, 2) : '-', '200', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, 'B')
            . $this->reporter->endrow()
            . $this->reporter->endtable();
        }

        // close previous client total
        if ($currentClient !== null) {
          $str .= $this->reporter->begintable($layoutsize)
            . $this->reporter->startrow()
            . $this->reporter->col('', '200', null, false, '2px solid', '')
            . $this->reporter->col('', '200', null, false, '2px solid', '')
            . $this->reporter->col('TOTAL:', '200', null, false, '2px solid', '', 'L', $font, $fontsize, 'B')
            . $this->reporter->col($clientTotal ? number_format($clientTotal, 2) : '-', '200', null, false, '2px solid', '', 'R', $font, $fontsize, 'B')
            . $this->reporter->endrow()
            . $this->reporter->endtable();
        }

        // reset for new client
        $currentClient   = $data[$i]['client'];
        $currentCustomer = null;
        $clientTotal     = 0;
        $customerTotal   = 0;
      }

      // for clientname change
      if ($currentCustomer !== $data[$i]['clientname']) {

        if ($currentCustomer !== null) {
          $str .= $this->reporter->begintable($layoutsize)
            . $this->reporter->startrow()
            . $this->reporter->col('', '200', null, false, '2px solid', 'LB')
            . $this->reporter->col('', '200', null, false, '2px solid', 'LB')
            . $this->reporter->col('', '200', null, false, '2px solid', 'LBR')
            . $this->reporter->col($customerTotal ? number_format($customerTotal, 2) : '-', '200', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, 'B')
            . $this->reporter->endrow()
            . $this->reporter->endtable();
        }

        $currentCustomer = $data[$i]['clientname'];
        $customerTotal   = 0;

        // customer header
        $str .= $this->reporter->begintable($layoutsize)
          . $this->reporter->startrow()
          . $this->reporter->col('Customer Name:', '130', null, false, '2px solid', 'TBL', 'L', $font, $fontsize, 'B')
          . $this->reporter->col($data[$i]['client'] . ' - ' . $data[$i]['clientname'], '670', null, false, '2px solid', 'TRB', 'L', $font, $fontsize, 'B')
          . $this->reporter->endrow()
          . $this->reporter->endtable()

          . $this->reporter->begintable($layoutsize)
          . $this->reporter->startrow()
          . $this->reporter->col('Transaction Date', '200', null, false, '2px solid', 'TL', 'L', $font, $fontsize, 'B')
          . $this->reporter->col('Type', '200', null, false, '2px solid', 'TL', 'L', $font, $fontsize, 'B')
          . $this->reporter->col('Document No.', '200', null, false, '2px solid', 'TL', 'L', $font, $fontsize, 'B')
          . $this->reporter->col('Total Amount', '200', null, false, '2px solid', 'TLR', 'L', $font, $fontsize, 'B')
          . $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow()
        . $this->reporter->col(date('m-d-Y', strtotime($data[$i]['postdate'])), '200', null, false, '2px solid', 'L')
        . $this->reporter->col($data[$i]['type'], '200', null, false, '2px solid', 'L')
        . $this->reporter->col($data[$i]['docno'], '200', null, false, '2px solid', 'L')
        . $this->reporter->col($data[$i]['totalamt'] ? number_format($data[$i]['totalamt'], 2) : '-', '200', null, false, '2px solid', 'LR', 'R')
        . $this->reporter->endrow();

      $customerTotal += (float) $data[$i]['totalamt'];
      $clientTotal += (float) $data[$i]['totalamt'];
    }

    // for printing the last clientname total
    if ($currentCustomer !== null) {
      $str .= $this->reporter->begintable($layoutsize)
        . $this->reporter->startrow()
        . $this->reporter->col('', '200', null, false, '2px solid', 'LB')
        . $this->reporter->col('', '200', null, false, '2px solid', 'LB')
        . $this->reporter->col('', '200', null, false, '2px solid', 'LRB')
        . $this->reporter->col($customerTotal ? number_format($customerTotal, 2) : '-', '200', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, 'B')
        . $this->reporter->endrow()

        . $this->reporter->endtable();
    }

    // for printing the last client total
    if ($currentClient !== null) {
      $str .= $this->reporter->begintable($layoutsize)
        . $this->reporter->startrow()
        . $this->reporter->col('', '200', null, false, '2px solid', '')
        . $this->reporter->col('', '200', null, false, '2px solid', '')
        . $this->reporter->col('TOTAL:', '200', null, false, '2px solid', '', 'L', $font, $fontsize, 'B')
        . $this->reporter->col($clientTotal ? number_format($clientTotal, 2) : '-', '200', null, false, '2px solid', '', 'R', $font, $fontsize, 'B')
        . $this->reporter->endrow()
        . $this->reporter->endtable();
    }

    $str .= $this->reporter->endreport();
    return $str;
  }

  // SUMMARIZED SALES - COLLECTION HEADER
  public function summarized_salesreport_collectionheader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    // $str = $this->reporter->begintable($layoutsize);
    // $str = $this->reporter->startrow($layoutsize);
    // $str .= $this->reporter->letterhead($center, $username, $config);
    // $str = $this->reporter->begintable($layoutsize);
    // $str = $this->reporter->startrow($layoutsize);

    $brandname = $config['params']['dataparams']['brandname'];
    $doc = $config['params']['dataparams']['posdoctype'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, '70', false, '3px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>COLLECTION SALES SUMMARY REPORT FOR THE DATE COVERED: </b>' . $start . '<b> to </b>' . $end, null, '20', false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '240', '30', false, $border, '', 'C', $font, $fontsize, '', '', '');
    if ($brandname == '') {
      $str .= $this->reporter->col('<b>BRAND: </b>' . 'ALL BRAND', '240', '30', false, $border, '', 'R', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>BRAND: </b>' . strtoupper($brandname), '240', '30', false, $border, '', 'R', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '40', '30', false, $border, '', 'C', $font, $fontsize, '', '', '');
    if ($doc == '') {
      $str .= $this->reporter->col('Doc:  ALL DOC', '240', '30', false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('<b>Doc: </b>' . strtoupper($doc), '240', '30', false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '240', '30', false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Document #', '130', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Client', '120', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less Vat', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SC/Pwd/Solo Parent Discount', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Other Discount', '120', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '120', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  // SUMMARIZED SALES - COLLECTION
  public function summarized_salesreport_collection($config)
  {
    $str = '';
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_collectionheader($config);
    $data = $this->summarized_salesreport_collectionqry($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $totalamt = 0;
    $ttllessvat = 0;
    $ttlsrdisc = 0;
    $ttldisc = 0;
    $ttlotherdisc = 0;
    $grandtotal = 0;

    $ttlcash = 0;
    $ttlcard = 0;
    $ttlcheque = 0;
    $ttlcredit = 0;
    $ttllp = 0;
    $ttlvoucher = 0;
    $ttldebit = 0;
    $ttlsmac = 0;
    $ttleplus = 0;
    $ttlonlinedeals = 0;

    $currentStation = null;
    $currentCashier = null;
    $currentYourRef = null;

    for ($i = 0; $i < count($data); $i++) {
      // Normalize values
      $station  = strtoupper($data[$i]['station']);
      $cashier  = strtoupper($data[$i]['cashier']);
      $yourref  = strtoupper($data[$i]['yourref']);

      // print station header
      if ($currentStation !== $station) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STATION: ' . $data[$i]['station'], '', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $currentStation = $station;
        $currentCashier = null; // reset cashier for each new station
      }

      // print cashier header
      if ($currentCashier !== $cashier) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CASHIER: ' . $data[$i]['cashier'], '', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $currentCashier = $cashier;
      }

      // print yourref header
      if ($currentYourRef !== $yourref) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['yourref'], '', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $currentYourRef = $yourref;
      }

      // update group trackers
      $currentStation = $station;
      $currentCashier = $cashier;
      $currentYourRef = $yourref;

      // report details
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['postdate'], '110', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['docno'], '130', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['cname'], '120', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['isamt'] != 0 ? number_format($data[$i]['isamt'], 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['lessvat'] != 0 ? number_format($data[$i]['lessvat'], 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['srdisc'] != 0 ? number_format($data[$i]['srdisc'], 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['disc'] != 0 ? number_format($data[$i]['disc'], 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['otherdisc'] != 0 ? number_format($data[$i]['otherdisc'], 2) : '-', '120', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['ttlsales'] != 0 ? number_format($data[$i]['ttlsales'], 2) : '-', '120', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totalamt += $data[$i]['isamt'];
      $ttllessvat += $data[$i]['lessvat'];
      $ttlsrdisc += $data[$i]['srdisc'];
      $ttldisc += $data[$i]['disc'];
      $ttlotherdisc += $data[$i]['otherdisc'];
      $grandtotal += $data[$i]['ttlsales'];

      $ttlcash += $data[$i]['cash'];
      $ttlcard += $data[$i]['card'];
      $ttlcheque += $data[$i]['cheque'];
      $ttlcredit += $data[$i]['cr'];
      $ttllp += $data[$i]['lp'];
      $ttlvoucher += $data[$i]['voucher'];
      $ttldebit += $data[$i]['debit'];
      $ttlsmac += $data[$i]['smac'];
      $ttleplus += $data[$i]['eplus'];
      $ttlonlinedeals += $data[$i]['onlinedeals'];
    }

    $ttlamt = $ttlcash + $ttlcard + $ttlcheque + $ttlcredit + $ttllp + $ttlvoucher + $ttldebit + $ttlsmac + $ttleplus + $ttlonlinedeals;

    // $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '3px solid', 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total:', '110', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($totalamt != 0 ? number_format($totalamt, 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttllessvat != 0 ? number_format($ttllessvat, 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlsrdisc != 0 ? number_format($ttlsrdisc, 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttldisc != 0 ? number_format($ttldisc, 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlotherdisc != 0 ? number_format($ttlotherdisc, 2) : '-', '120', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($grandtotal != 0 ? number_format($grandtotal, 2) : '-', '120', null, false, '2px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Summary:', '200', '30', false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Cash:', '200', '30', false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlcash != 0 ? number_format($ttlcash, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Card:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlcard != 0 ? number_format($ttlcard, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Cheque:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlcheque != 0 ? number_format($ttlcheque, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Credit:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlcredit != 0 ? number_format($ttlcredit, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Loyalty Points:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttllp != 0 ? number_format($ttllp, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Voucher:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlvoucher != 0 ? number_format($ttlvoucher, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Debit:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttldebit != 0 ? number_format($ttldebit, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total SMAC:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlsmac != 0 ? number_format($ttlsmac, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total E-PLUS:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttleplus != 0 ? number_format($ttleplus, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Online Deals:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlonlinedeals != 0 ? number_format($ttlonlinedeals, 2) : '-', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Amount:', '200', '50', false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlamt != 0 ? number_format($ttlamt, 2) : '-', '200', '50', false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Received by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approved by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '95', null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '85', null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '175', null, false, '3px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // SUMMARIZED SALES - PLU W/ Components head - Edcell
  public function summarized_salesreport_plu_wcomponets_head($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];

    $brandname = $config['params']['dataparams']['brandname'];
    $partid = $config['params']['dataparams']['partid'];
    $paymentmode = $config['params']['dataparams']['tpayment'];
    $class = $config['params']['dataparams']['classic'];
    // $data = $this->summarized_salesreport_query($config);

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $gnrtdate = date('m-d-Y H:i:s A');
    // $system = '';
    $srno = '';
    $machineid = '';
    $postrmnl = '';
    // $username = '';

    $str = '';
    $layoutsize = '1000';
    $font = "Tahoma";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";


    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '3px solid', '', 'C', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('PLU REPORT FOR THE DATE COVERED: ', 370, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($start . ' to ' . $end, null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    if ($brandname == '') {
      $str .= $this->reporter->col('BRAND: ' . 'ALL Brands', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    } else {
      $str .= $this->reporter->col('BRAND: ' . $brandname, null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    if ($partid == '0') {
      $str .= $this->reporter->col('PART#: ' . 'ALL Parts', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    } else {
      $str .= $this->reporter->col('PART#: ' . $partid, null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    }
    //$str .= $this->reporter->col('PART#: ', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    if ($paymentmode == '') {
      $str .= $this->reporter->col('Payment: ' . 'ALL Payment', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Payment: ' . $paymentmode, null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    }
    //$str .= $this->reporter->col('Payment: ', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('Payment: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    if ($class == '') {
      $str .= $this->reporter->col('Class: ' . 'ALL Class', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Class: ' . $class, null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    }
    //$str .= $this->reporter->col('Class: ', null, null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('Payment: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Material Code', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Item Name', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Quantity', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Total', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');


    return $str;
  }

  // (create JAN 16 2026) SUMMARIZED SALES - PLU W/ Components - Edcell
  public function summarized_salesreport_plu_wcomponets($config)
  {
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Verdana";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 15;
    $page = 15;
    $totalqty = 0;


    $data = $this->summarized_salesreport_plu_wcomponets_query($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_plu_wcomponets_head($config);
    $str .= $this->reporter->endtable();


    $currentClass  = '';
    $subtotalqty   = 0;
    $grandTotalQty   = 0;
    $subtotalext   = 0;
    $grandTotalext   = 0;

    foreach ($data as $key => $value) {
      if ($currentClass !== '' && $currentClass !== $value->cl_name) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUBTOTAL:', '800', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotalqty), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotalext, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $subtotalqty = 0;
        $subtotalext = 0;
      }


      if ($currentClass !== $value->cl_name) {
        $currentClass = $value->cl_name;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($currentClass, '110', null, false, '2px solid', '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->barcode, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->part, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->itemname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->isqty), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->ext, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotalqty += $value->isqty;
      $grandTotalQty += $value->isqty;
      $subtotalext += $value->ext;
      $grandTotalext += $value->ext;
    }
    if ($currentClass !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUBTOTAL:', '800', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotalqty), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotalext, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL:', '800', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandTotalQty), '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandTotalext, 2), '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // SUMMARIZED SALES - PLU W/O HEADER - Jan 16
  public function summarized_salesreport_plu_wo_components_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
    $font = "Verdana";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    // $str = $this->reporter->begintable($layoutsize);
    // $str = $this->reporter->startrow($layoutsize);
    // $str .= $this->reporter->letterhead($center, $username, $config);
    // $str = $this->reporter->begintable($layoutsize);
    // $str = $this->reporter->startrow($layoutsize);

    $brandname = $config['params']['dataparams']['brandname'];
    $partid = $config['params']['dataparams']['partid'];
    $paymentmode = $config['params']['dataparams']['pospayment'];
    $class = $config['params']['dataparams']['classic'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, '20', false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>PLU REPORT FOR THE DATE COVERED: </b>' . $start . '<b> to </b>' . $end, null, '20', false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($brandname == '') {
      $str .= $this->reporter->col('<b>BRAND: </b>' . 'ALL BRAND', '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>BRAND: </b>' . strtoupper($brandname), '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();
    // for part
    $str .= $this->reporter->startrow();
    if ($partid == '0') {
      $str .= $this->reporter->col('<b>PART#: </b>' . 'ALL PARTS', '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>PART#: </b>' . strtoupper($partid), '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();
    // for payment
    $str .= $this->reporter->startrow();
    if ($paymentmode == '') {
      $str .= $this->reporter->col('<b>Payment: </b>' . 'ALL PAYMENTS', '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>Payment: </b>' . strtoupper($paymentmode), '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();
    // class
    $str .= $this->reporter->startrow();
    if ($class == '') {
      $str .= $this->reporter->col('<b>Class: </b>' . 'ALL CLASS', '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>Class: </b>' . strtoupper($class), '250', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '150', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '400', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quantity', '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total', '150', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  // SUMMARIZED SALES - PLU W/O Components - Jan 16
  public function summarized_salesreport_plu_wo_components($config)
  {
    $str = '';
    $layoutsize = '800';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_plu_wo_components_header($config);
    $data = $this->summarized_salesreport_plu_wo_components_qry($config);

    // if (empty($data)) {
    //   return $this->othersClass->emptydata($config);
    // }

    $qtyTotal = 0;
    $extTotal = 0;

    $qtySubTotal = 0;
    $extSubtotal = 0;

    $currentClass = '';

    for ($i = 0; $i < count($data); $i++) {
      // Normalize values
      $class  = strtoupper($data[$i]['cl_name']);

      // // print class header
      if ($currentClass !== $class) {

        // cashier subtotal
        if (
          $currentClass !== null && $currentClass !== $class
        ) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Subtotal Quantity:', '300', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '250', null, false, '1px solid', 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($qtySubTotal != 0 ? number_format($qtySubTotal, 2) : '-', '100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($extSubtotal != 0 ? number_format($extSubtotal, 2) : '-', '150', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          // reset cashier subtotals
          $qtySubTotal = $extSubtotal = 0;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['cl_name'], '', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $currentClass = $class;
      }

      // report details
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '400', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['qty'] != 0 ? number_format($data[$i]['qty'], 2) : '-', '100', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '150', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
      // $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $qtySubTotal += $data[$i]['qty'];
      $extSubtotal += $data[$i]['ext'];

      $qtyTotal += $data[$i]['qty'];
      $extTotal += $data[$i]['ext'];
    }

    // print subtotal for the LAST class after the loop
    if ($currentClass !== null) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Subtotal Quantity:', '300', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, '1px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($qtySubTotal != 0 ? number_format($qtySubTotal, 2) : '-', '100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($extSubtotal != 0 ? number_format($extSubtotal, 2) : '-', '150', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total Items:', '300', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, '1px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($qtyTotal != 0 ? number_format($qtyTotal, 2) : '', '100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($extTotal != 0 ? number_format($extTotal, 2) : '', '140', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $topBorder = ($extTotal != 0) ? 'T' : '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, '1px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', $topBorder, 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, '1px solid', $topBorder, 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, '1px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid', $topBorder, 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, '2px solid', $topBorder, 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Received by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Approved by:', '250', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '95', null, false, '1px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, '1px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '85', null, false, '1px solid', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '175', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // NOV 27
  // customer sales header
  public function summarized_salesreport_csheader($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    $str = '';

    $font = "Times New Roman";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    //main header
    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER SALES SUMMARY', null, null, false, '3px solid', 'TLR', 'C', $font, '16', 'B', '', '');
    // $str .= $this->reporter->letterhead($center);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '3px solid', 'LR', 'C', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '', '50', false, '3px solid', 'LR', 'C', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: <u>' . date('Y-m-d', strtotime($start)) . '<u/>' . ' - ' . '<u>' . date('Y-m-d', strtotime($end)) . '<u/>', null, null, false, '3px solid', 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', null, false, '2px dotted', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('GROSS', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('SC/Pwd/Solo', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('DISCOUNT', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('TAX', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('NON-TAX', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('VOID', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('RETURNS', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, '', '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('NET', '90', null, false, '2px dotted', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  //summarized customer sales
  public function summarized_salesreport_customer_sales($config)
  {
    $str = '';
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Times New Roman";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str = '';
    // $this->reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_salesreport_csheader($config, $layoutsize);
    $data = $this->summarized_salesreport_clsalesquery($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    // for member type
    $currentType = null;
    $prevType = null;

    $subGross = $subDisc = $subDiscAmt = $subTax = $subNTax = $subVoid = $subRtn = $subNet = 0;

    // computation of grand total
    $ttlgross = 0;
    $ttldisc = 0;
    $ttldiscount = 0;
    $ttltax = 0;
    $ttlntax = 0;
    $ttlvoid = 0;
    $ttlrtn = 0;
    $ttlnet = 0;

    for ($i = 0; $i < count($data); $i++) {
      // destinguish the member type
      $type = ($data[$i]['client'] !== 'WALK-IN') ? 'MEMBER' : 'NON-MEMBER';

      $plotType = ($i === 0 || $type !== ($data[$i - 1]['client'] !== 'WALK-IN' ? 'MEMBER' : 'NON-MEMBER')) ? '<u><b>' . $type . '</b></u><br>' : '';

      if ($currentType !== null && $currentType !== $type) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subGross != 0 ? number_format($subGross, 2) : '-', '100', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subDisc != 0 ? number_format($subDisc, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subDiscAmt != 0 ? number_format($subDiscAmt, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subTax != 0 ? number_format($subTax, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subNTax != 0 ? number_format($subNTax, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subVoid != 0 ? number_format($subVoid, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subRtn != 0 ? number_format($subRtn, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subNet != 0 ? number_format($subNet, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // reset subtotals
        $subGross = $subDisc = $subDiscAmt = $subTax =
          $subNTax = $subVoid = $subRtn = $subNet = 0;
      }

      // report data plotting 
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($plotType . $data[$i]['cname'], '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
      $str .= $this->reporter->col($data[$i]['gross'] != 0 ? number_format($data[$i]['gross'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['disc'] != 0 ? number_format($data[$i]['disc'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['discamt'] != 0 ? number_format($data[$i]['discamt'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['tax'] != 0 ? number_format($data[$i]['tax'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['ntax'] != 0 ? number_format($data[$i]['ntax'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['void'] != 0 ? number_format($data[$i]['void'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['rtn'] != 0 ? number_format($data[$i]['rtn'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['net'] != 0 ? number_format($data[$i]['net'], 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // member subtotals
      $subGross   += $data[$i]['gross'];
      $subDisc    += $data[$i]['disc'];
      $subDiscAmt += $data[$i]['discamt'];
      $subTax     += $data[$i]['tax'];
      $subNTax    += $data[$i]['ntax'];
      $subVoid    += $data[$i]['void'];
      $subRtn     += $data[$i]['rtn'];
      $subNet     += $data[$i]['net'];

      $currentType = $type;

      // grandtotals
      $ttlgross += $data[$i]['gross'];
      $ttldisc += $data[$i]['disc'];
      $ttldiscount += $data[$i]['discamt'];
      $ttltax += $data[$i]['tax'];
      $ttlntax += $data[$i]['ntax'];
      $ttlvoid += $data[$i]['void'];
      $ttlrtn += $data[$i]['rtn'];
      $ttlnet += $data[$i]['net'];
    }

    // final member subtotals
    if ($currentType !== null) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($subGross != 0 ? number_format($subGross, 2) : '-', '100', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subDisc != 0 ? number_format($subDisc, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subDiscAmt != 0 ? number_format($subDiscAmt, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subTax != 0 ? number_format($subTax, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subNTax != 0 ? number_format($subNTax, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subVoid != 0 ? number_format($subVoid, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subRtn != 0 ? number_format($subRtn, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subNet != 0 ? number_format($subNet, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    //other details
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[$i]['cname'], '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '200', null, false, 'c solid', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlgross != 0 ? number_format($ttlgross, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttldisc != 0 ? number_format($ttldisc, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttldiscount != 0 ? number_format($ttldiscount, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttltax != 0 ? number_format($ttltax, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlntax != 0 ? number_format($ttlntax, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlvoid != 0 ? number_format($ttlvoid, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlrtn != 0 ? number_format($ttlrtn, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($ttlnet != 0 ? number_format($ttlnet, 2) : '-', '100', null, false, '3px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[$i]['cname'], '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[$i]['cname'], '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  // jan 12
  // summarized BIR Sales Summary
  public function summarized_salesreport_bir_sales_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
    $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));

    // if ($partid  == '0') {
    //   $str .= $this->reporter->col('PART:  ALL PART', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    // } else {
    //   $str .= $this->reporter->col('PART:  ' . strtoupper($part), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    // }

    $str = '';
    $layoutsize = '1300';
    $font = "calibri";
    $fontsize = "6";
    $fontsizehead = "6";
    $border = "1px solid ";
    $bg = '#808080';
    $bg2 = '#0BB5F4';
    $bg3 = '#FFFF00';
    $bg4 = '#FF9428';
    $bg5 = '#62C400';

    $bc = '#808080';
    $bc2 = '#0BB5F4';
    $bc3 = '#FFFF00';
    $bc4 = '#FF9428';
    $bc5 = '#62C400';

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $brandname = $config['params']['dataparams']['brandname'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, $fontsizehead, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->tin, null, null, false, '3px solid', '', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BIR SALES SUMMARY REPORT', null, null, false, '3px solid', '', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1300', null, false, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '65', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '65', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '55', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('', '55', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('', '40', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('', '55', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('', '40', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);
    $str .= $this->reporter->col('', '40', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);
    $str .= $this->reporter->col('', '30', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);
    $str .= $this->reporter->col('', '30', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);

    $str .= $this->reporter->col('', '40', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Deduction', '80', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '40', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '40', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');

    $str .= $this->reporter->col('', '36', null, $bg5, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '34', null, $bg5, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('Adjustment on Vat', '103', null, $bg5, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '37', null, $bg5, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');

    $str .= $this->reporter->col('', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '45', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '20', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '30', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Beginning SI No.', '65', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Ending SI No.', '65', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Grand Accum. Sales Ending Balance', '55', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('Grand Accum. Sales Beginning Balance', '55', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('Sales Issued w/ Manual SI/OR', '40', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('Gross Sales for the Day', '55', null, $bg2, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc2);
    $str .= $this->reporter->col('VATable Sales', '40', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);
    $str .= $this->reporter->col('VAT Amount', '40', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);
    $str .= $this->reporter->col('VAT Exempt Sales', '30', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);
    $str .= $this->reporter->col('Zero Rated Sales', '30', null, $bg3, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc3);

    $str .= $this->reporter->col('Adjustment on Vat', '200', null, $bg4, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '40', null, $bg4, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc4);
    $str .= $this->reporter->col('', '40', null, $bg4, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc4);
    $str .= $this->reporter->col('', '40', null, $bg4, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc4);

    $str .= $this->reporter->col('Discount', '110', null, $bg5, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc5);
    $str .= $this->reporter->col('', '35', null, $bg5, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc5);
    $str .= $this->reporter->col('', '30', null, $bg5, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc5);
    $str .= $this->reporter->col('', '35', null, $bg5, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc5);

    $str .= $this->reporter->col('VAT Payable', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Net Sales', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Sales Overrun / Overflow', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Other Income', '40', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Reset Counter', '30', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Z Counter', '30', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->col('Remarks', '35', null, $bg, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '40', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '65', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '65', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '55', null, $bg2, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '55', null, $bg2, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg2, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '55', null, $bg2, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg3, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg3, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, $bg3, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, $bg3, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SC', '40', null, $bg4, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PWD', '40', null, $bg4, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NAAC', '40', null, $bg4, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SOLO', '40', null, $bg4, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Others', '40', null, $bg4, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Returns', '40', null, $bg4, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc4);
    $str .= $this->reporter->col('Voids', '40', null, $bg4, '1px solid', 'b', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc4);
    $str .= $this->reporter->col('Total<br>Deduct', '40', null, $bg4, '1px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', $bc4);
    $str .= $this->reporter->col('SC', '36', null, $bg5, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PWD', '34', null, $bg5, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Others', '34', null, $bg5, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT on Returns', '37', null, $bg5, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Others', '32', null, $bg5, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total VAT adjust', '37', null, $bg5, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, $bg, '', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '35', null, $bg, '1px dotted', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1300', null, false, '1px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  //summarized customer sales
  public function summarized_salesreport_bir_sales($config)
  {
    $str = '';
    $layoutsize = '1300';
    $font = "calibri";
    $fontsize = "6";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str = '';
    $this->reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => $layoutsize];

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_salesreport_bir_sales_header($config);
    $data = $this->summarized_salesreport_bir_sales_qry($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    for ($i = 0; $i < count($data); $i++) {
      // for reset counter
      $resetctr = (string) $data[$i]['resetctr'];

      if ($resetctr == '0' || $resetctr === 0) {
        $resetctr = '-';
      } elseif (strlen($resetctr) == 1) {
        $resetctr = '00' . $resetctr;
      } elseif (strlen($resetctr) == 2) {
        $resetctr = '0' . $resetctr;
      }

      // report data plotting 
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '40', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', ''); //date
      $str .= $this->reporter->col($data[$i]['begin_si'], '65', null, false, '', '', 'L', $font, $fontsize, '', '', ''); //beginning si
      $str .= $this->reporter->col($data[$i]['end_si'], '65', null, false, '', '', 'L', $font, $fontsize, '', '', ''); //ending si
      $str .= $this->reporter->col($data[$i]['endamt'] != 0 ? number_format($data[$i]['endamt'], 2) : '-', '55', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', ''); // sales ending
      $str .= $this->reporter->col($data[$i]['startamt'] != 0 ? number_format($data[$i]['startamt'], 2) : '-', '55', null, false, '2px solid', '', 'R', $font, $fontsize, '', '', ''); // sales beginning
      $str .= $this->reporter->col('', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //sales issued w/ manual SI/OR
      $str .= $this->reporter->col(number_format((($data[$i]['gross'] == 0 ? $data[$i]['compgross'] : $data[$i]['gross']) + ($data[$i]['rt'] + $data[$i]['void'])), 2), '55', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //gross sales for the day
      $str .= $this->reporter->col($data[$i]['nvat'] != 0 ? number_format($data[$i]['nvat'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //vatable sales
      $str .= $this->reporter->col($data[$i]['vatamt'] != 0 ? number_format($data[$i]['vatamt'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //vat amount
      $str .= $this->reporter->col(((($data[$i]['vatex'] + $data[$i]['naacvalor']) - $data[$i]['zerorated']) != 0 ? number_format((($data[$i]['vatex'] + $data[$i]['naacvalor']) - $data[$i]['zerorated']), 2) : '-'), '30', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //vat exempt sales
      $str .= $this->reporter->col($data[$i]['zerorated'] != 0 ? number_format($data[$i]['zerorated'], 2) : '-', '30', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // zero rated sales
      $str .= $this->reporter->col($data[$i]['sramt'] != 0 ? number_format($data[$i]['sramt'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // SC
      $str .= $this->reporter->col($data[$i]['pwdamt'] != 0 ? number_format($data[$i]['pwdamt'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //PWD
      $str .= $this->reporter->col($data[$i]['acdisc'] != 0 ? number_format($data[$i]['acdisc'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //NAAC
      $str .= $this->reporter->col($data[$i]['soloamt'] != 0 ? number_format($data[$i]['soloamt'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //SOLO
      $str .= $this->reporter->col((($data[$i]['otherdisc'] + $data[$i]['valoramt']) != 0 ? number_format(($data[$i]['otherdisc'] + $data[$i]['valoramt']), 2) : '-'), '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //others
      $str .= $this->reporter->col($data[$i]['rt'] != 0 ? number_format($data[$i]['rt'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //return
      $str .= $this->reporter->col($data[$i]['void'] != 0 ? number_format($data[$i]['void'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //void
      $str .= $this->reporter->col((($data[$i]['sramt'] + $data[$i]['pwdamt'] + $data[$i]['acdisc'] + $data[$i]['soloamt'] + $data[$i]['otherdisc'] + $data[$i]['valoramt'] + $data[$i]['rt'] + $data[$i]['void']) != 0 ?
        number_format(($data[$i]['sramt'] + $data[$i]['pwdamt'] + $data[$i]['acdisc'] + $data[$i]['soloamt'] + $data[$i]['otherdisc'] + $data[$i]['valoramt'] + $data[$i]['rt'] + $data[$i]['void']), 2) : '-'), '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //total deduct
      $str .= $this->reporter->col($data[$i]['adjscvat'] != 0 ? number_format($data[$i]['adjscvat'], 2) : '-', '36', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //sc
      $str .= $this->reporter->col($data[$i]['adjpwdvat'] != 0 ? number_format($data[$i]['adjpwdvat'], 2) : '-', '34', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //pwd
      $str .= $this->reporter->col($data[$i]['adjotrvat'] != 0 ? number_format($data[$i]['adjotrvat'], 2) : '-', '34', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //others
      $str .= $this->reporter->col($data[$i]['adjrtvat'] != 0 ? number_format($data[$i]['adjrtvat'], 2) : '-', '37', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //vat on returns
      $str .= $this->reporter->col('-', '32', null, false, '', '', 'R', $font, $fontsize, '', '', ''); //others
      $str .= $this->reporter->col((($data[$i]['adjscvat'] + $data[$i]['adjpwdvat'] + $data[$i]['adjotrvat'] + $data[$i]['adjrtvat']) != 0 ?
        number_format(($data[$i]['adjscvat'] + $data[$i]['adjpwdvat'] + $data[$i]['adjotrvat'] + $data[$i]['adjrtvat']), 2) : '-'), '37', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // total val adjust
      $str .= $this->reporter->col('-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // vat payable
      $str .= $this->reporter->col((((($data[$i]['nvat'] + $data[$i]['vatex'] + $data[$i]['vatamt']) - ($data[$i]['sramt'] + $data[$i]['pwdamt'] + $data[$i]['acdisc'] + $data[$i]['soloamt'] + $data[$i]['valoramt'])) != 0 ?
        number_format((($data[$i]['nvat'] + $data[$i]['vatex'] + $data[$i]['vatamt']) - ($data[$i]['sramt'] + $data[$i]['pwdamt'] + $data[$i]['acdisc'] + $data[$i]['soloamt'] + $data[$i]['valoramt'])), 2) : '-')), '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // net sales
      $str .= $this->reporter->col('-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // sales overrun/overflow
      $str .= $this->reporter->col($data[$i]['excessgc'] != 0 ? number_format($data[$i]['excessgc'], 2) : '-', '40', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // other income
      $str .= $this->reporter->col($resetctr, '30', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // reset counter
      $str .= $this->reporter->col($data[$i]['zcounter'] != 0 ? number_format($data[$i]['zcounter'], 2) : '-', '30', null, false, '', '', 'R', $font, $fontsize, '', '', ''); // z counter
      $str .= $this->reporter->col('', '35', null, false, '1px dotted', 'B', 'R', $font, $fontsize, '', '', ''); // remarks
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->endreport();
    return $str;
  }

  // sr sales (create JAN 15 2026) header
  public function summarized_salesreport_sr_sales_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $gnrtdate = date('m-d-Y H:i:s A');
    // $system = '';
    $srno = '';
    $machineid = '';
    $postrmnl = '';
    // $username = '';

    $str = '';
    $layoutsize = '1000';
    $font = "Tahoma";
    $fontsize = "8";
    $fontsizehead = "10";
    $border = "1px solid ";
    $bg1 = "#808080";
    $bg2 = "#0BB5F4";
    $bg3 = "#FFFF00";
    $bg4 = "#8080FF";
    $bg5 = "#FFAE5E";
    $bg6 = "#63C600";
    $bg7 = "#FF6A22";
    $bg8 = "#CDCDCD";
    $bg9 = "#FFC993";

    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, $fontsizehead, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->tin, null, null, false, '3px solid', '', 'C', $font, $fontsizehead, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Senior Citizen Sales Book/Reposrt', null, null, false, '3px solid', '', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px solid', 'T', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '60', null, $bg1, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Name of Senior Citizen (SC)', '120', null, $bg2, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('OSCA ID No. / SC ID No.', '110', null, $bg3, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('SC Tin', '80', null, $bg4, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('SI Number', '90', null, $bg5, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Sales', '90', null, $bg6, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('VAT Amount', '90', null, $bg7, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('VAT Exempt Sales', '90', null, $bg8, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Discount Amount', '90', null, $bg9, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Net Sales', '90', null, $bg1, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px solid', 'T', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  // sr sales jan 15
  public function summarized_salesreport_sr_sales($config)
  {
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Tahoma";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 15;
    $page = 15;
    $totalSales = 0;
    $totalVatAmt = 0;
    $totalVatex = 0;
    $totalDiscAmt = 0;
    $totalNet = 0;

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_sr_sales_header($config);
    $data = $this->summarized_salesreport_sr_sales_query($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    for ($i = 0; $i < count($data); $i++) {
      //$str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['dateid'], '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['clientname'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['acctno'], '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['tin'], '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['docno'], '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['gross'] != 0 ? number_format($data[$i]['gross'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['vatamt'] != 0 ? number_format($data[$i]['vatamt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['vatexmpt'] != 0 ? number_format($data[$i]['vatexmpt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['sramt'] != 0 ? number_format($data[$i]['sramt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['net'] != 0 ? number_format($data[$i]['net'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totalSales += $data[$i]['gross'];
      $totalVatAmt += $data[$i]['vatamt'];
      $totalVatex += $data[$i]['vatexmpt'];
      $totalDiscAmt += $data[$i]['sramt'];
      $totalNet += $data[$i]['net'];
    } //end of for each

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '2px solid', 'B', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalSales != 0 ? number_format($totalSales, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalVatAmt != 0 ? number_format($totalVatAmt, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalVatex != 0 ? number_format($totalVatex, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalDiscAmt != 0 ? number_format($totalDiscAmt, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalNet != 0 ? number_format($totalNet, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  // pwd sales (create JAN 15 2026) header
  public function summarized_salesreport_pwd_sales_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $gnrtdate = date('m-d-Y H:i:s A');
    // $system = '';
    $srno = '';
    $machineid = '';
    $postrmnl = '';
    // $username = '';

    $str = '';
    $layoutsize = '1000';
    $font = "Tahoma";
    $fontsize = "8";
    $fontsizehead = "10";
    $border = "1px solid ";
    $bg1 = "#808080";
    $bg2 = "#0BB5F4";
    $bg3 = "#FFFF00";
    $bg4 = "#8080FF";
    $bg5 = "#FFAE5E";
    $bg6 = "#63C600";
    $bg7 = "#FF6A22";
    $bg8 = "#CDCDCD";
    $bg9 = "#FFC993";

    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, $fontsizehead, '', '', '');
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($headerdata[0]->tin, null, null, false, '3px solid', '', 'C', $font, $fontsizehead, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Person With Disability Sales Book/Reposrt', null, '40', false, '3px solid', '', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px solid', 'T', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '60', null, $bg1, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Name of Person with Disability(PWD)', '120', null, $bg2, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('PWD ID No.', '110', null, $bg3, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('PWD Tin', '80', null, $bg4, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('SI Number', '90', null, $bg5, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Sales', '90', null, $bg6, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('VAT Amount', '90', null, $bg7, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('VAT Exempt Sales', '90', null, $bg8, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Discount Amount', '90', null, $bg9, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Net Sales', '90', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px solid', 'T', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  // pwd sales jan 15
  public function summarized_salesreport_pwd_sales($config)
  {
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Tahoma";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 15;
    $page = 15;
    $totalSales = 0;
    $totalVatAmt = 0;
    $totalVatex = 0;
    $totalDiscAmt = 0;
    $totalNet = 0;

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_pwd_sales_header($config);
    $data = $this->summarized_salesreport_pwd_sales_query($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    for ($i = 0; $i < count($data); $i++) {
      //$str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['dateid'], '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['clientname'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['acctno'], '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['tin'], '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['docno'], '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['gross'] != 0 ? number_format($data[$i]['gross'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['vatamt'] != 0 ? number_format($data[$i]['vatamt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['vatexmpt'] != 0 ? number_format($data[$i]['vatexmpt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['pwdamt'] != 0 ? number_format($data[$i]['sramt'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data[$i]['net'] != 0 ? number_format($data[$i]['net'], 2) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totalSales += $data[$i]['gross'];
      $totalVatAmt += $data[$i]['vatamt'];
      $totalVatex += $data[$i]['vatexmpt'];
      $totalDiscAmt += $data[$i]['pwdamt'];
      $totalNet += $data[$i]['net'];
    } //end of for each

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', '20', false, '1px solid', 'B', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL:', '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalSales != 0 ? number_format($totalSales, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalVatAmt != 0 ? number_format($totalVatAmt, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalVatex != 0 ? number_format($totalVatex, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalDiscAmt != 0 ? number_format($totalDiscAmt, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->col($totalNet != 0 ? number_format($totalNet, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  // naac sales (create JAN 15 2026) header
  public function summarized_salesreport_naac_sales_head($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $gnrtdate = date('m-d-Y H:i:s A');
    // $system = '';
    $srno = '';
    $machineid = '';
    $postrmnl = '';
    // $username = '';

    $str = '';
    $layoutsize = '1200';
    $font = "Tahoma";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";
    $bg1 = "#808080";
    $bg2 = "#0BB5F4";
    $bg3 = "#FFFF00";
    $bg4 = "#FFAE5E";
    $bg5 = "#63C600";
    $bg6 = "#FFC993";

    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '3px solid', '', 'C', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('VAT REG TIN:' . $headerdata[0]->tin, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('National Athletes and Coaches Sales Book / Report', null, null, false, $border, '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '80', null, $bg1, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Name of National Athlete / Coach', '110', null, $bg2, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('PNSTM ID No.', '110', null, $bg3, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('SI Number', '170', null, $bg4, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Gross Sales / Receipts', '110', null, $bg5, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Discount Amount', '110', null, $bg6, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Net Sales', '100', null, $bg1, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function summarized_salesreport_naac_sales($config)
  {
    $layoutsize = '1200';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Tahoma";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 15;
    $page = 15;
    $totalprice = 0;
    $totalsalesdisc = 0;
    $totalgross = 0;

    $data = $this->summarized_salesreport_naac_sales_query($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_naac_sales_head($config);


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($data as $key => $value) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->dateid, '80', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->clientname, '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->acctno, '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->docno, '170', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->gross, 2), '110', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->salesdisc, 2), '110', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, 2), '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalprice = $totalprice + $value->amt;
      $totalsalesdisc = $totalsalesdisc + $value->salesdisc;
      $totalgross = $totalgross + $value->gross;
    } //end of for each

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgross, 2), '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsalesdisc, 2), '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalprice, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  // medal of valor (create JAN 13 2026) header
  public function summarized_salesreport_medal_valorhead($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $dcentername     = $config['params']['dataparams']['dcentername'];
    // $data = $this->summarized_salesreport_query($config);

    $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $gnrtdate = date('m-d-Y H:i:s A');
    // $system = '';
    $srno = '';
    $machineid = '';
    $postrmnl = '';
    // $username = '';

    $str = '';
    $layoutsize = '1200';
    $font = "Tahoma";
    $fontsize = "10";
    $fontsizehead = "10";
    $border = "1px solid ";
    $bg1 = "#808080";
    $bg2 = "#0BB5F4";
    $bg3 = "#FFFF00";
    $bg4 = "#FFAE5E";
    $bg5 = "#63C600";
    $bg6 = "#FFC993";

    if ($config['params']['dataparams']['dcentername'] == '') {
      $dcentername = '-';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '3px solid', '', 'C', $font, '14', 'B', '', '');
    // $str .= $this->reporter->col('from: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->col('VAT REG TIN:' . $headerdata[0]->tin, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col(($dcentername), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Medal of Valor Sales Book / Report', null, null, false, $border, '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '80', null, $bg1, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Name of Valor', '110', null, $bg2, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('PNSTM ID No.', '110', null, $bg3, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('SI Number', '170', null, $bg4, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Gross Sales / Receipts', '110', null, $bg5, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Discount Amount', '110', null, $bg6, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Net Sales', '100', null, $bg1, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function summarized_salesreport_medal_valor($config)
  {
    $layoutsize = '1200';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Tahoma";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 15;
    $page = 15;
    $totalprice = 0;
    $totalsalesdisc = 0;
    $totalgross = 0;

    $data = $this->summarized_salesreport_medal_valorquery($config);

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->summarized_salesreport_medal_valorhead($config);


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($data as $key => $value) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->dateid, '80', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->clientname, '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->acctno, '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->docno, '170', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->gross, 2), '110', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->salesdisc, 2), '110', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, 2), '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalprice = $totalprice + $value->amt;
      $totalsalesdisc = $totalsalesdisc + $value->salesdisc;
      $totalgross = $totalgross + $value->gross;
    } //end of for each

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalgross, 2), '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsalesdisc, 2), '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalprice, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class
