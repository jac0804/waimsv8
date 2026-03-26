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

class supplier_invoice
{
  public $modulename = 'Supplier Invoice';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dwhname', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
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
    left(now(),10) as `end`,
    '' as client,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '' as wh,
    '' as whname,
    '' as dwhname,
    '' as reportusers,
    '' as dclientname,
    '0' as whid
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
    $reporttype = $config['params']['dataparams']['reporttype'];
    $data = $this->reportDefault($config);
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $result = $this->DEFAULT_SV_SUMMARIZED($config, $data);
        break;
      case '1': // DETAILED
        $result = $this->DEFAULT_SV_DETAILED($config, $data);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $query = $this->default_QUERY_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $query = $this->default_QUERY_DETAILED($config);
        break;
    }

    $array = $this->coreFunctions->opentable($query);
    $result = json_decode(json_encode($array), true); // for convert to array
    return $result;
  }

  public function default_QUERY_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh    = $config['params']['dataparams']['wh'];
    $whid    = $config['params']['dataparams']['whid'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($wh != "") {
      $filter .= " and client.clientid = '$whid' ";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( 
        select head.docno,head.clientname as supplier, head.ourref, item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
        from glstock as stock
        left join glhead as rrhead on rrhead.trno=stock.trno
        left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
        left join glhead as head on rrcntnum.svnum=head.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as supp on supp.clientid=head.clientid
        where head.doc='SN' and head.dateid between '$start' and '$end' and cntnum.center='$center' $filter
        ) as a order by dateid $sorting";
        break;

      case 1: // unposted
        $query = "select * from ( 
        select head.docno,head.clientname as supplier, head.ourref, item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
        from glstock as stock
        left join glhead as rrhead on rrhead.trno=stock.trno
        left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
        left join lahead as head on rrcntnum.svnum=head.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as supp on supp.client=head.client
        where head.doc='SN' and 
        head.dateid between '$start' and '$end' and cntnum.center='$center' $filter 
        ) as a order by dateid $sorting";
        break;

      default: // sana all
        $query = "select * from ( 
        select head.docno,head.clientname as supplier, head.ourref, item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
        from glstock as stock
        left join glhead as rrhead on rrhead.trno=stock.trno
        left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
        left join lahead as head on rrcntnum.svnum=head.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as supp on supp.client=head.client
        where head.doc='SN' and 
        head.dateid between '$start' and '$end' and cntnum.center='$center' $filter 
        union all
        select head.docno,head.clientname as supplier, head.ourref, item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
        from glstock as stock
        left join glhead as rrhead on rrhead.trno=stock.trno
        left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
        left join glhead as head on rrcntnum.svnum=head.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as supp on supp.clientid=head.clientid
        where head.doc='SN' and head.dateid between '$start' and '$end' and cntnum.center='$center' $filter
        ) as a order by dateid $sorting";
        break;
    }

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh    = $config['params']['dataparams']['wh'];
    $whid    = $config['params']['dataparams']['whid'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($wh != "") {
      $filter .= " and client.clientid = '$whid' ";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select docno, dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem 
      from (
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
      ,client.client as wh,head.rem as hrem
      from glstock as stock
      left join glhead as rrhead on rrhead.trno=stock.trno
      left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
      left join glhead as head on rrcntnum.svnum=head.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as supp on supp.clientid=head.clientid
      where head.doc='SN' and head.dateid between '$start' and '$end' and cntnum.center='$center' $filter
      ) as a group by docno,dateid,supplier,wh,clientname,hrem
      order by dateid $sorting";
        break;

      case 1: // unposted
        $query = "select docno, dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem from ( select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako,client.client as wh,head.rem as hrem
      from glstock as stock
      left join glhead as rrhead on rrhead.trno=stock.trno
      left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
      left join lahead as head on rrcntnum.svnum=head.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.client=stock.whid
      left join client as supp on supp.client=head.client
      where head.doc='SN' and head.dateid between '$start' and '$end' and cntnum.center='$center' $filter
      ) as a group by docno,dateid,supplier,wh,clientname,hrem
      order by dateid $sorting";
        break;

      default: // sana all
        $query = "select docno, dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem from ( select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako,client.client as wh,head.rem as hrem
      from glstock as stock
      left join glhead as rrhead on rrhead.trno=stock.trno
      left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
      left join glhead as head on rrcntnum.svnum=head.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as supp on supp.clientid=head.clientid
      where head.doc='SN' and head.dateid between '$start' and '$end' and cntnum.center='$center' $filter
      union all
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
      ,client.client as wh,head.rem as hrem
      from glstock as stock
      left join glhead as rrhead on rrhead.trno=stock.trno
      left join cntnum as rrcntnum on rrcntnum.trno=rrhead.trno
      left join lahead as head on rrcntnum.svnum=head.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as supp on supp.client=head.client
      where head.doc='SN' and head.dateid between '$start' and '$end' and cntnum.center='$center' $filter
      ) as a group by docno,dateid,supplier,wh,clientname,hrem
      order by dateid $sorting";
        break;
    }

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '10', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '10', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '10', 'B', '', '') . '<br />';
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
    $str .= $this->reporter->col('Receiving Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_SV_DETAILED($config, $data)
  {
    $pagenumber = 1;
    $count = 6;
    $page = 6;
    $str = "";


    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh    = $config['params']['dataparams']['wh'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
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
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= '<br/><br/>';

    $str .= $this->tableHead('1000', 'SUPPLIER INVOICE', $config);

    $str .= $this->reporter->printline();
    $docno = "";
    $total = 0;

    for ($i = 0; $i < count($data); $i++) {
      if ($docno != "" && $docno != $data[$i]['docno']) {
        $str .= $this->reporter->begintable('600');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ' . number_format($total, $decimal_currency), '900', null, false, '1px solid', '', 'R', 'Century Gothic', '14', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      if ($docno == "" || $docno != $data[$i]['docno']) {
        $docno = $data[$i]['docno'];
        $total = 0;

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc#: ' . $data[$i]['docno'], '125', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
        $str .= $this->reporter->col('Date: ' . $data[$i]['dateid'], '125', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $data[$i]['supplier'], '125', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
        $str .= $this->reporter->col('Ourref: ' . $data[$i]['ourref'], '125', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Barcode', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Item Description', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Quantity', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Price', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Discount', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Total Price', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Warehouse', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Location', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Expiry', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Reference', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->col('Notes', '83', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data[$i]['barcode'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['rrqty'], $decimal_currency), '83', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['uom'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['rrcost'], $decimal_currency), '83', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['disc'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '83', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['clientname'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['loc'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['expiry'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['ref'], '83', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['rem'], '83', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();
      if ($docno == $data[$i]['docno']) {
        $total += $data[$i]['ext'];
      }
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, '1px solid', '', 'R', 'Century Gothic', '14', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }

  private function DEFAULT_SV_SUMMARIZED($config, $data)
  {
    $pagenumber = 1;
    $count = 6;
    $page = 6;
    $str = "";

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh    = $config['params']['dataparams']['wh'];

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('800');

    if ($companyid == 3) {
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
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
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br/><br/>';

    $str .= $this->tableHead('800', 'SUPPLIER INVOICE (SUMMARIZED)', $config);

    $str .= $this->reporter->printline();
    $docno = "";
    $total = 0;

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '150', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '11', 'B', '', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '11', 'B', '', '', '');
    $str .= $this->reporter->col('Name', '150', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '11', 'B', '', '', '');
    $str .= $this->reporter->col('Warehouse', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '11', 'B', '', '', '');
    $str .= $this->reporter->col('Remarks', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['docno'], '150', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['supplier'], '150', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['whname'], '150', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '11', '', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amount'], 2), '150', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '11', '', '', '', '');
      $str .= $this->reporter->col($data[$i]['hrem'], '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();
      $total = $total + $data[$i]['amount'];
      $str .= $this->reporter->endtable();

      if ($i == count($data) - 1) {
        $str .= $this->tableFooter('800', $total);
      }
    }
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function tableHead($a, $title, $config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh    = $config['params']['dataparams']['wh'];
    $whname = $config['params']['dataparams']['whname'];

    if ($wh == '') {
      $whname = 'ALL';
    }
    $str = "";
    $str .= $this->reporter->begintable($a);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($title, null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'Century Gothic', '10', '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, '1px solid ', '', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('User: ' . $filterusername, '200', null, false, '1px solid ', '', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
    $str .= $this->reporter->col('Warehouse: ' . $whname, '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
    $str .= $this->reporter->col('Sort by: ' . $sorting . 'ending', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function tableFooter($a, $total)
  {
    $str = "";
    $str .= $this->reporter->begintable($a);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total', '150', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '150', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '12', 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
}//end class