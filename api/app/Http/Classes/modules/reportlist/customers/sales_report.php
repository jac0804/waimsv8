<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class sales_report
{
  public $modulename = 'Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:3000px;max-width:3000px;';
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
      case 63://ericco
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'radioposttype'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'rcustomer');

        data_set( $col1,'radioposttype.options',
        [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'All', 'value' => '2', 'color' => 'teal']
          ]
        );

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        break;

      default:
        $fields = ['radioprint', 'start', 'dwhname', 'dwhref', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        break;
      }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as dwhname,
      '' as wh,
      '' as whname,
      '' as dwhref,
      '' as whref,
      '0'  as reporttype,
      '' as whnameref,
      '' as dclientname,
      '0' as clientid,
      '' as client,
      '' as clientname,
      '0' as posttype";
    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];

      switch ($companyid) {
        case 63://ericco
          $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];
          break;
        default:
           $this->reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];
          break;
        }
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }


  public function detailed_query($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $dwhname = $config['params']['dataparams']['dwhname'];
    $wh = $config['params']['dataparams']['wh'];
    $whname = $config['params']['dataparams']['whname'];
    $dwhref = $config['params']['dataparams']['dwhref'];
    $whref = $config['params']['dataparams']['whref'];
    $whnameref = $config['params']['dataparams']['whnameref'];
    $filter = "";
    $whreffilter = "";
    $whreffilter2 = "";

    if ($dwhname != "") {
      $filter .= " and wh.client = '$wh' ";
    }

    if ($dwhref != "") {
      $filter .= " and whref.client = '$whref' ";
    }

    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));
      $query = "select round(sum(qty),2) as qty, wh,whname,layinghouse,layinghousename,dateid,priceg,itemname, itemid 
              from (select head.wh,wh.clientname as whname, head.dateid,
                          case when head.whref = '' then head.wh else head.whref end as layinghouse,
                          case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename, 
                          item.amt9 as priceg,item.itemname, (ifnull(stock.qty,0)/360) as qty, stock.itemid
                    from lahead as head
                    left join lastock as stock on head.trno = stock.trno
                    left join client on client.client= head.client
                    left join item on item.itemid = stock.itemid
                    left join client as wh on wh.client = head.wh
                    left join client as whref on whref.client = head.whref
                    where head.doc='AJ' and item.category = 8 and head.dateid = '$dates[$dp]' $filter 
                    union all
                    select wh.client as wh,wh.clientname as whname,head.dateid, 
                    case when head.whref = '' then wh.client else head.whref end as layinghouse,
                    case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                          item.amt9 as priceg,item.itemname, (ifnull(stock.qty,0)/360) as qty, stock.itemid
                    from glhead as head
                    left join glstock as stock on head.trno = stock.trno
                    left join client on client.clientid= head.clientid
                    left join item on item.itemid = stock.itemid
                    left join client as wh on wh.clientid = head.whid
                    left join client as whref on whref.client = head.whref
                    where head.doc='AJ' and item.category = 8 and head.dateid = '$dates[$dp]' $filter  ) as a
                group by layinghouse,layinghousename,dateid,priceg,itemname,itemid,wh,whname
                order by layinghouse,layinghousename,itemname";
      $result[$dates[$dp]] = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }


    return $result;
  }

  private function summarized_query($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $dwhname = $config['params']['dataparams']['dwhname'];
    $wh = $config['params']['dataparams']['wh'];
    $whname = $config['params']['dataparams']['whname'];
    $dwhref = $config['params']['dataparams']['dwhref'];
    $whref = $config['params']['dataparams']['whref'];
    $whnameref = $config['params']['dataparams']['whnameref'];
    $filter = "";

    if ($dwhname != "") {
      $filter .= " and wh.client = '$wh' ";
    }

    if ($dwhref != "") {
      $filter .= " and whref.client = '$whref' ";
    }

    $day = date("d", strtotime($config['params']['dataparams']['start']));
    $month = date("m", strtotime($config['params']['dataparams']['start']));
    $year = date("Y", strtotime($config['params']['dataparams']['start']));
    $week = $day + 6;
    $end = "$year-$month-$week";

    $qry = "select layinghouse,layinghousename,sum(totaleggs) as totaleggs, sum(totalpopulation) as totalpopulation,
  sum(latestcost) as lteggs, sum(latestcostfeeds) as ltfeeds
  from(
       select wh,whname,layinghouse,layinghousename,round(sum(qty),2) as totaleggs, 0 as totalpopulation,
       round((sum(qty) * latestcost),2) as latestcost, 0 as latestcostfeeds
       from(select head.wh,wh.clientname as whname, head.dateid,
                   case when head.whref = '' then head.wh else head.whref end as layinghouse,
                   case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                   (ifnull(stock.qty,0)/360) as qty, item.amt9 as latestcost, 0 as latestcostfeeds
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            left join client on client.client= head.client
            left join item on item.itemid = stock.itemid
            left join client as wh on wh.client = head.wh
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and item.category = 8 and head.dateid between '" . $start . "' AND '" . $end . "'
            union all
            select wh.client as wh,wh.clientname as whname,head.dateid,
                   case when head.whref = '' then wh.client else head.whref end as layinghouse,
                   case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                   (ifnull(stock.qty,0)/360) as qty, item.amt9 as latestcost, 0 as latestcostfeeds
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join client on client.clientid= head.clientid
            left join item on item.itemid = stock.itemid
            left join client as wh on wh.clientid = head.whid
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and item.category = 8 and head.dateid between '" . $start . "' AND '" . $end . "') as a
       group by wh,whname,layinghouse,layinghousename,a.latestcost
       union all
       select wh,whname,layinghouse,layinghousename,0 as totaleggs,round(sum(qty),2) as totalpopulation,
       round((sum(qty) * latestcost),2) as latestcost, 0 as latestcostfeeds
       from(select wh.client as wh,wh.clientname as whname,head.dateid,
                   case when head.whref = '' then wh.client else head.whref end as layinghouse,
                   case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                   (stock.qty - stock.iss) as qty, item.amt9 as latestcost, 0 as latestcostfeeds
            FROM glstock AS stock
            left join glhead as head on stock.trno = head.trno
            LEFT JOIN item ON item.itemid= stock.itemid
            left join client as wh on wh.clientid = head.whid
            left join client as whref on whref.client = head.whref
            WHERE item.category = 6 and head.dateid <= '" . $end . "'
            union all
            select head.wh,wh.clientname as whname,head.dateid,
                    case when head.whref = '' then head.wh else head.whref end as layinghouse,
                    case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                    (stock.qty - stock.iss) as qty, item.amt9 as latestcost, 0 as latestcostfeeds
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join item on item.itemid = stock.itemid
            left join client as wh on wh.client = head.wh
            left join client as whref on whref.client = head.whref
            WHERE item.category = 6 and head.dateid <= '" . $end . "' ) as a
       group by wh,whname,layinghouse,layinghousename,a.latestcost
       union all
       select wh,whname,layinghouse,layinghousename,0 as totaleggs,0 as totalpopulation, 0 as latestcost,
       round((sum(qty) * latestcostfeeds),2) as latestcostfeeds
       from(select wh.client as wh,wh.clientname as whname,head.dateid,
                   case when head.whref = '' then wh.client else head.whref end as layinghouse,
                   case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                   stock.iss as qty, 0 as latestcost,item.amt9 as latestcostfeeds
            FROM glstock AS stock
            left join glhead as head on stock.trno = head.trno
            LEFT JOIN item ON item.itemid= stock.itemid
            left join client as wh on wh.clientid = head.whid
            left join client as whref on whref.client = head.whref
            WHERE item.category = 1 and head.dateid between '" . $start . "' AND '" . $end . "'
            union all
            select head.wh,wh.clientname as whname,head.dateid,
                    case when head.whref = '' then head.wh else head.whref end as layinghouse,
                    case when whref.clientname is null then wh.clientname else whref.clientname end as layinghousename,
                    stock.iss as qty,0 as latestcost, item.amt9 as latestcostfeeds
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join item on item.itemid = stock.itemid
            left join client as wh on wh.client = head.wh
            left join client as whref on whref.client = head.whref
            WHERE item.category = 1 and head.dateid between '" . $start . "' AND '" . $end . "' ) as a
       group by wh,whname,layinghouse,layinghousename,a.latestcostfeeds) as tb
  group by layinghouse,layinghousename
  order by layinghouse,layinghousename";

    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  }

  public function query_ericco($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype = $config['params']['dataparams']['posttype'];
    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];
    $filter = "";

    if ($client != '' && $clientid != 0) {
        $filter .= " and client.clientid='$clientid'";
    }

    switch ($posttype) {
      case 0: //Posted
        $query = " 
        select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname, 
            case when head.amount > 0 then head.amount
            else sum(stock.ext) end as amount,
            head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join hsistock as stock on stock.trno = head.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'  $filter
            group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype, head.amount
            union all
            select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname, 
            sum(stock.ext) as amount , head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glstock as stock on stock.trno = num2.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
            group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype
            order by docno
        ";
        break;

        case 1: //Unposted
        $query = "
        select
          'Unposted' as status,
          right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
          head.clientname, 
          case when head.amount > 0 then head.amount
          else sum(stock.ext) end as amount,
          head.address, head.trno, head.vattype
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join sistock as stock on stock.trno = head.trno
          left join client on head.client = client.client
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'   $filter
          group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype, head.amount
          union all
        select
          'Unposted' as status,
          right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
          head.clientname, 
          sum(stock.ext) as amount, head.address, head.trno, head.vattype
          from lahead as head
          left join cntnum as num on num.trno = head.trno
          left join cntnum as num2 on num2.svnum = head.trno
          left join glhead as ghead on ghead.trno = num2.trno
          left join glstock as stock on stock.trno = ghead.trno
          left join client on head.client = client.client
          where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
          group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype
          order by docno
        ";
          break;
        case 2: //All
          $query = "
          select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname, 
            case when head.amount > 0 then head.amount
            else sum(stock.ext) end as amount,
            head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join hsistock as stock on stock.trno = head.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'  $filter
            group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype, head.amount
            union all
            select
          'Posted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname, 
            sum(stock.ext) as amount , head.address, head.trno, head.vattype
            from glhead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glstock as stock on stock.trno = num2.trno
            left join client on head.clientid = client.clientid
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
            group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype
            union all
            select
          'Unposted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname, 
            case when head.amount > 0 then head.amount
            else sum(stock.ext) end as amount,
            head.address, head.trno, head.vattype
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join sistock as stock on stock.trno = head.trno
            left join client on head.client = client.client
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'ch'   $filter
            group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype, head.amount
            union all
            select
          'Unposted' as status,
            right(head.docno, 5) as docno, left(head.dateid,10) as dateid, client.tin,
            head.clientname, 
            sum(stock.ext) as amount, head.address, head.trno, head.vattype
            from lahead as head
            left join cntnum as num on num.trno = head.trno
            left join cntnum as num2 on num2.svnum = head.trno
            left join glhead as ghead on ghead.trno = num2.trno
            left join glstock as stock on stock.trno = ghead.trno
            left join client on head.client = client.client
            where date(head.dateid) between '$start' and '$end'  and head.doc = 'on'   $filter
            group by right(head.docno, 5), clientname, head.dateid, address, trno, client.tin, vattype
            order by docno
          ";
          break;
      }
      // var_dump($query);
    
   return $this->coreFunctions->openTable($query);  
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 63://ericco
        $data = $this->query_ericco($config);
        break;
      default:
        switch ($reporttype) {
          case 0: // summarized query
            $data = $this->summarized_query($config);
            break;

          case 1: // detailed query
            $data = $this->detailed_query($config);
            break;
        }
        break;
      }
    return $data;
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

     switch ($companyid) {
      case 63://ericco
        $result = $this->layout_ericco($config, $this->query_ericco($config));
        break;
      default:
        switch ($reporttype) {
          case 0: // summarized
            $result = $this->salesreport_summarized($config);
            break;

          case 1: // detailed
            $result = $this->salesreport_detailed($config);
            break;
        }
        break;
      }
    return $result;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = $this->coreFunctions->datareader("select date_add('$start', interval 6 day) as value");

    $str = '';
    $count = 38;
    $page = 40;

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES REPORT DETAILED', '400', null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->col(date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '400', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->pagenumber('Page : ', '400', null, false, $border, '', 'R', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }


  public function salesreport_detailed($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

    $this->reporter->linecounter = 0;
    $count = 68;
    $page = 70;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $dt = [];
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));
    }
    $itemdata = [];
    $itemname = "";
    foreach ($result as $key => $value) {
      foreach ($value as $key2 => $value2) {
        $itemdata[$value2['layinghouse']][$value2['itemid']][$key] = $value2['qty'];
      }
    }

    $subt1 = 0;
    $subt2 = 0;
    $subt3 = 0;
    $subt4 = 0;
    $subt5 = 0;
    $subt6 = 0;
    $subt7 = 0;
    $subtotal = 0;

    $t1 = 0;
    $t2 = 0;
    $t3 = 0;
    $t4 = 0;
    $t5 = 0;
    $t6 = 0;
    $t7 = 0;

    $gtotal = $gg = $lt = $ggg = $glt = 0;
    foreach ($itemdata as $q => $p) {

      $layinghousename = json_decode(json_encode($this->coreFunctions->opentable("select clientname from client where client = '$q' ")), true);

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp', '700', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('LAYING HOUSE : ' . $q . ' - ' . $layinghousename[0]['clientname'], '700', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '260', null, false, '1px dotted', 'B', 'C', $font, $fontsize, 'B', '', '');
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $str .= $this->reporter->col(date("Y-m-d", strtotime($interval)), '70', null, false, '1px dotted', 'B', 'r', $font, $fontsize, 'B', '', '');
        $dates[$dp] = date("Y-m-d", strtotime($interval));
      }

      $str .= $this->reporter->col('TOTAL', '80', null, false, '1px dotted', 'B', 'r', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('LATEST PRICE', '90', null, false, '1px dotted', 'B', 'r', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GROSS', '80', null, false, '1px dotted', 'B', 'r', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      foreach ($p as $x => $y) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $itemg = json_decode(json_encode($this->coreFunctions->opentable("select itemname, amt9 from item where itemid = $x ")), true);
        $str .= $this->reporter->col($itemg[0]['itemname'], '260', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
        foreach ($dates as $q => $w) {
          if (array_key_exists($w, $y)) {
            $str .= $this->reporter->col(number_format($y[$w], 2), '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
            $gtotal += $y[$w];
          } else {
            $str .= $this->reporter->col('0.00', '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          }

          if ($q == 1) {
            if (array_key_exists($w, $y)) {
              $t1 += $y[$w];
              $subt1 += $y[$w];
            }
          }

          if ($q == 2) {
            if (array_key_exists($w, $y)) {
              $t2 += $y[$w];
              $subt2 += $y[$w];
            }
          }

          if ($q == 3) {
            if (array_key_exists($w, $y)) {
              $t3 += $y[$w];
              $subt3 += $y[$w];
            }
          }

          if ($q == 4) {
            if (array_key_exists($w, $y)) {
              $t4 += $y[$w];
              $subt4 += $y[$w];
            }
          }

          if ($q == 5) {
            if (array_key_exists($w, $y)) {
              $t5 += $y[$w];
              $subt5 += $y[$w];
            }
          }

          if ($q == 6) {
            if (array_key_exists($w, $y)) {
              $t6 += $y[$w];
              $subt6 += $y[$w];
            }
          }

          if ($q == 7) {
            if (array_key_exists($w, $y)) {
              $t7 += $y[$w];
              $subt7 += $y[$w];
            }
          }
        }
        $subtotal += $gtotal;
        $lt += $itemg[0]['amt9'];
        $gg += ($gtotal * $itemg[0]['amt9']);

        $str .= $this->reporter->col(number_format($gtotal, 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($itemg[0]['amt9'], 2), '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotal * $itemg[0]['amt9'], 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $gtotal = 0;
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUB TOTAL (EGGS) : ', '260', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col(number_format($subt1, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subt2, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subt3, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subt4, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subt5, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subt6, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subt7, 2), '70', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($lt, 2), '90', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($gg, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subt1 = 0;
      $subt2 = 0;
      $subt3 = 0;
      $subt4 = 0;
      $subt5 = 0;
      $subt6 = 0;
      $subt7 = 0;
      $subtotal = 0;
      $glt += $lt;
      $ggg += $gg;
      $lt = 0;
      $gg = 0;
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL (EGGS) : ', '260', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($t1, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t2, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t3, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t4, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t5, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t6, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t7, 2), '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');

    $grandtotal = $t1 + $t2 + $t3 + $t4 + $t5 + $t6 + $t7;
    $str .= $this->reporter->col(number_format($grandtotal, 2), '80', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($glt, 2), '90', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($ggg, 2), '80', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= "</br>";

    ###################################################   CHICKEN

    $chickpopulation = $this->getchickenpopulation($config);

    if (($chickpopulation[$dates[$dp]][0]['population']) != NULL) {

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('POPULATION ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');

      $total = 0;
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $dates[$dp] = date("Y-m-d", strtotime($interval));
        $str .= $this->reporter->col(number_format($chickpopulation[$dates[$dp]][0]["population"], 2), '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        $total += $chickpopulation[$dates[$dp]][0]["population"];
      }
      $str .= $this->reporter->col(number_format($total, 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('POPULATION ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $dates[$dp] = date("Y-m-d", strtotime($interval));
        $str .= $this->reporter->col('0.00', '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('0.00', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }



    #########################################################    % YIELD
    if (($chickpopulation[$dates[$dp]][0]['population']) != NULL) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('% YIELD ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');

      $yield = 0;
      $totalyield = 0;
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $dates[$dp] = date("Y-m-d", strtotime($interval));

        switch ($dp) {
          case 1:
            $totaleggs = $t1;
            break;
          case 2:
            $totaleggs = $t2;
            break;
          case 3:
            $totaleggs = $t3;
            break;
          case 4:
            $totaleggs = $t4;
            break;
          case 5:
            $totaleggs = $t5;
            break;
          case 6:
            $totaleggs = $t6;
            break;
          case 7:
            $totaleggs = $t7;
            break;
        }


        $str .= $this->reporter->col(number_format(($totaleggs / $chickpopulation[$dates[$dp]][0]["population"]) * 100, 2), '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        $totalyield = (($totaleggs / $chickpopulation[$dates[$dp]][0]["population"]) * 100);
      }
      $str .= $this->reporter->col(number_format($totalyield, 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('% YIELD ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $dates[$dp] = date("Y-m-d", strtotime($interval));
        $str .= $this->reporter->col('0.00', '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('0.00', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    ######################################################### FEEDS
    $str .= "</br>";

    $feeds = $this->getfeeds($config);

    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));
    }
    $fitemdata = [];
    $itemname = "";
    foreach ($feeds as $key => $value) {
      foreach ($value as $key2 => $value2) {
        $fitemdata[$value2['itemid']][$key] = $value2['qty'];
      }
    }


    $f1 = 0;
    $f2 = 0;
    $f3 = 0;
    $f4 = 0;
    $f5 = 0;
    $f6 = 0;
    $f7 = 0;
    $feedstotal = 0;

    $feedslt = 0;
    $feedsgg = 0;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FEEDS ', '700', null, false, '1px dotted', 'B', 'l', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    foreach ($fitemdata as $x => $y) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      $feedsitem = json_decode(json_encode($this->coreFunctions->opentable("select itemname,amt9 from item where itemid = $x ")), true);
      $str .= $this->reporter->col($feedsitem[0]['itemname'], '260', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
      foreach ($dates as $q => $w) {
        if (array_key_exists($w, $y)) {
          $str .= $this->reporter->col(number_format($y[$w], 2), '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $feedstotal += $y[$w];
        } else {
          $str .= $this->reporter->col('0.00', '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        }

        if ($q == 1) {
          if (array_key_exists($w, $y)) {
            $f1 += $y[$w];
          }
        }

        if ($q == 2) {
          if (array_key_exists($w, $y)) {
            $f2 += $y[$w];
          }
        }

        if ($q == 3) {
          if (array_key_exists($w, $y)) {
            $f3 += $y[$w];
          }
        }

        if ($q == 4) {
          if (array_key_exists($w, $y)) {
            $f4 += $y[$w];
          }
        }

        if ($q == 5) {
          if (array_key_exists($w, $y)) {
            $f5 += $y[$w];
          }
        }

        if ($q == 6) {
          if (array_key_exists($w, $y)) {
            $f6 += $y[$w];
          }
        }

        if ($q == 7) {
          if (array_key_exists($w, $y)) {
            $f7 += $y[$w];
          }
        }
      }
      $feedslt += $feedsitem[0]['amt9'];
      $feedsgg += ($feedstotal * $feedsitem[0]['amt9']);
      $str .= $this->reporter->col(number_format($feedstotal, 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($feedsitem[0]['amt9'], 2), '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($feedstotal * $feedsitem[0]['amt9'], 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $feedstotal = 0;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '260', null, false, '1px solid', 'T', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL FEEDS : ', '260', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($f1, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($f2, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($f3, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($f4, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($f5, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($f6, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($f7, 2), '70', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');

    $feedsgrandtotal = $f1 + $f2 + $f3 + $f4 + $f5 + $f6 + $f7;
    $str .= $this->reporter->col(number_format($feedsgrandtotal, 2), '80', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($feedslt, 2), '90', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($feedsgg, 2), '80', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= "</br>";
    ############################################################   CONSUMED
    if (($chickpopulation[$dates[$dp]][0]['population']) != NULL) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CONSUMED ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');

      $totalconsumed = 0;
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $dates[$dp] = date("Y-m-d", strtotime($interval));

        switch ($dp) {
          case 1:
            $consumed = ($f1 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
          case 2:
            $consumed = ($f2 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
          case 3:
            $consumed = ($f3 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
          case 4:
            $consumed = ($f4 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
          case 5:
            $consumed = ($f5 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
          case 6:
            $consumed = ($f6 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
          case 7:
            $consumed = ($f7 / $chickpopulation[$dates[$dp]][0]["population"]) * 100;
            break;
        }


        $str .= $this->reporter->col(number_format($consumed, 2), '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
        $totalconsumed += $consumed;
      }
      $str .= $this->reporter->col(number_format($totalconsumed, 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CONSUMED ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');
      $dates = [];
      $dp = 0;
      for ($i = 0; $i <= 6; $i++) {
        $dp = $dp + 1;
        $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
        $dates[$dp] = date("Y-m-d", strtotime($interval));
        $str .= $this->reporter->col('0.00', '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('0.00', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    ############################################################ COST/EGG(FEED)

    $feedscost = $this->getfeedscost($config);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COST/EGG(FEED) ', '260', null, false, '1px dotted', '', 'l', $font, $fontsize, 'B', '', '');

    $totalcost = 0;
    $dates = [];
    $dp = 0;

    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));

      switch ($dp) {
        case 1:
          if ($t1 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t1) * 100;
          }
          break;
        case 2:
          if ($t2 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t2) * 100;
          }
          break;
        case 3:
          if ($t3 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t3) * 100;
          }
          break;
        case 4:
          if ($t4 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t4) * 100;
          }
          break;
        case 5:
          if ($t5 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t5) * 100;
          }
          break;
        case 6:
          if ($t6 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t6) * 100;
          }
          break;
        case 7:
          if ($t7 == 0) {
            $fcost = 0;
          } else {
            $fcost = ($feedscost[$dates[$dp]][0]['cost'] / $t7) * 100;
          }
          break;
      }


      $str .= $this->reporter->col(number_format($fcost, 2), '70', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
      $totalcost += $fcost;
    }
    $str .= $this->reporter->col(number_format($totalcost, 2), '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('-', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('-', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    ############################################################

    $str .= $this->reporter->endreport();

    return $str;
  }


  public function header_summarized($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $day = date("d", strtotime($config['params']['dataparams']['start']));
    $month = date("m", strtotime($config['params']['dataparams']['start']));
    $year = date("Y", strtotime($config['params']['dataparams']['start']));
    $week = $day + 6;
    $end = "$year-$month-$week";

    $str = '';
    $count = 38;
    $page = 40;

    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES REPORT SUMMARIZED', '400', null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->col(date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '400', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->pagenumber('Page : ', '400', null, false, $border, '', 'R', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LAYING HOUSE', '200', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POPULATION(AVERAGE)', '150', null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL NO. OF EGGS', '100', null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YIELD %', '80', null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES', '100', null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEEDS/VITAMINS/VACCINE', '170', null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    return $str;
  }

  public function salesreport_summarized($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $day = date("d", strtotime($config['params']['dataparams']['start']));
    $month = date("m", strtotime($config['params']['dataparams']['start']));
    $year = date("Y", strtotime($config['params']['dataparams']['start']));
    $week = $day + 6;
    $end = "$year-$month-$week";

    $this->reporter->linecounter = 0;
    $count = 68;
    $page = 70;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $dt = [];
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_summarized($config);
    $yield = 0;
    $totalpopulation = 0;
    $totaleggs = 0;
    $totalyield = 0;
    $totallteggs = 0;
    $totalltfeeds = 0;

    for ($i = 0; $i < count($result); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($result[$i]['totaleggs'] == 0 || $result[$i]['totalpopulation'] == 0) {
        $yield = '0.00';
      } else {
        $yield = ($result[$i]['totaleggs'] / $result[$i]['totalpopulation']) * 100;
      }

      $str .= $this->reporter->col($result[$i]['layinghouse'] . ' - ' . $result[$i]['layinghousename'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($result[$i]['totalpopulation'], 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($result[$i]['totaleggs'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($yield, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($result[$i]['lteggs'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($result[$i]['ltfeeds'], 2), '170', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->header_summarized($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }

      $totalpopulation += $result[$i]['totalpopulation'];
      $totaleggs += $result[$i]['totaleggs'];
      $totalyield += $yield;
      $totallteggs += $result[$i]['lteggs'];
      $totalltfeeds += $result[$i]['ltfeeds'];
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalpopulation, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totaleggs, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalyield, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totallteggs, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalltfeeds, 2), '170', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->endreport();

    return $str;
  }

  private function getchickenpopulation($config)
  { //  chicken 6
    $dwhname = $config['params']['dataparams']['dwhname'];
    $wh = $config['params']['dataparams']['wh'];
    $dwhref = $config['params']['dataparams']['dwhref'];
    $whref = $config['params']['dataparams']['whref'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $filter = "";

    if ($dwhname != "") {
      $filter .= " and wh.client = '$wh' ";
    }

    if ($dwhref != "") {
      $filter .= " and whref.client = '$whref' ";
    }

    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));

      $query = "select sum(population) as population from (
              select head.doc,head.dateid,head.docno,(stock.qty - stock.iss) as population
              FROM glstock AS stock
              left join glhead as head on stock.trno = head.trno
              LEFT JOIN item ON item.itemid= stock.itemid
              left join client as wh on wh.clientid = head.whid
              left join client as whref on whref.client = head.whref
              WHERE item.category = 6 and head.dateid <= '$dates[$dp]' $filter
              union all
              select head.doc,head.dateid,head.docno,(stock.qty - stock.iss) as population
              from lastock as stock
              left join lahead as head on head.trno=stock.trno
              left join item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join client as whref on whref.client = head.whref
              where item.category = 6 and head.dateid <= '$dates[$dp]' $filter) as a";
      $result[$dates[$dp]] = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }

    return $result;
  }

  private function getfeeds($config)
  { // feeds 

    $dwhname = $config['params']['dataparams']['dwhname'];
    $wh = $config['params']['dataparams']['wh'];
    $dwhref = $config['params']['dataparams']['dwhref'];
    $whref = $config['params']['dataparams']['whref'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $filter = "";

    if ($dwhname != "") {
      $filter .= " and wh.client = '$wh' ";
    }

    if ($dwhref != "") {
      $filter .= " and whref.client = '$whref' ";
    }

    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));

      $query = "select round(sum(qty),2) as qty, dateid,priceg,itemname, itemid from (
                    select head.docno,head.wh,wh.clientname as whname, head.dateid,
                    item.amt9 as priceg,item.itemname, stock.iss as qty, stock.itemid,stock.uom, uom.factor
                    from lahead as head
                    left join lastock as stock on head.trno = stock.trno
                    left join client on client.client= head.client
                    left join item on item.itemid = stock.itemid
                    left join client as wh on wh.client = head.wh
                    left join client as whref on whref.client = head.whref
              left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
                    where head.doc='SJ' and item.category = 1 and head.dateid = '$dates[$dp]' $filter
                    union all
                    select head.docno,wh.client as wh,wh.clientname as whname,head.dateid,
                    item.amt9 as priceg,item.itemname, stock.iss as qty, stock.itemid,stock.uom, uom.factor
                    from glhead as head
                    left join glstock as stock on head.trno = stock.trno
                    left join client on client.clientid= head.clientid
                    left join item on item.itemid = stock.itemid
                    left join client as wh on wh.clientid = head.whid
                    left join client as whref on whref.client = head.whref
              left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
                    where head.doc='SJ' and item.category = 1 and head.dateid = '$dates[$dp]' $filter
                    ) as a
                    group by dateid,priceg,itemname,itemid
                    order by itemname";
      $result[$dates[$dp]] = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }

    return $result;
  }

  private function getfeedscost($config)
  { // feeds 

    $dwhname = $config['params']['dataparams']['dwhname'];
    $wh = $config['params']['dataparams']['wh'];
    $dwhref = $config['params']['dataparams']['dwhref'];
    $whref = $config['params']['dataparams']['whref'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $filter = "";

    if ($dwhname != "") {
      $filter .= " and wh.client = '$wh' ";
    }

    if ($dwhref != "") {
      $filter .= " and whref.client = '$whref' ";
    }

    $dates = [];
    $dp = 0;
    for ($i = 0; $i <= 6; $i++) {
      $dp = $dp + 1;
      $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
      $dates[$dp] = date("Y-m-d", strtotime($interval));

      $query = "select round(sum(cost),2) as cost from (
                    select head.docno,head.wh,wh.clientname as whname, head.dateid,
                    item.amt9 as priceg,item.itemname, stock.iss as qty, stock.itemid,stock.uom, uom.factor,stock.cost
                    from lahead as head
                    left join lastock as stock on head.trno = stock.trno
                    left join client on client.client= head.client
                    left join item on item.itemid = stock.itemid
                    left join client as wh on wh.client = head.wh
                    left join client as whref on whref.client = head.whref
              left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
                    where head.doc='SJ' and item.category = 1 and head.dateid = '$dates[$dp]' $filter
                    union all
                    select head.docno,wh.client as wh,wh.clientname as whname,head.dateid,
                    item.amt9 as priceg,item.itemname, stock.iss as qty, stock.itemid,stock.uom, uom.factor,stock.cost
                    from glhead as head
                    left join glstock as stock on head.trno = stock.trno
                    left join client on client.clientid= head.clientid
                    left join item on item.itemid = stock.itemid
                    left join client as wh on wh.clientid = head.whid
                    left join client as whref on whref.client = head.whref
              left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
                    where head.doc='SJ' and item.category = 1 and head.dateid = '$dates[$dp]' $filter
                    ) as a";
      $result[$dates[$dp]] = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }

    return $result;
  }

  //ERICCO
  public function header_ericco($config)
  {
    $start = date("F Y", strtotime($config['params']['dataparams']['start']));
    $end = date("F Y", strtotime($config['params']['dataparams']['end']));
    $center     = $config['params']['center'];
    $font = "TAHOMA";
    $layoutsize = 1400;
    $fontsize_title = "12";
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUSIDIARY SALES JOURNAL', null, null, false, $border, '', 'L', $font, $fontsize_title, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
     if ($start == $end) 
    {
      $str .= $this->reporter->col('FOR THE MONTH OF  '. $start, null, null, false, $border, '', 'L', $font, $fontsize_title, '', '', ''); 
    }else{
      $str .= $this->reporter->col('FOR THE MONTHS OF  '. $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $fontsize_title, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .='</br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INVOICE DATE', 150, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NAME OF CUSTOMERS', 400, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INVOICE NO.', 100, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT REG NO.', 120, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS', 650, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INVOICE AMOUNT', 130, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT 12%', 130, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VATABLE SALES', 130, 60, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function layout_ericco($config, $result)
  {
    $start = date("F Y", strtotime($config['params']['dataparams']['start']));
    $end = date("F Y", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = 1480;
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_ericco($config);
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";
    $totalamount = 0;
    $totalvat = 0;
    $totalvatSales = 0;
    $maxRows = 23;
    $rowCount = 0;
    $months = '';
    
    if (empty($result)) {
        return $this->othersClass->emptydata($config);
    }

     if ($start == $end) 
    {
      $months = $start;
    }else{
      $months = $start . ' to ' . $end;
    }

    //extra space
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 150, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 400, 20, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 100, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 120, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 650, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    foreach ($result as $key => $data) {

    if ($rowCount > $maxRows) {
        $str .= $this->reporter->page_break();
        $str .= "</br></br>";
        $rowCount = 0;
    }
    $vat = $data->amount - ($data->amount/1.12);

    if ($data->vattype == 'VATABLE')
    {
      $vat = $data->amount - ($data->amount/1.12);  
    }else {
      $vat = 0;
    }



    if ($data->vattype == 'VATABLE')
    {
      $vatSales = ($data->amount/1.12);    
    }else {
      $vatSales = $data->amount;
    }
    

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(isset($data->dateid) ? date('d-M-y', strtotime($data->dateid)) : '', 150, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(isset($data->clientname) ? $data->clientname : '', 400, null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(isset($data->docno) ? $data->docno : '', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(isset($data->tin) ? $data->tin : '', 120, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(isset($data->address) ? $data->address : '', 650, null, false, $border, 'BLR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format(isset($data->amount) ? $data->amount : 0, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format(isset($vat) ? $vat : 0, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format(isset($vatSales) ? $vatSales : 0, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $rowCount ++;
    $totalamount += $data->amount;
    $totalvat += $vat;
    $totalvatSales += $vatSales;

    }//space
      $str .= $this->reporter->begintable($layoutsize); 
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 150, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 400, 20, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 100, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 120, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 650, 20, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 130, 20, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 150, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Total ' . $months, 400, null, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 650, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalamount, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalvat, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format( $totalvatSales, 2), 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize); 
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 150, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 400, null, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 650, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', 130, null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

    return $str;
  }
}//end class
