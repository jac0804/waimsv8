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

class sales_report
{
  public $modulename = 'Sales Report';
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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dclientname', 'dagentname', 'ditemname', 'categoryname', 'start', 'end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dclientname.lookupclass', 'lookupclient');
    data_set($col2, 'dclientname.label', 'Customer');
    data_set($col2, 'dagentname.action', 'lookupagentreport');
    data_set($col2, 'categoryname.action', 'lookupcategoryitem');
    data_set($col2, 'categoryname.name', 'category');

    $fields = ['radioposttype', 'radiostatus', 'radioreporttype'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'radioposttype.name', 'posttype');
    data_set($col3, 'radiostatus.name', 'statustype');
    data_set($col3, 'radioreporttype.name', 'salestype');
    data_set($col3, 'radioposttype.options', array(
      ['label' => 'All Transaction', 'value' => 'all', 'color' => 'black'],
      ['label' => 'Posted', 'value' => 'posted', 'color' => 'black'],
      ['label' => 'Unposted', 'value' => 'unposted', 'color' => 'black'],
    ));

    data_set($col3, 'radiostatus.options', array(
      ['label' => 'All Status', 'value' => 'all', 'color' => 'pink'],
      ['label' => 'Paid', 'value' => 'paid', 'color' => 'pink'],
      ['label' => 'Unpaid', 'value' => 'unpaid', 'color' => 'pink'],
    ));

