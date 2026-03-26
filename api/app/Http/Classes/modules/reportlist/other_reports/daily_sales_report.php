<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class daily_sales_report
{
  public $modulename = 'Daily Sales';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'start', 'end', 'dclientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['radioposttype', 'radiopaidstatus', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '0' as posttype,
    '0' as paidstatus,
    '0' as reporttype,
     0 as clientid
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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0': // summarized
        return $this->report_SUMMARIZED_Layout($config);
        break;
      case '1': // detailed
        return $this->reportDefaultLayout($config);
        break;
    }
  }

  public function reportDefault($config)
  {
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $clientid   = $config['params']['dataparams']['clientid'];
    $paidstatus = $config['params']['dataparams']['paidstatus'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";
    $filter2 = "";
    $join = "";
    if ($client != "") {
      $filter .= " and head.clientid = '$clientid'"; //posted
      if ($posttype == 1 || $posttype == 2) {
        $join = "left join client as client on client.client = head.client";
        $filter2 .= " and client.clientid = '$clientid'"; //unposted/all
      }
    }
    $union = "";
    if ($posttype == 2 && ($paidstatus == 1 || $paidstatus == 2)) {
      $union = " UNION ALL ";
    }
    // this qry is for unposted unpaid and all paidstatus 
    // union ng posted unpaid/all paidstatus at unposted unpaid
    $unposted = " $union
            select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  'Unpaid'  as status,
                  stock.ext as totalsales, ifnull(ag.clientname,'No Agent') as agentname
                  from lahead as head
                  left join lastock as stock on stock.trno = head.trno
                  left join client as ag on ag.client = head.client
                  $join
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext<>0
                  $filter2
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, stock.ext,  ag.clientname";

    //posted  unpaid and all paidstatus  para yung filter na unposted ay empty          
    if ($posttype == 0 && ($paidstatus == 1 || $paidstatus == 2)) {
      $unposted = "";
    }

    switch ($reporttype) {
      case '0': // summarized
        switch ($posttype) {
          case '0': //posted
          case '2': // all  
            switch ($paidstatus) {
              case '0': // paid
                $query = "
                  select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  case  when sum(ar.bal) = 0 then 'Paid'  when sum(ar.bal) > 0 then 'Unpaid' end as status,
                  stock.ext as totalsales, ifnull(ag.clientname,'No Agent') as agentname
                  from arledger as ar
                  left join glhead as head on ar.trno = head.trno
                  left join glstock as stock on stock.trno = head.trno
                  left join client as ag on ag.clientid = head.agentid
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and ar.bal = 0
                  $filter
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, ag.clientname,stock.ext
                  order by ag.clientname
                  ";
                break;
              case '1': // unpaid
                $query = "
                  select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  case when sum(ar.bal) = 0 then 'Paid' when sum(ar.bal) > 0 then 'Unpaid'  end as status,
                  stock.ext as totalsales, ifnull(ag.clientname,'No Agent') as agentname
                  from arledger as ar
                  left join glhead as head on ar.trno = head.trno
                  left join glstock as stock on stock.trno = head.trno
                  left join client as ag on ag.clientid = head.agentid
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and ar.bal <> 0
                  $filter
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, ag.clientname, stock.ext
                  $unposted
                  order by clientname ";
                break;

              case '2': // all
                $query = "
                  select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  case when sum(ar.bal) = 0 then 'Paid' when sum(ar.bal) > 0 then 'Unpaid' end as status,
                  stock.ext as totalsales, ifnull(ag.clientname,'No Agent') as agentname
                  from arledger as ar
                  left join glhead as head on ar.trno = head.trno
                  left join glstock as stock on stock.trno = head.trno
                  left join client as ag on ag.clientid = head.agentid
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
                  $filter
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, ag.clientname, stock.ext
                  $unposted
                  order by clientname";
                break;
            }
            break;
          case '1': // unposted
            switch ($paidstatus) {
              case '0': // paid
                $query = "
                  select '' as none";
                break;
              case '1':
              case '2': // unpaid
                $query = " $unposted
                  order by ag.clientname ";
                break;
            }
            break;
        }

        break; //end summarized

      case '1': // detailed
        switch ($posttype) {
          case '0': //posted
          case '2': // all  
            switch ($paidstatus) {
              case '0': // paid
                $query = "
                  select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  case  when sum(ar.bal) = 0 then 'Paid'  when sum(ar.bal) > 0 then 'Unpaid' end as status,
                  item.itemname, stock.uom, stock.iss as qty, stock.amt as salesprice,
                  stock.ext as totalsales, stock.cost as unitcost,
                  (stock.iss * stock.cost) as totalcost, stock.rebate,
                  (stock.ext - (stock.iss * stock.cost)) as profit, ifnull(ag.clientname,'No Agent') as agentname
                  from arledger as ar
                  left join glhead as head on ar.trno = head.trno
                  left join glstock as stock on stock.trno = head.trno
                  left join item as item on item.itemid = stock.itemid
                  left join client as ag on ag.clientid = head.agentid
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and ar.bal = 0
                  $filter
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, item.itemname, stock.uom, stock.iss, stock.amt, ag.clientname,
                  stock.ext, stock.cost, stock.rebate
                  order by ag.clientname
                  ";
                break;
              case '1': // unpaid
                $query = "
                  select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  case when sum(ar.bal) = 0 then 'Paid' when sum(ar.bal) > 0 then 'Unpaid'  end as status,
                  item.itemname, stock.uom, stock.iss as qty, stock.amt as salesprice,
                  stock.ext as totalsales, stock.cost as unitcost,
                  (stock.iss * stock.cost) as totalcost, stock.rebate,
                  (stock.ext - (stock.iss * stock.cost)) as profit, ifnull(ag.clientname,'No Agent') as agentname
                  from arledger as ar
                  left join glhead as head on ar.trno = head.trno
                  left join glstock as stock on stock.trno = head.trno
                  left join item as item on item.itemid = stock.itemid
                  left join client as ag on ag.clientid = head.agentid
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and ar.bal <> 0
                  $filter
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, item.itemname, stock.uom, stock.iss, stock.amt, ag.clientname,
                  stock.ext, stock.cost, stock.rebate
                  $unposted
                  order by clientname ";
                break;

              case '2': // all
                $query = "
                  select head.trno, head.docno, head.clientname, date(head.dateid) as dateid,
                  case  when sum(ar.bal) = 0 then 'Paid'  when sum(ar.bal) > 0 then 'Unpaid' end as status,
                  item.itemname, stock.uom, stock.iss as qty, stock.amt as salesprice,
                  stock.ext as totalsales, stock.cost as unitcost,
                  (stock.iss * stock.cost) as totalcost, stock.rebate,
                  (stock.ext - (stock.iss * stock.cost)) as profit, ifnull(ag.clientname,'No Agent') as agentname
                  from arledger as ar
                  left join glhead as head on ar.trno = head.trno
                  left join glstock as stock on stock.trno = head.trno
                  left join item as item on item.itemid = stock.itemid
                  left join client as ag on ag.clientid = head.agentid
                  where head.doc = 'SJ' and date(head.dateid) between '$start' and '$end'
                  $filter
                  group by head.trno, head.docno, head.clientname,
                  head.dateid, item.itemname, stock.uom, stock.iss, stock.amt, ag.clientname,
                  stock.ext, stock.cost, stock.rebate
                  $unposted
                  order by clientname";
                break;
            }
            break;
          case '1': // unposted
            switch ($paidstatus) {
              case '0': // paid
                $query = "
                  select '' as none";
                break;
              case '1':
              case '2': // unpaid
                $query = " $unposted
                  order by ag.clientname ";
                break;
            }
            break;
        }
        break; //end detailed
    }

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config, $layoutsize)
  {

    $font = $this->companysetup->getrptfont($config['params']);

    $center   = $config['params']['center'];
    $username   = $config['params']['user'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $paidstatus = $config['params']['dataparams']['paidstatus'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $str = '';


    switch ($posttype) {
      case '0': // posted
        $posttype = "Posted";
        break;
      case '1': // unposted
        $posttype = "Unposted";
        break;
      case '2': // all
        $posttype = "All Transaction";
        break;
    }

    switch ($paidstatus) {
      case '0': // posted
        $paidstatus = "Paid";
        break;
      case '1': // unposted
        $paidstatus = "Unpaid";
        break;
      case '2': // all
        $paidstatus = "All Status";
        break;
    }
    if ($client != "") {
      $client = $client;
    } else {
      $client = "All Customer";
    }
    if ($reporttype == 1) { // detailed
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<br>');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Daily Sales Detailed', null, null, false, '1px solid ', '', 'C', $font, '17', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('From ' . date('M d, Y', strtotime($start)) . 'To ' . date('M d, Y', strtotime($end)), null, null, false, '1px solid ', '', 'C', $font, '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Transaction: ' . $posttype . ' Status: ' . $paidstatus . ' Customer: ' . $client, null, null, false, '1px solid ', '', 'C', $font, '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= "<br>";

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Agentname', '100', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Type', '50', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Date', '100', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Num', '120', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Status', '50', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Name', '120', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Item Name', '120', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('QTY', '50', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('UOM', '50', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Sales Price', '75', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('Total Sales', '75', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('Rebate', '75', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('Unit Cost', '75', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('Total Cogs', '75', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('Profit', '75', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<br>');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Daily Sales Summarized', null, null, false, '1px solid ', '', 'C', $font, '17', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('From ' . date('M d, Y', strtotime($start)) . 'To ' . date('M d, Y', strtotime($end)), null, null, false, '1px solid ', '', 'C', $font, '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Transaction: ' . $posttype . ' Status: ' . $paidstatus . ' Customer: ' . $client, null, null, false, '1px solid ', '', 'C', $font, '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= "<br>";

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Agentname', '150', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Type', '100', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Date', '100', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Num', '150', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Name', '200', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
      $str .= $this->reporter->col('Total Amount', '150', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('Status', '150', null, false, '1px solid ', 'BT', 'C', $font, '10', 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    return $str;
  }

  public function reportDefaultLayout($config)
  {

    $font = $this->companysetup->getrptfont($config['params']);

    $result     = $this->reportDefault($config);
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $paidstatus = $config['params']['dataparams']['paidstatus'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $count = 40;
    $page = 38;
    $str = '';
    $layoutsize = 1500;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, $layoutsize);


    if ($posttype == 1) {
      if ($paidstatus == 0) {
        return $str;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);

    $docno = "";
    $agentname = "";
    $subtotqty = 0;
    $subtotsprice = 0;
    $subtotsales = 0;
    $subtotrebate = 0;
    $subtotcost = 0;
    $subtottcost = 0;
    $subtotprofit = 0;

    $totqty = 0;
    $totsprice = 0;
    $totsales = 0;
    $totrebate = 0;
    $totcost = 0;
    $tottcost = 0;
    $totprofit = 0;

    $c = 0;
    $cnt = count((array)$result);
    foreach ($result as $key => $data) {
      $c++;
      $str .= $this->reporter->addline();

      $qty = $data->qty;
      $salesprice = $data->salesprice;
      $totalsales = $data->totalsales;
      $rebate = $data->rebate;
      $unitcost = $data->unitcost;
      $totalcost = $data->totalcost;
      $profit = $data->profit;

      if ($qty == 0) {
        $qty = '-';
      } else {
        $qty = number_format($qty, 2);
      }

      if ($salesprice == 0) {
        $salesprice = '-';
      } else {
        $salesprice = number_format($salesprice, 2);
      }

      if ($totalsales == 0) {
        $totalsales = '-';
      } else {
        $totalsales = number_format($totalsales, 2);
      }

      if ($rebate == 0) {
        $rebate = '-';
      } else {
        $rebate = number_format($rebate, 2);
      }

      if ($unitcost == 0) {
        $unitcost = '-';
      } else {
        $unitcost = number_format($unitcost, 2);
      }

      if ($totalcost == 0) {
        $totalcost = '-';
      } else {
        $totalcost = number_format($totalcost, 2);
      }

      if ($profit == 0) {
        $profit = '-';
      } else {
        $profit = number_format($profit, 2);
      }

      if ($docno != $data->docno) {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->subTotal(number_format($subtotqty, 2), number_format($subtotsprice, 2), number_format($subtotsales, 2), number_format($subtotrebate, 2), number_format($subtotcost, 2), number_format($subtottcost, 2), number_format($subtotprofit, 2), $config);
          $str .= $this->reporter->endrow();

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config, $layoutsize);
            $str .= $this->reporter->begintable($layoutsize);
            $page = $page + $count;
          }
        }
      }

      if ($agentname != $data->agentname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agentname, '100', null, false, '1px solid ', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      if ($docno != $data->docno) {

        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('Invoice', '50', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col($data->docno, '120', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col($data->status, '50', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col($data->clientname, '120', null, false, '1px solid ', '', 'L', $font, '10', '');
        $subtotqty = 0;
        $subtotsprice = 0;
        $subtotsales = 0;
        $subtotrebate = 0;
        $subtotcost = 0;
        $subtottcost = 0;
        $subtotprofit = 0;
      } else {
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('-', '50', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('-', '120', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('-', '50', null, false, '1px solid ', '', 'L', $font, '10', '');
        $str .= $this->reporter->col('-', '120', null, false, '1px solid ', '', 'L', $font, '10', '');
      }
      $str .= $this->reporter->col($data->itemname, '120', null, false, '1px solid ', '', 'L', $font, '10', '');
      $str .= $this->reporter->col($qty, '50', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, '1px solid ', '', 'L', $font, '10', '');
      $str .= $this->reporter->col($salesprice, '75', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->col($totalsales, '75', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->col($rebate, '75', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->col($unitcost, '75', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->col($totalcost, '75', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->col($profit, '75', null, false, '1px solid ', '', 'R', $font, '10', '');
      $str .= $this->reporter->endrow();


      $docno = $data->docno;
      $agentname = $data->agentname;
      $subtotqty += $data->qty;
      $subtotsprice += $data->salesprice;
      $subtotsales += $data->totalsales;
      $subtotrebate += $data->rebate;
      $subtotcost += $data->unitcost;
      $subtottcost += $data->totalcost;
      $subtotprofit += $data->profit;

      $totqty += $data->qty;
      $totsprice += $data->salesprice;
      $totsales += $data->totalsales;
      $totrebate += $data->rebate;
      $totcost += $data->unitcost;
      $tottcost += $data->totalcost;
      $totprofit += $data->profit;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }

      if ($c == $cnt) {
        $str .= $this->subTotal(number_format($subtotqty, 2), number_format($subtotsprice, 2), number_format($subtotsales, 2), number_format($subtotrebate, 2), number_format($subtotcost, 2), number_format($subtottcost, 2), number_format($subtotprofit, 2), $config);
      }
    } // end loop

    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total: ', '700', null, false, '1px solid ', 'B', 'L', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totqty, 2), '50', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'B', 'L', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totsprice, 2), '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totsales, 2), '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totrebate, 2), '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totcost, 2), '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($tottcost, 2), '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totprofit, 2), '75', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function report_SUMMARIZED_Layout($config)
  {

    $result     = $this->reportDefault($config);
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $paidstatus = $config['params']['dataparams']['paidstatus'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $count = 40;
    $page = 38;
    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, $layoutsize);


    if ($posttype == 1) {
      if ($paidstatus == 0) {
        return $str;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);

    $docno = "";
    $agentname = "";
    $subtotsales = 0;

    $totsales = 0;

    $c = 0;
    $cnt = count((array)$result);
    $i = 0;
    foreach ($result as $key => $data) {
      $c++;
      $str .= $this->reporter->addline();

      $totalsales = $data->totalsales;

      if ($totalsales == 0) {
        $totalsales = '-';
      } else {
        $totalsales = number_format($totalsales, 2);
      }


      if ($docno != $data->docno) {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, '10', 'R');
          $str .= $this->reporter->col('Invoice', '100', null, false, '1px solid ', '', 'L', $font, '10', 'R');
          $str .= $this->reporter->col($result[$i - 1]->dateid, '100', null, false, '1px solid ', '', 'L', $font, '10', 'R');
          $str .= $this->reporter->col($result[$i - 1]->docno, '150', null, false, '1px solid ', '', 'L', $font, '10', 'R');
          $str .= $this->reporter->col($result[$i - 1]->clientname, '200', null, false, '1px solid ', '', 'L', $font, '10', 'R');
          $str .= $this->reporter->col(number_format($subtotsales, 2), '150', null, false, '1px solid ', '', 'R', $font, '10', 'R');
          $str .= $this->reporter->col($result[$i - 1]->status, '150', null, false, '1px solid ', '', 'C', $font, '10', 'R');
          $str .= $this->reporter->endrow();
        }
      }

      if ($agentname != $data->agentname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agentname, '150', null, false, '1px solid ', '', 'L', $font, '10', 'B');
        $str .= $this->reporter->endrow();
      }

      if ($docno != $data->docno) {
        $subtotsales = 0;
      }

      $docno = $data->docno;
      $subtotsales += $data->totalsales;

      $totsales += $data->totalsales;
      $agentname = $data->agentname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }

      if ($c == $cnt) {
        $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, '10', 'R');
        $str .= $this->reporter->col('Invoice', '100', null, false, '1px solid ', '', 'L', $font, '10', 'R');
        $str .= $this->reporter->col($result[$i]->dateid, '100', null, false, '1px solid ', '', 'L', $font, '10', 'R');
        $str .= $this->reporter->col($result[$i]->docno, '150', null, false, '1px solid ', '', 'L', $font, '10', 'R');
        $str .= $this->reporter->col($result[$i]->clientname, '200', null, false, '1px solid ', '', 'L', $font, '10', 'R');
        $str .= $this->reporter->col(number_format($subtotsales, 2), '150', null, false, '1px solid ', '', 'R', $font, '10', 'R');
        $str .= $this->reporter->col($result[$i]->status, '150', null, false, '1px solid ', '', 'C', $font, '10', 'R');
      }
      $i++;
    } // end loop

    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total: ', '700', null, false, '1px solid ', 'B', 'L', $font, '10', 'B');
    $str .= $this->reporter->col(number_format($totsales, 2), '150', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'B', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function subTotal($subtotqty, $subtotsprice, $subtotsales, $subtotrebate, $subtotcost, $subtottcost, $subtotprofit, $config)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $str = "";

    if ($subtotqty == 0) {
      $subtotqty = '-';
    }

    if ($subtotsprice == 0) {
      $subtotsprice = '-';
    }

    if ($subtotsales == 0) {
      $subtotsales = '-';
    }

    if ($subtotrebate == 0) {
      $subtotrebate = '-';
    }

    if ($subtotcost == 0) {
      $subtotcost = '-';
    }

    if ($subtottcost == 0) {
      $subtottcost = '-';
    }

    if ($subtotprofit == 0) {
      $subtotprofit = '-';
    }

    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col($subtotqty, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->col($subtotsprice, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');
    $str .= $this->reporter->col($subtotsales, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');
    $str .= $this->reporter->col($subtotrebate, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');
    $str .= $this->reporter->col($subtotcost, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');
    $str .= $this->reporter->col($subtottcost, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');
    $str .= $this->reporter->col($subtotprofit, '75', null, false, '1px solid ', 'T', 'R', $font, '10', 'B');

    return $str;
  }
}//end class