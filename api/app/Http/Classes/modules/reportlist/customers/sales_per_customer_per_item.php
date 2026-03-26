<?php

namespace App\Http\Classes\modules\reportlist\customers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class sales_per_customer_per_item
{
  public $modulename = 'Sales Per Customer Per Item';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $logger;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'categoryname', 'subcatname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname', 'industry');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        break;
      case 15: //nathina
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
          ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
          ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);
        break;
      case 21: //kinggeorge
        array_push($fields, 'divsion', 'dwhname');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        array_push($fields, 'dagentname', 'brand');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 36: //rozlab
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'customercategory', 'dagentname', 'dcentername', 'categoryname', 'subcatname'];
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    switch ($companyid) {
      case 19: // housegem
        $fields = ['print'];
        break;
      default:
        $fields = ['radiosalescustomerperitem', 'print'];
        break;
    }
    $col2 = $this->fieldClass->create($fields);

    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { // labsol cebu, labsol manila & technolab
      data_set($col2, 'radiosalescustomerperitem.options', [
        ['label' => 'Amount', 'value' => 'sales', 'color' => 'orange'],
        ['label' => 'Quantity', 'value' => 'qty', 'color' => 'orange'],
        ['label' => 'Both', 'value' => 'both', 'color' => 'orange']
      ]);
    }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    switch ($companyid) {
      case 15: //nathina
        $type = "PDFM";
        break;
      default:
        $type = "default";
        break;
    }

    $paramstr = "
    select '" . $type . "' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    'sales' as options,
    '' as dclientname,
    '' as categoryname,
    '' as category,
    '' as subcat,
    '' as subcat,
    '' as subcatname,
    '' as category_name,
    '0' as category_id,
    '' as agent,
    '' as agentid,
    '' as agentname,
    '' as dagentname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= " ,'' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'' as industry ";
        break;
      case 21: //kinggeorge
        $paramstr .= ",'' as divsion,'' as groupid,'' as stockgrp, '' as wh, '' as whname, '' as dwhname, '' as whid";
        break;
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $paramstr .= ",'' as brand, '' as brandid";
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
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', '-1');

    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $company = $config['params']['companyid'];

    switch ($company) {
      case 19:  //housegem
        $result = $this->housegem_layout($config);
        break;
      case 21: //kinggeorge
        $result = $this->kinggeorge_layout($config);
        break;
      case 32: //3m
        $result = $this->mmm_layout($config);
        break;
      case 1: //vitaline
        $result = $this->vitaline_layout($config);
        break;
      default: /* DEFAULT */
        if ($config['params']['dataparams']['print'] == 'PDFM') {
          $data = $this->reportDefault($config);
          $result = $this->default_PDF($config, $data);
        } else {
          $result = $this->reportDefaultLayout($config);
        }
        break;
    }

    return $result;
  }
  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $custid =  $config['params']['dataparams']['category_id']; // custumer category
    $agentid     = $config['params']['dataparams']['agentid'];

    $filter = "";

    if (
      $category != ""
    ) {
      $filter = $filter . " and item.category='$category'";
    }

    if (
      $subcatname != ""
    ) {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($custid != 0) {
      $filter = $filter . " and client.category='$custid'";
    }

    if ($agentid != 0) {
      $filter .= " and ag.clientid = '$agentid'";
    }

    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    } //end if

    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $prjid = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['ddeptname'];
        $project = $config['params']['dataparams']['projectid'];
        $indus = $config['params']['dataparams']['industry'];
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $config['params']['dataparams']['deptid'];
        }
        if ($prjid != "") {
          $filter1 .= " and stock.projectid = $project";
        }
        if ($deptid != "") {
          $filter1 .= " and head.deptid = $dept";
        }
        if ($indus != "") {
          $filter1 .= " and client.industry = '$indus'";
        }
        break;
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab

        $brand     = $config['params']['dataparams']['brand'];
        if (!empty($brand)) {
          $brandid     = $config['params']['dataparams']['brandid'];
          $filter1 .= " and item.brand = $brandid";
        }

        break;
    }

    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { // labsol cebu, labsol manila & technolab
      $opt = " sum(sales) as sales,sum(qty) as qty";
    } else {
      $opt = " sum($option) as sales";
    }

    $query = "select docno,client, clientname, barcode, itemname,$opt, amt as price,shipto,yourref, ourref,dateid,agentname
              from (select 'u' as tr, head.trno, head.doc, head.docno, head.client, head.clientname, item.barcode, 
                           item.itemname, stock.iss as qty, stock.amt, stock.ext as sales, head.shipto, yourref, ourref, head.dateid,ag.clientname as agentname
                    from lahead as head 
                    left join lastock as stock on stock.trno=head.trno 
                    left join client on client.client=head.client
                    left join item on item.itemid=stock.itemid 
                    left join cntnum on cntnum.trno=head.trno
                    left join client as ag on ag.client=head.agent
                    where head.doc in ('SJ','MJ','SD','SE','SF') $filter $filter1 
                    and date(head.dateid) between '$start' and '$end'
                    union all
                    select 'p' as tr, head.trno, head.doc, head.docno, 
                    client.client, head.clientname, item.barcode, 
                    item.itemname, stock.iss as qty, stock.amt, stock.ext as sales, head.shipto, yourref, ourref, head.dateid,ag.clientname as agentname
                    from glhead as head 
                    left join glstock as stock on stock.trno=head.trno 
                    left join client on client.clientid=head.clientid
                    left join item on item.itemid=stock.itemid 
                    left join cntnum on cntnum.trno=head.trno
                    left join client as ag on ag.clientid=head.agentid
                    where head.doc in ('SJ','MJ','SD','SE','SF') $filter $filter1 and item.isofficesupplies=0
                    and date(head.dateid) between '$start' and '$end') as sa
              group by client, clientname, barcode, itemname,amt,docno,shipto,yourref, ourref,dateid,agentname
              order by clientname, itemname";
    $this->othersClass->logConsole("==========");
    $this->othersClass->logConsole($query);

    return $this->coreFunctions->opentable($query);
  }

  public function mmm_query($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];


    $filter = "";

    if (
      $category != ""
    ) {
      $filter = $filter . " and item.category='$category'";
    }

    if (
      $subcatname != ""
    ) {
      $filter = $filter . " and item.subcat='$subcatname'";
    }


    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    } //end if

    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $query = "select docno,client, clientname, barcode, itemname, sum($option) as sales, amt as price,shipto,yourref, ourref, dateid, uom, brgy, area
              from (select 'u' as tr, head.trno, head.doc, head.docno, head.client, head.clientname, item.barcode, 
                           item.itemname, stock.isqty as qty, stock.amt*uom.factor as amt, stock.isamt as amtx, stock.ext as sales, head.shipto, yourref, ourref, head.dateid, stock.uom,
                    client.brgy, client.area
                    from lahead as head 
                    left join lastock as stock on stock.trno=head.trno 
                    left join client on client.client=head.client
                    left join item on item.itemid=stock.itemid 
                    left join cntnum on cntnum.trno=head.trno
                    left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
                    where head.doc in ('SJ','SD','SE','SF') $filter $filter1 
                    and date(head.dateid) between '$start' and '$end'
                    union all
                    select 'p' as tr, head.trno, head.doc, head.docno, 
                    client.client, head.clientname, item.barcode, 
                    item.itemname, stock.isqty as qty, stock.amt*uom.factor as amt,stock.isamt  as amtx, stock.ext as sales, head.shipto, yourref, ourref, head.dateid, stock.uom,
                    client.brgy, client.area
                    from glhead as head 
                    left join glstock as stock on stock.trno=head.trno 
                    left join client on client.clientid=head.clientid
                    left join item on item.itemid=stock.itemid 
                    left join cntnum on cntnum.trno=head.trno
                    left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
                    where head.doc in ('SJ','SD','SE','SF') $filter $filter1 and item.isofficesupplies=0
                    and date(head.dateid) between '$start' and '$end') as sa
              group by client, clientname, barcode, itemname,amt,docno,shipto,yourref, ourref,dateid, uom, brgy, area
              order by clientname, itemname";

    return $this->coreFunctions->opentable($query);
  }

  private function housegem_query($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $wh = isset($config['params']['dataparams']['wh']) ? $config['params']['dataparams']['wh'] : '';

    $filter = "";

    if (
      $category != ""
    ) {
      $filter = $filter . " and item.category='$category'";
    }

    if (
      $subcatname != ""
    ) {
      $filter = $filter . " and item.subcat='$subcatname'";
    }


    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    } //end if

    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }
    $filter1 .= "";

    $docfilter = "'SJ','SD','SE','SF', 'CM'";

    $qty = 'sum(stock.iss-stock.qty) as qty, ';

    if ($config['params']['companyid'] == 21) { //kinggeorge
      $docfilter = "'SJ','SD','SE','SF'";
      $qty = 'sum(stock.isqty-stock.qty) as qty, ';

      $groupid  = $config['params']['dataparams']['groupid'];
      $groupname =  $config['params']['dataparams']['stockgrp'];


      if ($groupid) {
        $filter = $filter . " and item.groupid='$groupid'";
      }
    }

    if ($wh != "") {
      $filter .= " and wh.client='$wh'";
    }

    $query = "select docno, client, clientname, barcode, itemname, sum(qty) as qty, sum(sales) as sales, isamt as price, dateid, terms, due, agentname, yourref, ourref, shipto, uom
    from (select 'u' as tr, head.trno, head.doc, head.docno, head.client, head.clientname, item.barcode, 
    item.itemname, 
    $qty
    stock.isamt, 
    sum(case when head.doc = 'CM' then (stock.ext*-1) else stock.ext end) as sales,
    date(head.dateid) as dateid, head.terms, head.due, ag.clientname as agentname, head.yourref, head.ourref, head.shipto, stock.uom
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno 
    left join client on client.client=head.client
    left join client as ag on ag.client = head.agent
    left join client as wh on wh.client = head.wh
    left join item on item.itemid=stock.itemid 
    left join cntnum on cntnum.trno=head.trno
    where head.doc in (" . $docfilter . ") $filter $filter1 
    and date(head.dateid) between '$start' and '$end'
    group by head.trno, head.doc, head.docno, head.client, head.clientname, item.barcode, item.itemname, stock.isamt, head.dateid, head.terms, head.due, ag.clientname, head.yourref, head.ourref, head.shipto, stock.uom
    union all
    select 'p' as tr, head.trno, head.doc, head.docno, 
    client.client, head.clientname, item.barcode, 
    item.itemname, 
    $qty
    stock.isamt,  sum(case when head.doc = 'CM' then (stock.ext*-1) else stock.ext end) as sales,
    date(head.dateid) as dateid, head.terms, head.due, ag.clientname as agentname, head.yourref, head.ourref, head.shipto, stock.uom
    from glhead as head 
    left join glstock as stock on stock.trno=head.trno 
    left join client on client.clientid=head.clientid
    left join client as ag on ag.clientid = head.agentid
    left join client as wh on wh.clientid = head.whid
    left join item on item.itemid=stock.itemid 
    left join cntnum on cntnum.trno=head.trno
    where head.doc in (" . $docfilter . ") $filter $filter1 and item.isofficesupplies=0
    and date(head.dateid) between '$start' and '$end'
    group by head.trno, head.doc, head.docno, client.client, head.clientname, item.barcode, item.itemname, stock.isamt, head.dateid, head.terms, head.due, ag.clientname, head.yourref, head.ourref, head.shipto, stock.uom
    ) as sa
    group by docno, client, clientname, barcode, itemname, isamt, dateid, terms, due, agentname, yourref, ourref, shipto, uom
    order by clientname asc, docno, itemname";


    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SALES PER CUSTOMER PER ITEM ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' - ' . $end, '200', null, false, $border, '', 'L', $font, '10', '', '', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer :' . $client, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($option) {
      $str .= $this->reporter->col('Option : AMOUNT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL',  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }


    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Industry : ' . $indus, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    switch ($companyid) {
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('DOCUMENT # ', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('AGENT NAME ', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM NAME ', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('CUSTOMER NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('DOCUMENT # ', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('DOC DATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM NAME ', '300', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
        break;
    }

    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');

    switch ($option) {
      case 'qty':
        $str .= $this->reporter->col('QUANTITY', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
      case 'sales':
        $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
      case 'both':
        $str .= $this->reporter->col('QUANTITY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function reportDefaultLayout($config)
  {
    //ini_set('memory_limit', '-1');
    $companylist = [36]; //rozlab
    $company   = $config['params']['companyid'];
    switch ($company) {
      case 19: //housegem
        $result  = $this->housegem_query($config);
        break;
      default:
        $result  = $this->reportDefault($config);
        break;
    }


    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;
    $subtotalqty = 0;
    $subtotalsales = 0;
    $remtotal = 0;
    $remtotalqty = 0;
    $remtotalsales = 0;

    $clientname = "";
    $i = 0;
    foreach ($result as $key => $data) {
      if ($clientname != '' && $clientname != ($data->clientname . ' ( ' . $data->client . ' )')) {
        SubtotalHere:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('SUBTOTAL:', '600', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, $font, $fontsize, 'B');

        if ($company == 23 || $company == 41 || $company == 52) { //labsol & technolab
          switch ($option) {
            case 'sales':
              $str .= $this->reporter->col(number_format($subtotalsales, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;
            case 'qty':
              $str .= $this->reporter->col(number_format($subtotalqty, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');

              break;
            case 'both':
              $str .= $this->reporter->col(number_format($subtotalqty, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($subtotalsales, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;
          }
        } else {
          $str .= $this->reporter->col(number_format($subtotal, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $subtotal = 0;
        $subtotal2 = 0;
        if ($i == (count((array)$result) - 1)) {
          break;
        }
        $str .= $this->reporter->addline();
        if (!in_array($company, $companylist)) {
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->default_displayHeader($config);
            $page = $page + $count;
          }
        }
      }

      if ($clientname == '' || $clientname != ($data->clientname . ' ( ' . $data->client . ' )')) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname . ' ( ' . $data->client . ' )', $layoutsize, null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();
        if (!in_array($company, $companylist)) {
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->default_displayHeader($config);
            $page = $page + $count;
          }
        }
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      switch ($company) {
        case 23:
        case 41:
        case 52: //technolab
          $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->agentname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          break;

        default:
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          break;
      }

      $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      if ($company == 23 || $company == 41 || $company == 52) { //labsol & technolab
        switch ($option) {
          case 'sales':
            $str .= $this->reporter->col(number_format($data->sales, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            break;
          case 'qty':
            $str .= $this->reporter->col(number_format($data->qty, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            break;
          case 'both':
            $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->sales, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            break;
        }
      } else {
        $str .= $this->reporter->col(number_format($data->sales, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotal += $data->sales;
      if ($company == 23 || $company == 41 || $company == 52) { //labsol & technolab
        $subtotalqty += $data->qty;
        $subtotalsales += $data->sales;
        $remtotalqty += $data->qty;
        $remtotalsales += $data->sales;
      } else {
        $subtotal += $data->sales;
        $remtotal += $data->sales;
      }

      $clientname = $data->clientname . ' ( ' . $data->client . ' )';

      if (!in_array($company, $companylist)) {
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page = $page + $count;
        }
      }

      if ($i == (count((array)$result) - 1)) {
        goto SubtotalHere;
      }
      $i++;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRANDTOTAL:', '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    if ($company == 23 || $company == 41 || $company == 52) { //labsol & technolab
      switch ($option) {
        case 'sales':
          $str .= $this->reporter->col(number_format($remtotalsales, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          break;
        case 'qty':
          $str .= $this->reporter->col(number_format($remtotalqty, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');

          break;
        case 'both':
          $str .= $this->reporter->col(number_format($remtotalqty, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($remtotalsales, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          break;
      }
    } else {
      $str .= $this->reporter->col(number_format($remtotal, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function vitaline_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES PER CUSTOMER PER ITEM ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' - ' . $end, '200', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Customer : ' . ($client == '' ? 'ALL' : $client), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Option : ' . ($option ? 'AMOUNT' : strtoupper($option)), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Category : ' . ($categoryname == '' ? 'ALL' : strtoupper($categoryname)), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Sub-Category : ' . ($subcatname == '' ? 'ALL' : strtoupper($subcatname)), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT # ', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOC DATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM NAME ', '300', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    switch ($option) {
      case 'qty':
        $str .= $this->reporter->col('QUANTITY', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
      case 'sales':
        $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
      case 'both':
        $str .= $this->reporter->col('QUANTITY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function vitaline_layout($config)
  {
    $result  = $this->reportDefault($config);
    $count = 48;
    $page = 50;
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    if (empty($result)) return $this->othersClass->emptydata($config);

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->vitaline_displayHeader($config);
    $subtotal = 0;
    $subtotalqty = 0;
    $subtotalsales = 0;
    $remtotal = 0;
    $remtotalqty = 0;
    $remtotalsales = 0;

    $clientname = "";
    $i = 0;
    foreach ($result as $key => $data) {
      if ($clientname != '' && $clientname != ($data->clientname . ' ( ' . $data->client . ' )')) {
        SubtotalHere:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('SUBTOTAL:', '600', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($subtotal, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $subtotal = 0;
        $subtotal2 = 0;

        if (++$i == count($result)) break;
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->vitaline_displayHeader($config);
          $page = $page + $count;
        }
      }

      if ($clientname == '' || $clientname != ($data->clientname . ' ( ' . $data->client . ' )')) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname . ' ( ' . $data->client . ' )', $layoutsize, null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->vitaline_displayHeader($config);
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->sales, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotal += $data->sales;
      $subtotal += $data->sales;
      $remtotal += $data->sales;

      $clientname = $data->clientname . ' ( ' . $data->client . ' )';

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->vitaline_displayHeader($config);
        $page = $page + $count;
      }


      if (++$i == count($result)) {
        goto SubtotalHere;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRANDTOTAL:', '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($remtotal, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function housegem_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    $str = '';
    $layoutsize = '1400';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SALES PER CUSTOMER PER ITEM ', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer :' . $client, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($option) {
      $str .= $this->reporter->col('Option : AMOUNT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL',  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }


    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT # ', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DR DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('OURREF', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DUE DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM NAME ', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AGENT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function housegem_layout($config)
  {
    $this->reporter->linecounter = 0;
    $company   = $config['params']['companyid'];

    $result  = $this->housegem_query($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1400';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->housegem_header($config);

    $subtotal = 0;
    $remtotal = 0;

    $clientname = "";
    $i = 0;
    foreach ($result as $key => $data) {

      if ($clientname != $data->clientname) {
        if ($clientname != "") {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('SUBTOTAL', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $subtotal = 0;
          $str .= "<br/>";

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->housegem_header($config);
            $page = $page + $count;
          }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname . ' ( ' . $data->client . ' )', '1400', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->housegem_header($config);
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->due, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->sales, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotal += $data->sales;
      $remtotal += $data->sales;
      $clientname = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->housegem_header($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('SUBTOTAL', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('GRANDTOTAL', '200', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($remtotal, 2), '100', null, false, '1.5px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1.5px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_header_PDF($config, $data)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];


    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename);
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);



    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');



    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(80, 0, "DATE PERIOD : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(200, 0, $start . ' to ' . $end, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 0, "CUSTOMER : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, ($client != '' ? $client : 'ALL'), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    $label = ($option != 'sales' ? strtoupper($option) : 'AMOUNT');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(100, 0, "OPTION : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, $label, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(100, 0, "CATEGORY : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, ($categoryname != '' ? $categoryname : 'ALL'), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(100, 0, "SUB-CATEGORY : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, ($subcatname != '' ? $subcatname : 'ALL'), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");



    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(65, 25, "CUSTOMER", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(110, 25, "DOCUMENT #", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "DOC DATE", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(250, 25, "ITEM NAME", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "PRICE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, $label, 'TB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }

  public function default_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalprice = 0;
    $totalsales = 0;
    $stprice = 0;
    $stsales = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_header_PDF($params, $data);

    $clientx = "";
    foreach ($data as $key => $value) {

      $maxrow = 1;
      $clientname = $value->clientname;
      $client = $value->client;
      $dateid = $value->dateid;
      $itemname = $value->itemname;
      $docno = $value->docno;
      $price = number_format($value->price, 2);
      $sales = number_format($value->sales, 2);

      $arr_clientname = $this->reporter->fixcolumn([$clientname . ' ( ' . $client . ' ) '], '50', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
      $arr_dateid = $this->reporter->fixcolumn([$dateid], '10', 0);
      $arr_docno = $this->reporter->fixcolumn([$docno], '15', 0);
      $arr_price = $this->reporter->fixcolumn([$price], '15', 0);
      $arr_sales = $this->reporter->fixcolumn([$sales], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_clientname, $arr_itemname, $arr_dateid, $arr_docno, $arr_price, $arr_sales]);

      if ($clientx != $value->client) {

        if ($clientx != "") {

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(65, 18, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(110, 18, 'SUB - TOTAL : ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(75, 18, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(250, 18, ' ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(100, 18, number_format($stprice, 2), 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(100, 18, number_format($stsales, 2), 'T', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

          PDF::MultiCell(700, 18, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
          $stsales = 0;
          $stprice = 0;
        }
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(700, 20, ' ' . (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
        }
      }

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(65, 18, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 18, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 18, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(250, 18, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 18, ' ' . (isset($arr_price[$r]) ? $arr_price[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 18, ' ' . (isset($arr_sales[$r]) ? $arr_sales[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      }

      $totalsales += $value->sales;
      $totalprice += $value->price;
      $stsales += $value->sales;
      $stprice += $value->price;

      if (PDF::getY() > 900) {
        $this->default_header_PDF($params, $data);
      }
      $clientx = $value->client;
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(65, 20, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(110, 20, 'SUB - TOTAL : ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(250, 20, ' ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, number_format($stprice, 2), 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, number_format($stsales, 2), 'T', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(125, 18, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(125, 18, 'GRAND - TOTAL : ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(250, 18, ' ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 18, number_format($totalprice, 2), 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 18, number_format($totalsales, 2), 'T', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function kinggeorge_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $groupname =  $config['params']['dataparams']['stockgrp'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SALES PER CUSTOMER PER ITEM ', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer :' . $client, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($option) {
      $str .= $this->reporter->col('Option : AMOUNT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL',  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }


    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Group : ALL',  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . strtoupper($groupname),  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SHIP TO', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PO NO', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DATE', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SALESMAN', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEMNAME', '175', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TOTAL', '75', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function kinggeorge_layout($config)
  {
    $this->reporter->linecounter = 0;
    $company   = $config['params']['companyid'];

    $result  = $this->housegem_query($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_header($config);

    $subtotal = 0;
    $remtotal = 0;

    $clientname = "";
    $i = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->shipto, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '175', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->sales, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $subtotal += $data->sales;
      $remtotal += $data->sales;
      $clientname = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->endtable();

        $str .= $this->kinggeorge_header($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRANDTOTAL', '200', null, false, '1.5px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($remtotal, 2), '800', null, false, '1.5px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
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
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];


    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SALES PER CUSTOMER PER ITEM ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer : ' . ($client != '' ? strtoupper($client) : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Option : ' . strtoupper($option), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Category : ' . ($categoryname != '' ? strtoupper($categoryname) : 'ALL'),  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Sub-Category : ' . ($subcatname != '' ? strtoupper($subcatname) : 'ALL'), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CUSTOMER NAME', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT # ', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ITEM NAME ', '300', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');

    switch ($option) {
      case 'qty':
        $str .= $this->reporter->col('QUANTITY', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        break;
      case 'sales':
        $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');

        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function mmm_layout($config)
  {
    // ini_set('memory_limit', '-1');
    $company   = $config['params']['companyid'];
    $result  = $this->mmm_query($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->mmm_header($config);

    $subtotal = 0;
    $remtotal = 0;

    $clientname = "";
    $i = 0;
    foreach ($result as $key => $data) {
      if ($clientname != '' && $clientname != ($data->clientname . ' ( ' . $data->client . ' )')) {
        SubtotalHere:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('SUBTOTAL:', '600', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($subtotal, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        if ($option == 'qty') {
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, $font, $fontsize, 'B');
        }



        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $subtotal = 0;
        if ($i == (count((array)$result) - 1)) {
          break;
        }
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->mmm_header($config);
          $page = $page + $count;
        }
      }

      if ($clientname == '' || $clientname != ($data->clientname . ' ( ' . $data->client . ' )')) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname . ' ( ' . $data->client . ' ) - ' . $data->brgy . ', ' . $data->area, $layoutsize, null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->price, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->sales, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      if ($option == 'qty') {
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotal += $data->sales;
      $remtotal += $data->sales;
      $clientname = $data->clientname . ' ( ' . $data->client . ' )';

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->mmm_header($config);
        $page = $page + $count;
      }

      if ($i == (count((array)$result) - 1)) {
        goto SubtotalHere;
      }
      $i++;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRANDTOTAL:', '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($remtotal, 2), '200', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class