    data_set($col3, 'radioreporttype.options', array(
      ['label' => 'Rep/Agent', 'value' => '0', 'color' => 'blue'],
      ['label' => 'Customer', 'value' => '1', 'color' => 'blue'],
      ['label' => 'Item', 'value' => '2', 'color' => 'blue'],
      ['label' => 'Brand', 'value' => '3', 'color' => 'blue'],
    ));

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    return $this->coreFunctions->opentable("
        select 'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        0 as clientid,
        '' as client,
        '' as clientname,
        '' as dclientname,
        0 as agentid,
        '' as agent,
        '' as agentname,
        '' as dagentname,
        '' as barcode,
        0 as itemid,
        '' as itemname,
        '' as ditemname,
        '' as category,
        'all' as posttype,
        'all' as statustype,
        '0' as salestype
        ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $result = $this->default_query($config);

    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function default_query($filters)
  {
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];
    $agent = $filters['params']['dataparams']['agent'];
    $agentid = $filters['params']['dataparams']['agentid'];
    $category = $filters['params']['dataparams']['category'];
    $itemid = $filters['params']['dataparams']['itemid'];
    $barcode = $filters['params']['dataparams']['barcode'];
    $posttype = $filters['params']['dataparams']['posttype'];
    $statustype = $filters['params']['dataparams']['statustype'];
    $salestype = $filters['params']['dataparams']['salestype'];

    $filter = "";
    $joincl = "";
    $postedfilter = "";
    $unpostedfilter = "";

    if ($client != "") {
      switch ($posttype) {
        case 'unposted':
          $joincl .= "left join client on client.client = head.client";
          $filter .= " and client.clientid = '" . $clientid . "'  ";
          break;
        case 'posted':
          $filter .=  " and head.clientid= '" . $clientid . "'  "; //posted
          break;

        default:
          $joincl .= "left join client on client.client = head.client";
          $postedfilter .=  "and head.clientid= '" . $clientid . "' ";
          $unpostedfilter .= " and client.clientid = '" . $clientid . "'";
          break;
      }
    } //end if


    if ($agent != "") {
      if ($agent == "NO AGENT") {
        $filter .= " and agent.client IS NULL  ";
      } else {
        switch ($posttype) {
          case 'unposted':
            if ($salestype == 1 || $salestype == 2 || $salestype == 3) {
              $joincl .= " left join client as agent ON agent.client = head.agent";
            }
            $filter .= " and agent.clientid = '" . $agentid . "' ";
            break;
          case 'posted':
            $filter .=  " and head.agentid = '" . $agentid . "' "; //posted
            break;
          default:
            $joincl .= " left join client as agent ON agent.client = head.agent"; //unposted
            $postedfilter .=  "and head.agentid = '" . $agentid . "'";
            $unpostedfilter .= " and agent.clientid = '" . $agentid . "'";
            break;
        }
      }
    } //end if


    if ($barcode != "") {
      $filter .= " and item.itemid = '" . $itemid . "'  ";
    } //end if


    if ($category != "") {
      $filter .= " and item.category = '" . $category . "'  ";
    } //end if

    // payment status
    switch ($statustype) {
      case 'paid':
        $paymentstatus = "and ar.bal = 0";
        break;

      case 'unpaid':
        $paymentstatus = "and (ar.bal <> 0 or ar.bal is null)";
        break;

      default: // all
        $paymentstatus = "";
        break;
    }
    switch ($salestype) {
      case '0': // rep/agent
        switch ($posttype) {
          case 'posted': // posted
            $query = "select agent.clientname as agentname, date(head.dateid) as dateid, head.docno, head.clientname,
            item.itemname, sum(stock.isqty) as qty, stock.uom, stock.rebate, stock.amt, sum(stock.ext) as ext, stock.cost, 
            sum(stock.iss * stock.cost) as cogs, sum(stock.ext - (stock.iss * stock.cost)) as profit,
            case when ar.bal <> 0 THEN 'UNPAID'  when ar.bal = 0 THEN 'PAID'  end as status,
            date(crhead.dateid) as collected, '' as refund 
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join client as agent ON agent.clientid = head.agentid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            left join (
            select refx, max(trno) as last_trno
                   from gldetail group by refx) lastcr on lastcr.refx = ar.trno
            left join gldetail as crdetail on crdetail.refx = lastcr.refx and crdetail.trno = lastcr.last_trno
            left join glhead as crhead on crhead.trno = crdetail.trno

            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by agent.clientname, date(head.dateid), head.docno,head.clientname,
            item.itemname,  stock.uom, stock.rebate, stock.amt,  stock.cost, status, bal, date(crhead.dateid), refund 
            order by agentname, clientname";

            break;

            // left join gldetail as crdetail on ar.trno = crdetail.refx
            // left join glhead as crhead on crhead.trno = crdetail.trno
          case 'unposted': // unposted
            $query = "select agent.clientname as agentname, date(head.dateid) as dateid, head.docno, head.clientname, 
            item.itemname, sum(stock.isqty) as qty, stock.uom, stock.rebate, stock.amt, sum(stock.ext) as ext, stock.cost, 
            sum(stock.iss * stock.cost) as cogs, sum(stock.ext - (stock.iss * stock.cost)) as profit,
            case when MAX(ar.bal) <> 0 THEN 'UNPAID'   when MAX(ar.bal) = 0 THEN 'PAID'  end as status,
            date(crhead.dateid) as collected, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join client as agent ON agent.client = head.agent 
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            
            left join (
            select refx, max(trno) as last_trno
                   from gldetail group by refx) lastcr on lastcr.refx = ar.trno
            left join gldetail as crdetail on crdetail.refx = lastcr.refx and crdetail.trno = lastcr.last_trno
            left join glhead as crhead on crhead.trno = crdetail.trno

            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by agent.clientname, date(head.dateid), head.docno, head.clientname, 
            item.itemname, stock.uom, stock.rebate, stock.amt, stock.cost, date(crhead.dateid), refund
            order by agentname, clientname";
            break;

          default: // all
            $query = "select agent.clientname as agentname, date(head.dateid) as dateid, head.docno, head.clientname, 
            item.itemname, sum(stock.isqty) as qty, stock.uom, stock.rebate, stock.amt, sum(stock.ext) as ext, stock.cost, 
            sum(stock.iss * stock.cost) as cogs, sum(stock.ext - (stock.iss * stock.cost)) as profit,
            case when MAX(ar.bal) <> 0 THEN 'UNPAID' when MAX(ar.bal) = 0 THEN 'PAID' end as status, 
            date(crhead.dateid) as collected, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join client as agent ON agent.clientid = head.agentid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            
            left join (
            select refx, max(trno) as last_trno
                   from gldetail group by refx) lastcr on lastcr.refx = ar.trno
            left join gldetail as crdetail on crdetail.refx = lastcr.refx and crdetail.trno = lastcr.last_trno
            left join glhead as crhead on crhead.trno = crdetail.trno

            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $postedfilter $paymentstatus 
            group by agent.clientname, date(head.dateid), head.docno, head.clientname, 
            item.itemname, stock.uom, stock.rebate, stock.amt, stock.cost, date(crhead.dateid), refund
            union all
            select agent.clientname as agentname, date(head.dateid) as dateid, head.docno, head.clientname, 
            item.itemname, sum(stock.isqty) as qty, stock.uom, stock.rebate, stock.amt, sum(stock.ext) as ext, stock.cost, 
            sum(stock.iss * stock.cost) as cogs, sum(stock.ext - (stock.iss * stock.cost)) as profit,
            case  when MAX(ar.bal) <> 0 THEN 'UNPAID'  when MAX(ar.bal) = 0 THEN 'PAID' else 'UNPAID' end as status, 
            date(crhead.dateid) as collected, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join client as agent ON agent.client = head.agent 
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            
            left join (
            select refx, max(trno) as last_trno
                   from gldetail group by refx) lastcr on lastcr.refx = ar.trno
            left join gldetail as crdetail on crdetail.refx = lastcr.refx and crdetail.trno = lastcr.last_trno
            left join glhead as crhead on crhead.trno = crdetail.trno
            
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $unpostedfilter $paymentstatus 
            group by agent.clientname, date(head.dateid), head.docno, head.clientname, 
            item.itemname,  stock.uom, stock.rebate, stock.amt, stock.cost, date(crhead.dateid), refund
            order by agentname, clientname";
            break;

        }
        break;

      case '1': // customer
        switch ($posttype) {
          case 'posted': // posted
            $query = "select head.clientname, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case when ar.bal <> 0 THEN 'UNPAID'  when ar.bal = 0 THEN 'PAID'  end as status, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by head.clientname, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, status, refund
            order by clientname";
            break;

          case 'unposted': // unposted
            $query = "select head.clientname, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when MAX(ar.bal) <> 0 THEN 'UNPAID'  when MAX(ar.bal) = 0 THEN 'PAID'  else 'UNPAID'  end as status, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            $joincl
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by head.clientname, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            order by clientname";
            break;

          default: // all
            $query = "select head.clientname, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case when MAX(ar.bal) <> 0 THEN 'UNPAID' when MAX(ar.bal) = 0 THEN 'PAID' end as status, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter  $postedfilter $paymentstatus
            group by head.clientname, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            union all
            select head.clientname, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case   when MAX(ar.bal) <> 0 THEN 'UNPAID' when MAX(ar.bal) = 0 THEN 'PAID' else 'UNPAID' end as status, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            $joincl
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter  $unpostedfilter $paymentstatus
            group by head.clientname, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            order by clientname
            ";
            break;
        }
        break;

      case '2': // item
        switch ($posttype) {
          case 'posted': // posted
            $query = "select item.itemname, date(head.dateid) as dateid, head.docno, head.clientname, 
            stock.isqty as qty, stock.uom, stock.rebate, stock.isamt as amt, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID' when max(ar.bal) = 0 THEN 'PAID'  end as status, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by item.itemname, date(head.dateid), head.docno, head.clientname, 
            stock.isqty, stock.uom, stock.rebate, stock.isamt, stock.ext, stock.cost, cogs, profit, refund
            order by itemname";
            break;

          case 'unposted': // unposted
            $query = "select item.itemname, date(head.dateid) as dateid, head.docno, head.clientname, 
            stock.isqty as qty, stock.uom, stock.rebate, stock.isamt  as amt, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID'  when max(ar.bal) = 0 THEN 'PAID'  else 'UNPAID' end as status, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            $joincl
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by item.itemname, date(head.dateid), head.docno, head.clientname, 
            stock.isqty, stock.uom, stock.rebate, stock.isamt, stock.ext, stock.cost, cogs, profit, refund
            order by itemname";
            break;

          default: // all
            $query = "select item.itemname, date(head.dateid) as dateid, head.docno, head.clientname, 
            stock.isqty as qty, stock.uom, stock.rebate, stock.isamt  as amt, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case   when max(ar.bal) <> 0 THEN 'UNPAID'  when max(ar.bal) = 0 THEN 'PAID'  end as status, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $postedfilter $paymentstatus
            group by item.itemname, date(head.dateid), head.docno, head.clientname, 
            stock.isqty, stock.uom, stock.rebate, stock.isamt, stock.ext, stock.cost, cogs, profit, refund
            union all
            select item.itemname, date(head.dateid) as dateid, head.docno, head.clientname, 
            stock.isqty as qty, stock.uom, stock.rebate, stock.isamt, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID'  when max(ar.bal) = 0 THEN 'PAID'  else 'UNPAID' end as status, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
             $joincl
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $unpostedfilter $paymentstatus
            group by item.itemname, date(head.dateid), head.docno, head.clientname, 
            stock.isqty, stock.uom, stock.rebate, stock.isamt, stock.ext, stock.cost, cogs, profit, refund
            order by itemname ";
            break;
        }
        break;

      default: // brand 
        switch ($posttype) {
          case 'posted': // posted
            $query = "select ifnull(frontend_ebrands.brand_desc,' No Brand') as brand, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt  as amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID'   when max(ar.bal) = 0 THEN 'PAID'  end as status, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join frontend_ebrands on frontend_ebrands.brandid = item.brand
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by frontend_ebrands.brand_desc, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            order by brand";
            break;

          case 'unposted': // unposted
            $query = "select ifnull(frontend_ebrands.brand_desc,' No Brand') as brand, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID'  when max(ar.bal) = 0 THEN 'PAID'  else 'UNPAID'  end as status, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join frontend_ebrands on frontend_ebrands.brandid = item.brand
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
             $joincl
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $paymentstatus
            group by frontend_ebrands.brand_desc, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            order by brand";
            break;

          default: // all
            $query = "select ifnull(frontend_ebrands.brand_desc,' No Brand') as brand, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID' when max(ar.bal) = 0 THEN 'PAID'  end as status, '' as refund
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join frontend_ebrands on frontend_ebrands.brandid = item.brand
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $unpostedfilter $paymentstatus
            group by frontend_ebrands.brand_desc, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            union all
            select ifnull(frontend_ebrands.brand_desc,' No Brand') as brand, date(head.dateid) as dateid, head.docno, 
            item.itemname, stock.isqty as qty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, 
            (stock.iss * stock.cost) as cogs, (stock.ext - (stock.iss * stock.cost)) as profit,
            case  when max(ar.bal) <> 0 THEN 'UNPAID' when max(ar.bal) = 0 THEN 'PAID'  else 'UNPAID' end as status, '' as refund
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join item on stock.itemid = item.itemid
            left join frontend_ebrands on frontend_ebrands.brandid = item.brand
            left join arledger as ar on stock.trno = ar.trno and stock.line=ar.line
             $joincl
            where head.doc = 'SJ' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter $postedfilter $paymentstatus
            group by frontend_ebrands.brand_desc, date(head.dateid), head.docno, 
            item.itemname, stock.isqty, stock.uom, stock.amt, stock.rebate, stock.ext, stock.cost, cogs, profit, refund
            order by brand";
            break;
        }
        break;
    }
      // var_dump($query);
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config, $result)
  {

    $salestype = $config['params']['dataparams']['salestype'];
    switch ($salestype) {
      case '0': // rep/agent
        $reportdata =  $this->DEFAULT_SALES_REPORT_BY_AGENT($result, $config);
        break;

      case '1': // customer
        $reportdata =  $this->DEFAULT_SALES_REPORT_BY_CUSTOMER($result, $config);
        break;

      case '2': // item
        $reportdata =  $this->DEFAULT_SALES_REPORT_BY_ITEM($result, $config);
        break;

      default: // brand 
        $reportdata =  $this->DEFAULT_SALES_REPORT_BY_BRAND($result, $config);
        break;
    }

    return $reportdata;
  }

