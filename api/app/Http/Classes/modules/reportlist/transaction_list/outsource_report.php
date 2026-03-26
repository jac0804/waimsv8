<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class outsource_report
{
  public $modulename = 'Outsource Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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

    $fields = ['radioprint', 'start', 'end', 'telesales', 'ostech'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'telesales.lookupclass', 'telesales');
    data_set($col1, 'telesales.type', 'lookup');
    data_set($col1, 'telesales.action', 'lookupclient');
    data_set($col1, 'telesales.readonly', true);

    data_set($col1, 'ostech.lookupclass', 'ostech');
    data_set($col1, 'ostech.type', 'lookup');
    data_set($col1, 'ostech.action', 'lookupclient');
    data_set($col1, 'ostech.readonly', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting', 'radiostatus'];
    $col2 = $this->fieldClass->create($fields);
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    data_set(
      $col2,
      'radiostatus.options',
      [
        ['label' => 'With PO', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Without PO', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $centername = $this->coreFunctions->datareader("select name as value from center where code = '" . $center . "'");
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as telesales,
      '' as telesalesid,
      '' as ostech,
      '' as ostechid,
      '0' as posttype,
      '0' as reporttype, 
      '2' as status,
      'ASC' as sorting,
      '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'0' as clientid
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        switch ($reporttype) {
          case 0: // summarized
            $result = $this->afti_summarized($config);
            break;

          case 1: // detailed
            $result = $this->afti_os_detailed($config);
            break;
        }
        break;

      default: // default
        switch ($reporttype) {
          case 0: // summarized
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;

          case 1: // detailed
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;

          case 2:
            $result = $this->reportDefaultLayout_SUMMARYPERITEM($config);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $query = $this->afti_query($config);
        break;
      default:
        switch ($reporttype) {
          case 2:
            $query = $this->SUMMIT_QUERY($config);
            break;
          default:
            $query = $this->default_QUERY($config);
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function afti_query($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $telesalesid     = $config['params']['dataparams']['telesalesid'];
    $ostechid     = $config['params']['dataparams']['ostechid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $status   = $config['params']['dataparams']['status'];
    $filter = "";

    if ($telesalesid != "") {
      $filter .= " and tele.clientid = '$telesalesid' ";
    }
    if ($ostechid != "") {
      $filter .= " and os.clientid = '$ostechid' ";
    }

    if ($status == 0) {
      $filter .= " and head.ourref <> ''";
    }

    if ($status == 1) {
      $filter .= " and head.ourref = ''";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource, head.rem, date(head.datesent) as datesent, head.datequote, 
          date(head.dateforward) as dateforward, stock.currency, i.itemname as afticodes, stock.cost,i.itemid,head.customerid
          from hoshead as head
          left join hosstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname, head.rem, date(head.datesent), head.datequote, head.dateforward, stock.currency, i.itemname, stock.cost,i.itemid
          order by docno $sorting";


            break;

          case 1: // unposted
            $query = "select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource, head.rem, date(head.datesent) as datesent, head.datequote, 
          date(head.dateforward) as dateforward, stock.currency, i.itemname as afticodes, stock.cost,i.itemid,head.customerid
          from oshead as head
          left join osstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname, head.rem, date(head.datesent), head.datequote, head.dateforward, stock.currency, i.itemname, stock.cost,i.itemid
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource, head.rem, date(head.datesent) as datesent, head.datequote, 
          date(head.dateforward) as dateforward, stock.currency, i.itemname as afticodes, stock.cost,i.itemid,head.customerid
          from oshead as head
          left join osstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname, head.rem,date(head.datesent), head.datequote, head.dateforward, stock.currency, i.itemname, stock.cost,i.itemid
          union all
          select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource, head.rem, date(head.datesent) as datesent, head.datequote, 
          date(head.dateforward) as dateforward, stock.currency, i.itemname as afticodes, stock.cost,i.itemid,head.customerid
          from hoshead as head
          left join hosstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname, head.rem,date(head.datesent), head.datequote, head.dateforward, stock.currency, i.itemname, stock.cost,i.itemid
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
          select item.itemid,head.docno, date(head.dateid) as dateid, ifnull(cl.clientname,'') as customername, ifnull(item.itemname,'') as itemname, 
          ifnull(b.brand_desc,'') as brandname, ifnull(item.partno,'') as partno, ifnull(info.itemdescription,'') as itemdesc, 
          ifnull(stock.qty,0) as qty, ifnull(item.amt,0) as price, ifnull(stock.cost,0) as cost, ifnull(tele.clientname, '') as agentname,head.customerid
          from hoshead as head
          left join hosstock as stock on head.trno = stock.trno
          left join client as cl on cl.clientid = head.customerid
          left join item on stock.itemid = item.itemid
          left join frontend_ebrands as b on b.brandid = item.brand
          left join iteminfo as info on info.itemid = item.itemid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where doc = 'os' and date(head.dateid) between '$start' and '$end' $filter
          group by item.itemid, head.docno, date(head.dateid), cl.clientname, item.itemname, 
          b.brand_desc, item.partno, info.itemdescription, stock.qty,
          item.amt, stock.cost, tele.clientname,head.customerid
          order by dateid,docno $sorting
          ";

            break;

          case 1: // unposted
            $query = "select item.itemid,head.docno, date(head.dateid) as dateid, ifnull(cl.clientname,'') as customername, 
          ifnull(item.itemname,'') as itemname, 
          ifnull(b.brand_desc,'') as brandname, ifnull(item.partno,'') as partno, ifnull(info.itemdescription,'') as itemdesc, 
          ifnull(stock.qty,0) as qty, ifnull(item.amt,0) as price, ifnull(stock.cost,0) as cost, ifnull(tele.clientname, '') as agentname,head.customerid
          from oshead as head
          left join osstock as stock on head.trno = stock.trno
          left join client as cl on cl.clientid = head.customerid
          left join item on stock.itemid = item.itemid
          left join frontend_ebrands as b on b.brandid = item.brand
          left join iteminfo as info on info.itemid = item.itemid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where doc = 'os' and date(head.dateid) between '$start' and '$end' $filter
          group by item.itemid,head.docno, date(head.dateid), cl.clientname, item.itemname, 
          b.brand_desc, item.partno, info.itemdescription, stock.qty,
          item.amt, stock.cost, tele.clientname,head.customerid
          order by dateid,docno $sorting";
            break;

          default: // all
            $query = "
          select item.itemid,head.docno, date(head.dateid) as dateid, ifnull(cl.clientname,'') as customername, ifnull(item.itemname,'') as itemname, 
          ifnull(b.brand_desc,'') as brandname, ifnull(item.partno,'') as partno, ifnull(info.itemdescription,'') as itemdesc, 
          ifnull(stock.qty,0) as qty, ifnull(item.amt,0) as price, ifnull(stock.cost,0) as cost, ifnull(tele.clientname, '') as agentname,head.customerid
          from hoshead as head
          left join hosstock as stock on head.trno = stock.trno
          left join client as cl on cl.clientid = head.customerid
          left join item on stock.itemid = item.itemid
          left join frontend_ebrands as b on b.brandid = item.brand
          left join iteminfo as info on info.itemid = item.itemid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where doc = 'os' and date(head.dateid) between '$start' and '$end' $filter
          group by item.itemid,head.docno, date(head.dateid), cl.clientname, item.itemname, 
          b.brand_desc, item.partno, info.itemdescription, stock.qty,
          item.amt, stock.cost, tele.clientname,head.customerid
          union all
          select item.itemid,head.docno, date(head.dateid) as dateid, ifnull(cl.clientname,'') as customername, ifnull(item.itemname,'') as itemname, 
          ifnull(b.brand_desc,'') as brandname, ifnull(item.partno,'') as partno, ifnull(info.itemdescription,'') as itemdesc, 
          ifnull(stock.qty,0) as qty, ifnull(item.amt,0) as price, ifnull(stock.cost,0) as cost, ifnull(tele.clientname, '') as agentname,head.customerid
          from oshead as head
          left join osstock as stock on head.trno = stock.trno
          left join client as cl on cl.clientid = head.customerid
          left join item on stock.itemid = item.itemid
          left join frontend_ebrands as b on b.brandid = item.brand
          left join iteminfo as info on info.itemid = item.itemid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where doc = 'os' and date(head.dateid) between '$start' and '$end' $filter
          group by item.itemid,head.docno, date(head.dateid), cl.clientname, item.itemname, 
          b.brand_desc, item.partno, info.itemdescription, stock.qty,
          item.amt, stock.cost, tele.clientname,head.customerid
          order by dateid,docno $sorting
          ";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }

  public function default_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $telesalesid     = $config['params']['dataparams']['telesalesid'];
    $ostechid     = $config['params']['dataparams']['ostechid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];


    $filter = "";

    if ($telesalesid != "") {
      $filter .= " and tele.clientid = '$telesalesid' ";
    }
    if ($ostechid != "") {
      $filter .= " and os.clientid = '$ostechid' ";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource
          from hoshead as head
          left join hosstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname
          order by docno $sorting";


            break;

          case 1: // unposted
            $query = "select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource
          from oshead as head
          left join osstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource
          from oshead as head
          left join osstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname
          union all
          select head.docno,head.yourref as refno,
          case when head.customerid != 0 then client.clientname else head.customer end as company,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,
          tele.clientname as telesales,os.clientname as ostech,
          count(i.itemname) as totalsource
          from hoshead as head
          left join hosstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.customerid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          left join item as i on i.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          where head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.yourref,head.customerid,client.clientname,head.customer,
          head.lineitem,head.crossref,head.nooffertotal,head.nobidtotal,tele.clientname,os.clientname
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from hosstock as stock
          left join hoshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where head.doc='OS' and head.dateid between '$start' and '$end' $filter 
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from osstock as stock
          left join oshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where head.doc='OS' and head.dateid between '$start' and '$end' $filter 
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from osstock as stock
          left join oshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where head.doc='OS' and head.dateid between '$start' and '$end' $filter
          union all
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from hosstock as stock
          left join hoshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join client as tele on tele.clientid=head.telesalesid
          left join client as os on os.clientid=head.ostechid
          where head.doc='OS' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }

  public function SUMMIT_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from hosstock as stock
          left join hoshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='OS'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting
          ";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from osstock as stock
          left join oshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='OS'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting";
        break;

      default: // all
        $query = "
            select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
            sum(stock.ext) as ext
            from osstock as stock
            left join oshead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join transnum on transnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            where head.doc='OS'
            and date(head.dateid) between '$start' and '$end' $filter 
            group by wh.clientname, wh.client, item.itemname,item.uom
            UNION ALL
            select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
            sum(stock.ext) as ext
            from hosstock as stock
            left join hoshead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join transnum on transnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            where head.doc='OS'
            and date(head.dateid) between '$start' and '$end' $filter 
            group by wh.clientname, wh.client, item.itemname,item.uom
            order by clientname,itemname $sorting";
        break;
    } // end switch posttype

    return $query;
  }

  public function afti_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $status = $config['params']['dataparams']['status'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    switch ($status) {
      case 0:
        $status = 'With PO';
        break;

      case 1:
        $status = 'Without PO';
        break;

      default:
        $status = 'All';
        break;
    }

    if ($companyid == 10) { //afti
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
    $layoutsize = 1200;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Outsource Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status :'. $status, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '200', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '400', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Brand', '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Part No.', '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '500', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Rate', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Value in SGD', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function afti_os_detailed($config)
  {
    $result = $this->reportDefault($config);
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $status   = $config['params']['dataparams']['status'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    switch ($status) {
      case 0:
        $status = 'With PO';
        break;

      case 1:
        $status = 'Without PO';
        break;

      default:
        $status = 'All';
        break;
    }

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $sgdamt = 0;
    $phptosgd = $this->othersClass->getexchangerate('PHP', 'SGD');

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->afti_header_detailed($config);



    if (!empty($result)) {
      foreach ($result as $key => $data) {
        // if ($status == 0 || $status == 1) {
        //   $this->coreFunctions->LogConsole($status);
        //   $itemid = $data->itemid;
        //   $exist = $this->coreFunctions->datareader("select ifnull(trno,0) as value from (select sq.trno from hqsstock as qss left join hqshead as qsh on qsh.trno = qss.trno
        //   left join hsqhead as sq on sq.trno = qsh.sotrno where qss.itemid =?
        //   union all
        //   select sq.trno from hqtstock as qss left join hqshead as qsh on qsh.trno = qss.trno
        //   left join hsrhead as sr on sr.qtrno = qsh.trno
        //   left join hsrstock as srs on srs.trno = sr.trno
        //   left join hsshead as sq on sq.trno = sr.trno where srs.itemid =?) as a limit 1", [$itemid, $itemid],'',true);
        //   if ($status == 0) {//with PO
        //     if (floatval($exist) != 0) {
        //       goto withPO;
        //    }
        //   }

        //   if ($status == 1) {//without PO
        //     if (floatval($exist) == 0) {
        //       $this->coreFunctions->LogConsole($itemid);
        //       goto withPO;
        //     }
        //   }
        // }else{
        withPO:
        $sgdamt = $data->price * $phptosgd;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '200', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customername, '400', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->brandname, '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->partno, '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemdesc, '500', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('PHP ' . number_format($data->cost, 2), '120', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('PHP ' . number_format($data->qty * $data->cost, 2), '120', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($sgdamt, 2), '120', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp' . $data->agentname, '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->afti_header_detailed($config);
          $page = $page + $count;
        }
        //}
        //end if

      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_DEFAULT($config);
    $docno = "";
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          if ($companyid == 10) { //afti
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Supplier: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT_OS($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $telesales     = $config['params']['dataparams']['telesales'];
    $telesalesid     = $config['params']['dataparams']['telesalesid'];
    $ostech     = $config['params']['dataparams']['ostech'];
    $ostechid     = $config['params']['dataparams']['ostechid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($telesales == "") {
      $telesales = "ALL";
    }
    if ($ostech == "") {
      $ostech = "ALL";
    }

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Outsource Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Telesales : ' . $telesales, '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OS Technical : ' . $ostech, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    return $str;
  }

  public function tableheader_OS($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REF#', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COMPANY', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LINE ITEM TOTAL', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CROSS REF TOTAL', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO OFFER TOTAL', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO BID TOTAL', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL SOURCED', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TELESALES', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OS TECHNICAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "8";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT_OS($config);
    $str .= $this->tableheader_OS($layoutsize, $config);

    $str .= $this->reporter->begintable($layoutsize);
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->refno, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->company, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->lineitem, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->crossref, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->nooffertotal, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->nobidtotal, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->totalsource, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->telesales, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ostech, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT_OS($config);
          $str .= $this->tableheader_OS($layoutsize, $config);
          $str .= $this->reporter->begintable($layoutsize);
          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARYPERITEM($config)
  {
    $result = $this->reportDefault($config);
    $client     = $config['params']['dataparams']['client'];
    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $client = "";
    $total = 0;
    $i = 0;
    $totalext = 0;
    $totalqty = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($client != "" && $client != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($client == "" || $client != $data->clientname) {
          $client = $data->clientname;
          $totalqty = 0;
          $totalext = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize + 5, 'B', '', '', '8px');

          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ITEM', '425', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '425', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($client == $data->clientname) {
          $totalext += $data->ext;
          $totalqty += $data->qty;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    if ($companyid == 10) { //afti
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
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Outsource Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10) { //afti
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function header_detailed_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    if ($companyid == 10) { //afti
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
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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
    $str .= $this->reporter->col('Outsource Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10) { //afti
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function afti_summarized($config)
  {
    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;
    $price = $this->companysetup->getdecimal('price', $config['params']);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = 1600;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->afti_summarized_header($config, $layoutsize);
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        // if ($status == 0 || $status == 1) {
        //   $this->coreFunctions->LogConsole($status);
        //   $itemid = $data->itemid;
        //   $clientid = $data->customerid;
        //   $exist = $this->coreFunctions->datareader("select ifnull(trno,0) as value from (select sq.trno from hqsstock as qss left join hqshead as qsh on qsh.trno = qss.trno
        //   left join hsqhead as sq on sq.trno = qsh.sotrno left join client on client.client = qsh.client where qss.itemid =? and client.clientid = ?
        //   union all
        //   select sq.trno from hqtstock as qss left join hqshead as qsh on qsh.trno = qss.trno
        //   left join hsrhead as sr on sr.qtrno = qsh.trno
        //   left join hsrstock as srs on srs.trno = sr.trno
        //   left join hsshead as sq on sq.trno = sr.trno left join client on client.client = sr.client where srs.itemid =? and client.clientid = ?) as a limit 1", [$itemid,$clientid, $itemid,$clientid],'',true);
        //   if ($status == 0) {//with PO
        //     if (floatval($exist) != 0) {
        //       goto withPO;
        //    }
        //   }

        //   if ($status == 1) {//without PO
        //     if (floatval($exist) == 0) {
        //       $this->coreFunctions->LogConsole($itemid);
        //       goto withPO;
        //     }
        //   }
        // } else {
        withPO:
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->refno, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->company, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->lineitem, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->crossref, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->nooffertotal, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->nobidtotal, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->totalsource, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->datesent, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->datequote, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->afticodes, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        switch (strtoupper($data->currency)) {
          case 'USD':
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->cost, $price), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            break;

          case 'SGD':
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->cost, $price), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            break;

          default: // PHP
            $str .= $this->reporter->col(number_format($data->cost, $price), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            break;
        }

        $str .= $this->reporter->col($data->dateforward, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->telesales, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ostech, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->afti_summarized_header($config, $layoutsize);
          $page = $page + $count;
        } //end if

        //}
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function afti_summarized_header($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $telesales     = $config['params']['dataparams']['telesales'];
    $ostech     = $config['params']['dataparams']['ostech'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $status   = $config['params']['dataparams']['status'];

    if ($telesales == "") {
      $telesales = "ALL";
    }
    if ($ostech == "") {
      $ostech = "ALL";
    }

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    switch ($status) {
      case 0:
        $status = 'With PO';
        break;

      case 1:
        $status = 'Without PO';
        break;

      default:
        $status = 'All';
        break;
    }

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Outsource Report (' . $reporttype . ')', '1000', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Telesales : ' . $telesales, '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OS Technical : ' . $ostech, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status : ' . $status, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REF#', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COMPANY', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LINE<br>ITEM<br>TOTAL', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CROSS<br>REF<br>TOTAL', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO<br>OFFER<br>TOTAL', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NO<br>BID<br>TOTAL', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL SOURCED', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE SENT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE AFTECH QUOTED', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AFTI CODES', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PRICE (PHP)&nbsp;', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PRICE (USD)&nbsp;', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PRICE (SGD)&nbsp;', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE FORWARDED TO TELE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOTES', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TELESALES', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OS TECHNICAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class