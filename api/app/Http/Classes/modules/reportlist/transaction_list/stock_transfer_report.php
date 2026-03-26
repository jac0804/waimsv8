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

class stock_transfer_report
{
  public $modulename = 'Stock Transfer Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];



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
    $fields = ['radioprint', 'start', 'end', 'ddeptname', 'reportusers', 'dcentername', 'approved'];
    if ($config['params']['companyid'] == 39) array_push($fields, 'dwhname', 'dwhname2'); //cbbsi
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    if ($config['params']['companyid'] == 39) { //cbbsi
      data_set($col1, 'dwhname.label', 'Source Warehouse');
      data_set($col1, 'dwhname2.label', 'Destination Warehouse');
    }

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
    $center = $config['params']['center'];
    $name = $this->coreFunctions->datareader("select name as value from center where code =?", [$center]);
    $paramstr =  "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as `end`,
      '' as dept,'0' as deptid, '' as deptname,
      '' as userid,
      '' as username,
      '' as approved,
      '0' as posttype,
      '0' as reporttype, 
      'ASC' as sorting,
      '' as center,
      '' as dclientname,
      '' as reportusers,
      '0' as whid, '' as wh, '' as whname, '' as whid2, '' as wh2, 
      '' as wh2name, '' as dwhname, '' as dwhname2,'' as ddeptname";
    switch ($config['params']['companyid']) {
      case 17: //unihome
        $paramstr .= " ,  '$name' as dcentername";
        break;
      default:
        $paramstr .= " , '' as dcentername";
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
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        if ($config['params']['companyid'] == 39) { //cbbsi
          $result = $this->reportCBBSILayout_SUMMARIZED($config);
        } else {
          $result = $this->reportDefaultLayout_SUMMARIZED($config);
        }
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_DETAILED($config);
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
        if ($config['params']['companyid'] == 39) { //cbbsi
          $query = $this->default_CBBSIQUERY_SUMMARIZED($config);
        } else {
          $query = $this->default_QUERY_SUMMARIZED($config);
        }
        break;
      case '1': // DETAILED
        $query = $this->default_QUERY_DETAILED($config);
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptid     = $config['params']['dataparams']['deptid'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($dept != "") {
      $filter .= " and dept.clientid = '$deptid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    $doc = "ST";

    if ($config['params']['companyid'] == 8) { //maxipro
      $doc = 'MT';
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,stock.amt,stock.ext as cost
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      where head.doc='$doc' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center,stock.amt, stock.ext as cost
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='$doc' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      default: // all
        $query = "select * from ( select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,stock.amt,stock.ext as cost
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      where head.doc='$doc' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,stock.amt,stock.ext as cost
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      where head.doc='$doc' and stock.iss<>0  and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;
    }

    return $query;
  }

  public function default_CBBSIQUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptid     = $config['params']['dataparams']['deptid'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($dept != "") {
      $filter .= " and dept.clientid = '$deptid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    $filter1 = '';
    $filter2 = '';
    $source = $config['params']['dataparams']['wh']; //client
    $sourceid = $config['params']['dataparams']['whid']; //clientid
    $destination = $config['params']['dataparams']['wh2']; //client
    $destid = $config['params']['dataparams']['whid2']; //clientid
    if ($source != '') {
      $filter2 .= " and head.whid='" . $sourceid . "'";
    }
    if ($destination != '') {
      $filter2 .= " and client.clientid='" . $destid . "'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(ext) as ext,hrem,center, yourref, postdate, dispatchdate from ( 
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid, date(info.shipdate) as dispatchdate,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join hcntnuminfo as info on info.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      where head.doc='ST' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter $filter2) as a 
      group by docno,dateid,deptname,wh,clientname,hrem,center,yourref,postdate,dispatchdate
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(ext) as ext,hrem,center,yourref,postdate, dispatchdate from ( 
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid, date(info.shipdate) as dispatchdate,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='ST' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter $filter1) as a 
        group by docno,dateid,deptname,wh,clientname,hrem,center,yourref,postdate,dispatchdate
        order by docno $sorting";
        break;

      default: // all
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(ext) as ext,hrem,center,yourref,postdate, dispatchdate from ( 
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid, date(info.shipdate) as dispatchdate,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='ST' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter $filter2
      union all
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid, date(info.shipdate) as dispatchdate,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='ST' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter $filter1) as a 
      group by docno,dateid,deptname,wh,clientname,hrem ,center,yourref,postdate,dispatchdate
      order by docno $sorting";
        break;
    }


    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptname     = $config['params']['dataparams']['deptname'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($dept != "") {
      $filter .= " and dept.client = '$dept' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    $doc = "ST";

    if ($config['params']['companyid'] == 8) { //maxipro
      $doc = "MT";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(ext) as ext,hrem,center, yourref, postdate from ( 
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      where head.doc='$doc' and stock.rrqty<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a 
      group by docno,dateid,deptname,wh,clientname,hrem,center,yourref,postdate
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(ext) as ext,hrem,center,yourref,postdate from ( 
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='$doc' and stock.rrqty<>0 and date(head.dateid) between '$start' and '$end' $filter
        ) as a 
        group by docno,dateid,deptname,wh,clientname,hrem,center,yourref,postdate
        order by docno $sorting";
        break;

      default: // all
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(ext) as ext,hrem,center,yourref,postdate from ( 
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='$doc' and stock.rrqty<>0 and date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.qty,stock.rrqty,stock.ext,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center, head.yourref, date(cntnum.postdate) as postdate
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        where head.doc='$doc' and stock.rrqty<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a 
      group by docno,dateid,deptname,wh,clientname,hrem ,center,yourref,postdate
      order by docno $sorting";
        break;
    }

    return $query;
  }

  public function header_CBBSI($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptname     = $config['params']['dataparams']['deptname'];
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
    $str .= $this->reporter->col('Stock Transfer Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Source: ' . ($config['params']['dataparams']['wh'] == '' ? 'ALL' : $config['params']['dataparams']['wh']), null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Destination: ' . ($config['params']['dataparams']['wh2'] == '' ? 'ALL' : $config['params']['dataparams']['wh2']), null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptname     = $config['params']['dataparams']['deptname'];
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
    $layoutsize = '800';
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
      $str .= $this->reporter->letterhead($center, $username, $config);
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
    $str .= $this->reporter->col('Stock Transfer Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $companyid = $config['params']['companyid'];
    $count = 38;
    $page = 40;

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
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '900', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();
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
          $str .= $this->reporter->col('Department: ' . $data->deptname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Quantity', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Barcode', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->col('Location', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          if ($companyid == 17) { //unihome
            $str .= $this->reporter->col('Cost', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Amount', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          }
          $str .= $this->reporter->col('Expiry', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(number_format($data->qty, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->barcode, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        if ($companyid == 17) { //unihome
          $str .= $this->reporter->col(number_format($data->amt, 2), '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->cost, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        }
        $str .= $this->reporter->col($data->expiry, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($docno == $data->docno) {
          $total += $data->qty;
        }
        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportCBBSILayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_CBBSI($config);
    $str .= $this->tableheaderCBBSI($layoutsize, $config);

    $docno = "";
    $total = 0;
    $i = 0;

    if (!empty($result)) {
      $str .= $this->reporter->begintable($layoutsize);
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '115', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateid, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->deptname, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->whname, '145', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->deptname, '145', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->yourref, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->postdate, '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dispatchdate, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->hrem, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $total = $total + $data->ext;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_CBBSI($config);
          $str .= $this->tableheaderCBBSI($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '575', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Grand Total', '85', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($total, 2), '85', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '255', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 38;
    $page = 40;

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
    $str .= $this->tableheader($layoutsize, $config);

    $docno = "";
    $total = 0;
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->deptname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->whname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->hrem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();

        $total = $total + $data->ext;


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Grand Total', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheaderCBBSI($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '115', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Date', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Department', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Source', '145', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Warehouse(Destination)', '145', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Yourref', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Post Date', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Dispatch Date', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Remarks', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Department', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Warehouse(Destination)', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }
}//end class