  private function DEFAULT_SALES_REPORT_BY_AGENT_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $str = '';
    $startdate = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $clientname = $params['params']['dataparams']['clientname'];
    $agentname = $params['params']['dataparams']['agentname'];
    $category = $params['params']['dataparams']['category'];
    $itemname = $params['params']['dataparams']['itemname'];
    $posttype = $params['params']['dataparams']['posttype'];
    $statustype = $params['params']['dataparams']['statustype'];
    $salestype = $params['params']['dataparams']['salestype'];

    if ($clientname == '') {
      $clientname = 'ALL';
    }

    if ($agentname == '') {
      $agentname = 'ALL';
    }

    if ($category == '') {
      $category = 'ALL';
    }

    if ($itemname == '') {
      $itemname = 'ALL';
    }

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales by Rep Detail', null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Status : ' . strtoupper($statustype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Customer : ' . $clientname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Agent : ' . $agentname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Item : ' . $itemname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('category : ' . $category, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent Name', '150', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Doc #', '80', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Item', '120', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Qty', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Rebate', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Sale Price', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Unit Cost', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total COGS', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Profit', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Status', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Collected', '50', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Refund', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_AGENT($data, $params)
  {
    $str = '';
    $count = 60;
    $page = 59;
    $font = $this->companysetup->getrptfont($params['params']);
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    // decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $decimal_qty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimal_price = $this->companysetup->getdecimal('price', $params['params']);

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_SALES_REPORT_BY_AGENT_HEADER($params);

    $agentname = "";
    $customername = "";
    $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrebate = 0;
    $csubqty = $csubprice = $csubsales = $csubcost = $csubcogs = $csubprofit = $csubrebate = 0;
    $grandqty = $grandprice = $grandsales = $grandcost = $grandcogs = $grandprofit = $grandrebate = 0;

    foreach ($data as $key => $value) {
      $rebate = $value->rebate;

      if ($rebate == 0) {
        $rebate = '-';
      } else {
        $rebate = number_format($rebate, $decimal_currency);
      }

      if ($customername != $value->clientname) {
        if ($customername != "") {

          if ($csubrebate == 0) {
            $csubrebate = '-';
          } else {
            $csubrebate = number_format($csubrebate, $decimal_currency);
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('SUB-TOTAL', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($csubqty, $decimal_qty), '50', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');

          $str .= $this->reporter->col($csubrebate, '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($csubprice, $decimal_currency), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($csubsales, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($csubcost, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($csubcogs, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($csubprofit, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $csubqty = $csubprice = $csubsales = $csubcost = $csubcogs = $csubprofit = $csubrebate = 0;
        }
      }

      if ($value->agentname == "") {
        $value->agentname = "NO AGENT";
      }

      if ($agentname != $value->agentname) {
        if ($agentname != "") {

          if ($subrebate == 0) {
            $subrebate = '-';
          } else {
            $subrebate = number_format($subrebate, $decimal_currency);
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($agentname . ' GRAND-TOTAL', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');

          $str .= $this->reporter->col($subrebate, '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrebate = 0;
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->DEFAULT_SALES_REPORT_BY_AGENT_HEADER($params);
            $page = $page + $count;
          } //end if
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($value->agentname, '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_SALES_REPORT_BY_AGENT_HEADER($params);
          $page = $page + $count;
        } //end if
      }

      if ($customername != $value->clientname) {
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col($value->clientname, '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_SALES_REPORT_BY_AGENT_HEADER($params);
          $page = $page + $count;
        } //end if
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->dateid, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->docno, '80', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->itemname, '120', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->qty, $decimal_qty), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->uom, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($rebate, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, $decimal_currency), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->ext, $decimal_currency), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cost, $decimal_currency), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cogs, $decimal_currency), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->profit, $decimal_currency), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->status, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->collected, '50', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->refund, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->DEFAULT_SALES_REPORT_BY_AGENT_HEADER($params);
        $page = $page + $count;
      } //end if
      $agentname = $value->agentname;
      $customername = $value->clientname;

      $subqty += $value->qty;
      $subprice += $value->amt;
      $subsales += $value->ext;
      $subcost += $value->cost;
      $subcogs += $value->cogs;
      $subprofit += $value->profit;
      $subrebate += $value->rebate;

      $csubqty += $value->qty;
      $csubprice += $value->amt;
      $csubsales += $value->ext;
      $csubcost += $value->cost;
      $csubcogs += $value->cogs;
      $csubprofit += $value->profit;
      $csubrebate += $value->rebate;

      $grandqty += $value->qty;
      $grandprice += $value->amt;
      $grandsales += $value->ext;
      $grandcost += $value->cost;
      $grandcogs += $value->cogs;
      $grandprofit += $value->profit;
      $grandrebate += $value->rebate;
    } //end for eachs

    // Last part for sub total
    if ($subrebate == 0) {
      $subrebate = '-';
    } else {
      $subrebate = number_format($subrebate, $decimal_currency);
    }

    if ($csubrebate == 0) {
      $csubrebate = '-';
    } else {
      $csubrebate = number_format($csubrebate, $decimal_currency);
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUB-TOTAL', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($csubqty, $decimal_qty), '50', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($csubrebate, '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($csubprice, $decimal_currency), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($csubsales, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($csubcost, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($csubcogs, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($csubprofit, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($agentname . ' GRAND-TOTAL', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($subrebate, '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    // GRAND TOTAL
    if ($grandrebate == 0) {
      $grandrebate = '-';
    } else {
      $grandrebate = number_format($grandrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND-TOTAL', '150', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandqty, $decimal_qty), '50', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($grandrebate, '80', null, false, '2px solid', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandprice, $decimal_currency), '80', null, false, '2px solid', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandsales, $decimal_currency), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcost, $decimal_currency), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcogs, $decimal_currency), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandprofit, $decimal_currency), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_CUSTOMER_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $str = '';
    $startdate = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $clientname = $params['params']['dataparams']['clientname'];
    $agentname = $params['params']['dataparams']['agentname'];
    $category = $params['params']['dataparams']['category'];
    $itemname = $params['params']['dataparams']['itemname'];
    $posttype = $params['params']['dataparams']['posttype'];
    $statustype = $params['params']['dataparams']['statustype'];
    $salestype = $params['params']['dataparams']['salestype'];

    if ($clientname == '') {
      $clientname = 'ALL';
    }

    if ($agentname == '') {
      $agentname = 'ALL';
    }

    if ($category == '') {
      $category = 'ALL';
    }

    if ($itemname == '') {
      $itemname = 'ALL';
    }

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales by Customer Detail', null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Status : ' . strtoupper($statustype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Customer : ' . $clientname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Agent : ' . $agentname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Item : ' . $itemname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('category : ' . $category, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer', '150', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Doc #', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Item', '200', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Qty', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Sale Price', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Rebate', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Unit Cost', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total COGS', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Profit', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Running Balance', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Status', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Refund', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_CUSTOMER($data, $params)
  {
    $str = '';
    $count = 60;
    $page = 59;
    $font = $this->companysetup->getrptfont($params['params']);
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    // decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $decimal_qty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimal_price = $this->companysetup->getdecimal('price', $params['params']);

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_SALES_REPORT_BY_CUSTOMER_HEADER($params);

    $clientname = "";
    $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrunningbal = $subrebate = 0;
    $grandqty = $grandprice = $grandsales = $grandcost = $grandcogs = $grandprofit = $grandrebate = 0;
    $runningbal = 0;
    foreach ($data as $key => $value) {

      $rebate = $value->rebate;

      if ($rebate == 0) {
        $rebate = '-';
      } else {
        $rebate = number_format($rebate, $decimal_currency);
      }

      if ($clientname != $value->clientname) {
        if ($clientname != "") {


          if ($subrebate == 0) {
            $subrebate = '-';
          } else {
            $subrebate = number_format($subrebate, $decimal_currency);
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('Total&nbsp' . $clientname, '150', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col($subrebate, '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subrunningbal, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrebate = 0;
          $runningbal = $subrunningbal = 0;

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->DEFAULT_SALES_REPORT_BY_CUSTOMER_HEADER($params);
            $page = $page + $count;
          } //end if
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($value->clientname, '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_SALES_REPORT_BY_CUSTOMER_HEADER($params);
          $page = $page + $count;
        } //end if
      }

      $runningbal += $value->profit;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->dateid, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->itemname, '200', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->qty, $decimal_qty), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->uom, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($rebate, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->ext, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cost, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cogs, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->profit, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($runningbal, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->status, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->refund, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->DEFAULT_SALES_REPORT_BY_CUSTOMER_HEADER($params);
        $page = $page + $count;
      } //end if
      $clientname = $value->clientname;

      $subqty += $value->qty;
      $subprice += $value->amt;
      $subsales += $value->ext;
      $subcost += $value->cost;
      $subcogs += $value->cogs;
      $subprofit += $value->profit;
      $subrunningbal += $value->profit;
      $subrebate += $value->rebate;

      $grandqty += $value->qty;
      $grandprice += $value->amt;
      $grandsales += $value->ext;
      $grandcost += $value->cost;
      $grandcogs += $value->cogs;
      $grandprofit += $value->profit;
      $grandrebate += $value->rebate;
    } //end for eachs

    // Last part for sub total
    if ($subrebate == 0) {
      $subrebate = '-';
    } else {
      $subrebate = number_format($subrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total&nbsp' . $clientname, '150', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($subrebate, '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subrunningbal, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    // GRAND TOTAL
    if ($grandrebate == 0) {
      $grandrebate = '-';
    } else {
      $grandrebate = number_format($grandrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND-TOTAL', '150', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandqty, $decimal_qty), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');

    $str .= $this->reporter->col(number_format($grandprice, $decimal_currency), '50', null, false, '2px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($grandrebate, '50', null, false, '2px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandsales, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcost, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcogs, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandprofit, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_ITEM_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $str = '';
    $startdate = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $clientname = $params['params']['dataparams']['clientname'];
    $agentname = $params['params']['dataparams']['agentname'];
    $category = $params['params']['dataparams']['category'];
    $itemname = $params['params']['dataparams']['itemname'];
    $posttype = $params['params']['dataparams']['posttype'];
    $statustype = $params['params']['dataparams']['statustype'];
    $salestype = $params['params']['dataparams']['salestype'];

    if ($clientname == '') {
      $clientname = 'ALL';
    }

    if ($agentname == '') {
      $agentname = 'ALL';
    }

    if ($category == '') {
      $category = 'ALL';
    }

    if ($itemname == '') {
      $itemname = 'ALL';
    }
    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales by Item Detail', null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Status : ' . strtoupper($statustype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Customer : ' . $clientname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Agent : ' . $agentname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Item : ' . $itemname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('category : ' . $category, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item', '200', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Doc #', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Customer', '150', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Qty', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Rebate', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Sale Price', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Unit Cost', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total COGS', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Profit', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Running Balance', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Status', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Refund', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_ITEM($data, $params)
  {
    $str = '';
    $count = 60;
    $page = 59;
    $font = $this->companysetup->getrptfont($params['params']);
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    // decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $decimal_qty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimal_price = $this->companysetup->getdecimal('price', $params['params']);

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_SALES_REPORT_BY_ITEM_HEADER($params);

    $itemname = "";
    $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrunningbal = $subrebate = 0;
    $grandqty = $grandprice = $grandsales = $grandcost = $grandcogs = $grandprofit = $grandrebate = 0;
    $runningbal = 0;
    foreach ($data as $key => $value) {

      $rebate = $value->rebate;

      if ($rebate == 0) {
        $rebate = '-';
      } else {
        $rebate = number_format($rebate, $decimal_currency);
      }

      if ($itemname != $value->itemname) {
        if ($itemname != "") {


          if ($subrebate == 0) {
            $subrebate = '-';
          } else {
            $subrebate = number_format($subrebate, $decimal_currency);
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('Total&nbsp' . $itemname, '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col($subrebate, '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subrunningbal, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrebate = 0;
          $runningbal = $subrunningbal = 0;
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->DEFAULT_SALES_REPORT_BY_ITEM_HEADER($params);
            $page = $page + $count;
          } //end if
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($value->itemname, '200', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_SALES_REPORT_BY_ITEM_HEADER($params);
          $page = $page + $count;
        } //end if

      }

      $runningbal += $value->profit;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col('', '200', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->dateid, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->clientname, '150', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->qty, $decimal_qty), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->uom, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($rebate, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->ext, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cost, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cogs, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->profit, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($runningbal, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->status, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->refund, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->DEFAULT_SALES_REPORT_BY_ITEM_HEADER($params);
        $page = $page + $count;
      } //end if
      $itemname = $value->itemname;

      $subqty += $value->qty;
      $subprice += $value->amt;
      $subsales += $value->ext;
      $subcost += $value->cost;
      $subcogs += $value->cogs;
      $subprofit += $value->profit;
      $subrunningbal += $value->profit;
      $subrebate += $value->rebate;

      $grandqty += $value->qty;
      $grandprice += $value->amt;
      $grandsales += $value->ext;
      $grandcost += $value->cost;
      $grandcogs += $value->cogs;
      $grandprofit += $value->profit;
      $grandrebate += $value->rebate;
    } //end for eachs

    // Last part for sub total
    if ($subrebate == 0) {
      $subrebate = '-';
    } else {
      $subrebate = number_format($subrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total&nbsp' . $itemname, '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($subrebate, '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subrunningbal, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    // GRAND TOTAL
    if ($grandrebate == 0) {
      $grandrebate = '-';
    } else {
      $grandrebate = number_format($grandrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND-TOTAL', '200', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandqty, $decimal_qty), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');

    $str .= $this->reporter->col($grandrebate, '50', null, false, '2px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandprice, $decimal_currency), '50', null, false, '2px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandsales, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcost, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcogs, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandprofit, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_BRAND_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $str = '';
    $startdate = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $enddate = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $clientname = $params['params']['dataparams']['clientname'];
    $agentname = $params['params']['dataparams']['agentname'];
    $category = $params['params']['dataparams']['category'];
    $itemname = $params['params']['dataparams']['itemname'];
    $posttype = $params['params']['dataparams']['posttype'];
    $statustype = $params['params']['dataparams']['statustype'];
    $salestype = $params['params']['dataparams']['salestype'];

    if ($clientname == '') {
      $clientname = 'ALL';
    }

    if ($agentname == '') {
      $agentname = 'ALL';
    }

    if ($category == '') {
      $category = 'ALL';
    }

    if ($itemname == '') {
      $itemname = 'ALL';
    }
    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales by Brand Detail', null, null, '', '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', 'C', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Status : ' . strtoupper($statustype), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Customer : ' . $clientname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Agent : ' . $agentname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Item : ' . $itemname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('category : ' . $category, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand', '150', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Doc #', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Item', '200', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Qty', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Sale Price', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Rebate', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total Sales', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Unit Cost', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Total COGS', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Profit', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Running Balance', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Status', '50', null, '', '2px solid ', 'B', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Refund', '50', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function DEFAULT_SALES_REPORT_BY_BRAND($data, $params)
  {
    $str = '';
    $count = 60;
    $page = 59;
    $font = $this->companysetup->getrptfont($params['params']);
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    // decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $decimal_qty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimal_price = $this->companysetup->getdecimal('price', $params['params']);

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_SALES_REPORT_BY_BRAND_HEADER($params);

    $brand = "";
    $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrunningbal = $subrebate = 0;
    $grandqty = $grandprice = $grandsales = $grandcost = $grandcogs = $grandprofit = $grandrebate = 0;
    $runningbal = 0;
    foreach ($data as $key => $value) {

      $rebate = $value->rebate;

      if ($rebate == 0) {
        $rebate = '-';
      } else {
        $rebate = number_format($rebate, $decimal_currency);
      }

      if ($brand != $value->brand) {
        if ($brand != "") {


          if ($subrebate == 0) {
            $subrebate = '-';
          } else {
            $subrebate = number_format($subrebate, $decimal_currency);
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('Total&nbsp' . $brand, '150', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col($subrebate, '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subrunningbal, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $subqty = $subprice = $subsales = $subcost = $subcogs = $subprofit = $subrebate = 0;
          $runningbal = $subrunningbal = 0;

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->DEFAULT_SALES_REPORT_BY_BRAND_HEADER($params);
            $page = $page + $count;
          } //end if
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($value->brand, '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_SALES_REPORT_BY_BRAND_HEADER($params);
          $page = $page + $count;
        } //end if
      }

      $runningbal += $value->profit;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->dateid, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->itemname, '200', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->qty, $decimal_qty), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->uom, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($rebate, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->ext, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cost, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->cogs, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($value->profit, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($runningbal, $decimal_currency), '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->status, '50', null, '', '1px dashed ', 'T', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($value->refund, '50', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->DEFAULT_SALES_REPORT_BY_BRAND_HEADER($params);
        $page = $page + $count;
      } //end if
      $brand = $value->brand;

      $subqty += $value->qty;
      $subprice += $value->amt;
      $subsales += $value->ext;
      $subcost += $value->cost;
      $subcogs += $value->cogs;
      $subprofit += $value->profit;
      $subrunningbal += $value->profit;
      $subrebate += $value->rebate;

      $grandqty += $value->qty;
      $grandprice += $value->amt;
      $grandsales += $value->ext;
      $grandcost += $value->cost;
      $grandcogs += $value->cogs;
      $grandprofit += $value->profit;
      $grandrebate += $value->rebate;
    } //end for eachs

    // Last part for sub total
    if ($subrebate == 0) {
      $subrebate = '-';
    } else {
      $subrebate = number_format($subrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total&nbsp' . $brand, '150', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subqty, $decimal_qty), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprice, $decimal_currency), '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($subrebate, '50', null, false, '1px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subsales, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcost, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subcogs, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subprofit, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subrunningbal, $decimal_currency), '50', null, false, '1px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    // GRAND TOTAL
    if ($grandrebate == 0) {
      $grandrebate = '-';
    } else {
      $grandrebate = number_format($grandrebate, $decimal_currency);
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND-TOTAL', '150', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandqty, $decimal_qty), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');

    $str .= $this->reporter->col(number_format($grandprice, $decimal_currency), '50', null, false, '2px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col($grandrebate, '50', null, false, '2px solid', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandsales, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcost, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandcogs, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandprofit, $decimal_currency), '50', null, false, '2px solid ', 'T', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'T', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn
}//end class