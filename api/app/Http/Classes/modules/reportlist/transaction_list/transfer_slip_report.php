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

class transfer_slip_report
{
  public $modulename = 'Transfer Slip Report';
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

    if ($companyid == 21) { //kinggeorge
      $fields = ['radioprint', 'start', 'end', 'reportusers', 'approved'];
    } else {
      $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'approved'];
    }
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group');
        break;
      case 32: //3m
        array_push($fields, 'dclientname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.label', 'Destination');
        data_set($col1, 'dclientname.lookupclass', 'whtslip');
        break;
      case 43: //mighty
        array_push($fields, 'dwhname', 'dclientname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.label', 'Destination');
        data_set($col1, 'dclientname.lookupclass', 'whtslip');
        data_set($col1, 'dwhname.label', 'Source Warehouse');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];

    if ($companyid == 21) { //kinggeorge
      array_push($fields, 'radiolayoutformat');
    }

    $col2 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 11: //summit
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summarized Per Item', 'value' => '2', 'color' => 'orange'],
            ['label' => 'Summarized Per Document', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
          ]
        );
        break;
      case 21: //kinggeorge
        data_set(
          $col2,
          'radiolayoutformat.options',
          [
            ['label' => 'Selling Price', 'value' => 'price', 'color' => 'orange'],
            ['label' => 'Cost', 'value' => 'cost', 'color' => 'orange']
          ]
        );
        break;
        break;
    }

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $companyid = $config['params']['companyid'];
    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start, left(now(),10) as end,'' as wh,'' as whname,'' as whid,'' as userid,
                        '' as username,'' as approved,'0' as posttype,'0' as reporttype,'ASC' as sorting,'' as reportusers,'' as dwhname,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as dclientname,
                        '' as client,
                        '' as clientname,
                        '' as clientid,'' as project, '' as projectid, '' as projectname,
                        '0' as clientid,
                        'price' as layoutformat";

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
      case 11: // summit
        switch ($reporttype) {
          case 0:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case 1:
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
          case 2:
            $result = $this->SUMMIT_summarized_per_item($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case 0:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case 1:
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }
    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    // QUERY

    switch ($companyid) {
      case 11: //summit
        $query = $this->SUMMIT_QUERY($config);
        break;
      default:
        $query = $this->default_QUERY($config);
        break;
    }

    // return $query
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $projectcode = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($companyid != 21) { //not kinggeorge
      $fcenter    = $config['params']['dataparams']['center'];
      if ($fcenter != "") {
        $filter .= " and cntnum.center = '$fcenter'";
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    if ($companyid == 32) { //3m
      $client = $config['params']['dataparams']['client'];
      $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', "client='" . $client . "'");
      if ($client != '') {
        $filter2 = " and head.clientid=" . $clientid;
        $filter3 = " and head.client='" . $client . "'";
      }
    }

    if ($companyid == 43) { //mighty
      $client = $config['params']['dataparams']['client'];
      $clientid = $config['params']['dataparams']['clientid'];
      $wh = $config['params']['dataparams']['wh'];
      $whid = $config['params']['dataparams']['whid'];
      if ($client != '') {
        $filter2 = " and head.clientid=" . $clientid;
        $filter3 = " and head.client='" . $client . "'";
      }
      if ($wh != "") {
        $filter2 .= " and head.whid= " . $whid . "";
        $filter3 .= " and head.wh='" . $wh . "'";
      }
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select * from (
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, head.createby,
          left(head.dateid,10) as dateid, sum(stock.cost * stock.iss) as cost, sum(item.amt * stock.iss) as amt 
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid 
          left join client as wh on wh.clientid = head.whid
          where head.doc='TS' and stock.iss<>0
          and head.dateid between '$start' and '$end' $filter $filter1 $filter2
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";

            break;

          case 1: // unposted
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,head.clientname as supplier,
          sum(stock.ext) as ext, wh.clientname,head.createby,
          left(head.dateid,10) as dateid, sum(stock.cost * stock.iss) as cost, sum(item.amt * stock.iss) as amt
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter $filter1 $filter3
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid 
          ) as g order by g.docno $sorting";
            break;

          case 2: // all
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,head.clientname as supplier,
          sum(stock.ext) as ext, wh.clientname,head.createby,
          left(head.dateid,10) as dateid, sum(stock.cost * stock.iss) as cost, sum(item.amt * stock.iss) as amt
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter $filter1 $filter3
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          UNION ALL
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, head.createby,
          left(head.dateid,10) as dateid, sum(stock.cost * stock.iss) as cost, sum(item.amt * stock.iss) as amt 
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid 
          left join client as wh on wh.clientid = head.whid
          where head.doc='TS' and stock.iss<>0
          and head.dateid between '$start' and '$end' $filter $filter1 $filter2
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,stock.iss,stock.uom" . $barcodeitemnamefield . ",stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem,item.amt,stock.cost
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join client as source on source.clientid=head.whid
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          " . $addjoin . "
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter $filter1 $filter2
          order by docno $sorting
          ";
            break;

          case 1: // unposted
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,stock.iss,stock.uom" . $barcodeitemnamefield . ",stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem,item.amt,stock.cost
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join client as source on source.client=head.wh
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          " . $addjoin . "
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter $filter1 $filter3
          order by docno $sorting
          ";
            break;

          case 2: // all
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,stock.iss,stock.uom" . $barcodeitemnamefield . ",stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem,item.amt,stock.cost
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join client as source on source.client=head.wh
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          " . $addjoin . "
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter $filter1 $filter3
          union all
          select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,stock.iss,stock.uom" . $barcodeitemnamefield . ",stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem,item.amt,stock.cost
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join client as source on source.clientid=head.whid
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          " . $addjoin . "
          where head.doc='TS'  and stock.iss<>0 and head.dateid between '$start' and '$end' $filter $filter1 $filter2
          order by docno $sorting
          ";
            break;
        }
        break;
    }

    return $query;
  }

  public function SUMMIT_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select * from (
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, head.createby,
          left(head.dateid,10) as dateid 
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid 
          left join client as wh on wh.clientid = head.whid
          where head.doc='TS'
          and head.dateid between '$start' and '$end' $filter 
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";
            break;

          case 1: // unposted
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,head.clientname as supplier,
          sum(stock.ext) as ext, wh.clientname,head.createby,
          left(head.dateid,10) as dateid from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='TS' and head.dateid between '$start' and '$end' $filter 
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid 
          ) as g order by g.docno $sorting";
            break;

          case 2: // all
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,head.clientname as supplier,
          sum(stock.ext) as ext, wh.clientname,head.createby,
          left(head.dateid,10) as dateid from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='TS' and head.dateid between '$start' and '$end' $filter 
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          UNION ALL
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, head.createby,
          left(head.dateid,10) as dateid from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid 
          left join client as wh on wh.clientid = head.whid
          where head.doc='TS'
          and head.dateid between '$start' and '$end' $filter 
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,item.barcode,stock.iss,stock.uom,item.itemname,stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join client as source on source.clientid=head.whid
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter
          order by docno $sorting
          ";
            break;

          case 1: // unposted
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,item.barcode,stock.iss,stock.uom,item.itemname,stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join client as source on source.client=head.wh
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter
          order by docno $sorting
          ";
            break;

          case 2: // all
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,item.barcode,stock.iss,stock.uom,item.itemname,stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join client as source on source.client=head.wh
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          where head.doc='TS' and stock.iss<>0 and head.dateid between '$start' and '$end' $filter
          union all
          select head.docno,source.clientname as whsource,head.clientname as whdestination,
          head.dateid,item.barcode,stock.iss,stock.uom,item.itemname,stock.isamt,stock.isqty,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join client as source on source.clientid=head.whid
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          where head.doc='TS'  and stock.iss<>0 and head.dateid between '$start' and '$end' $filter
          order by docno $sorting
          ";
            break;
        }
        break;
      case 2: // summarize per item
        switch ($posttype) {
          case 0: // posted
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.iss as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.clientid = head.whid
              left join cntnum on cntnum.trno=head.trno
              where head.doc = 'TS' and head.dateid between '$start' and '$end' $filter) as a
              group by whname, itemname, uom
              order by whname " . $sorting . "";
            break;
          case 1: // unposted
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.iss as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join cntnum on cntnum.trno=head.trno
              where head.doc = 'TS' and head.dateid between '$start' and '$end' $filter) as a
              group by whname, itemname, uom
              order by whname " . $sorting . "";
            break;
          case 2: // all
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.iss as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join cntnum on cntnum.trno=head.trno
              where head.doc = 'TS' and head.dateid between '$start' and '$end' $filter
              union all
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.iss as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.clientid = head.whid
              left join cntnum on cntnum.trno=head.trno
              where head.doc = 'TS' and head.dateid between '$start' and '$end' $filter) as a
              group by whname, itemname, uom
              order by whname " . $sorting . "";
            break;
        }
        break;
    }

    return $query;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $companyid = $config['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];



    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;

    $PriceLabel = 'Price';
    $TotalPriceLabel = 'Total Price';

    if ($companyid == 21) { //kinggeorge
      if ($config['params']['dataparams']['layoutformat'] == 'cost') {
        $PriceLabel = 'Cost';
        $TotalPriceLabel = 'Total Cost';
      }
    }

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Source: ' . $data->whsource, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Destination: ' . $data->whdestination, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', $fontsize, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col($PriceLabel, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col($TotalPriceLabel, '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $price = $data->isamt;
        $ext = $data->ext;

        if ($companyid == 21) { // kinggeorge
          if ($config['params']['dataparams']['layoutformat'] == 'cost') {
            $price = $data->cost;
            $ext = $data->cost * $data->iss;
          } else if ($config['params']['dataparams']['layoutformat'] == 'price') {
            $price = $data->amt;
            $ext = $data->amt * $data->iss;
          }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isqty, $decimal_currency), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($price, $decimal_currency), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $ext;
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

  public function header_DEFAULT($config)
  {
    $companyid  = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['wh'];
    $clientname = $config['params']['dataparams']['whname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

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

    switch ($companyid) {
      case 11: //summit
        if ($reporttype == 0) {
          $reporttype = 'Summarized Per Document';
        } elseif ($reporttype == 2) {
          $reporttype = 'Summarized Per Item';
        } else {
          $reporttype = 'Detailed';
        }
        break;
      default:
        if ($reporttype == 0) {
          $reporttype = 'Summarized';
        } else {
          $reporttype = 'Detailed';
        }
        break;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    $str = '';


    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transfer Slip Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User : ' . $user, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type : ' . $posttype, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sorting by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
      case 32: //3m
        $clientname = $config['params']['dataparams']['clientname'];
        if ($clientname == '') $clientname = 'ALL';
        $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '350', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Destination: ' . strtoupper($clientname), null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        break;
      default:
        $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        break;
    }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['wh'];
    $clientname = $config['params']['dataparams']['whname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $ext = $data->ext;
        if ($companyid == 21) { //kinggeorge
          if ($config['params']['dataparams']['layoutformat'] == 'cost') {
            $ext = $data->cost;
          } else if ($config['params']['dataparams']['layoutformat'] == 'price') {
            $ext = $data->amt;
          }
        }

        $str .= $this->reporter->col(number_format($ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $companyid = $config['params']['companyid'];

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $PriceLabel = 'AMOUNT';
    if ($companyid == 21) { //kinggeorge
      if ($config['params']['dataparams']['layoutformat'] == 'cost') {
        $PriceLabel = 'COST';
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SOURCE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DESTINATION', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($PriceLabel, '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function SUMMIT_summarized_per_item($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['wh'];
    $clientname = $config['params']['dataparams']['whname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $whname = "";
    $subtotal = 0;
    $grandtotal = 0;

    $counter = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        if ($whname != $data->whname) {
          if ($whname != "") {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col("SUBTOTAL: ", '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $subtotal = 0;
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("Warehouse: " . $data->whname, '100', null, false, $border, '', 'L', $font, '14', 'B', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ITEMNAME', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->reporter->begintable($layoutsize);
          $page = $page + $count;
        } //end if

        $whname = $data->whname;
        $subtotal += $data->amount;
        $grandtotal += $data->amount;
        $counter++;

        if ($counter == count($result)) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("SUBTOTAL: ", '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class