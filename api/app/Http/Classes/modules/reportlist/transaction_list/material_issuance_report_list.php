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

class material_issuance_report_list
{
  public $modulename = 'Material Issuance Report List';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dcentername', 'approved'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dclientname.lookupclass', 'wasupplier');

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
    $companyid = $config['params']['companyid'];

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as `end`,'0' as clientid, '' as client,'' as clientname,'' as userid,
        '' as username,'' as approved,'0' as posttype,'0' as reporttype, 'ASC' as sorting,'' as dclientname,'' as reportusers,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
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
        $query = $this->default_QUERY_SUMMARIZED($config);
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
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    $leftjoin = "";
    $leftjoin1 = "";
    $leftjoin2 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }

    if ($client != "") {
      if ($posttype == 0) { //posted
        $leftjoin .= " left join client as supp on supp.clientid=head.clientid ";
      } elseif ($posttype == 1) { //
        $leftjoin .= " left join client as supp on supp.client=head.client ";
      } else { //all
        $leftjoin1 .= " left join client as supp on supp.clientid=head.clientid ";
        $leftjoin2 .= " left join client as supp on supp.client=head.client ";
      }
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }


    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $addjoin = "";


    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as supplier
      " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,cntnum.center, dept.client as deptcode, dept.clientname as deptname
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      " . $addjoin . "
      where head.doc='RM'  and head.dateid between '$start' and '$end' $filter $filter1
      ) as a order by docno,center $sorting";

        break;

      case 1: // unposted
        $query = "select * from (
      select head.docno,head.clientname as supplier
      " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,cntnum.center,dept.client as deptcode, dept.clientname as deptname
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      " . $addjoin . "
      where head.doc='RM' and head.dateid between '$start' and '$end' $filter $filter1
      ) as a order by docno,center $sorting";
        break;

      default: // sana all
        $query = "select * from ( select head.docno,head.clientname as supplier
      " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,cntnum.center,dept.client as deptcode, dept.clientname as deptname
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin1
      left join client as dept on dept.clientid = head.deptid
      " . $addjoin . "
      where head.doc='RM' and head.dateid between '$start' and '$end' $filter $filter1
      union all
      select head.docno,head.clientname as supplier
      " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,cntnum.center, dept.client as deptcode, dept.clientname as deptname
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin2
      left join client as dept on dept.clientid = head.deptid
      " . $addjoin . "
      where head.doc='RM' and head.dateid between '$start' and '$end' $filter $filter1
      ) as a order by docno,center $sorting";
        break;
    }

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    $leftjoin = "";
    $leftjoin1 = "";
    $leftjoin2 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }

    if ($client != "") {
      if ($posttype == 0) { //posted
        $leftjoin .= " left join client as supp on supp.clientid=head.clientid ";
      } elseif ($posttype == 1) { //
        $leftjoin .= " left join client as supp on supp.client=head.client ";
      } else { //all
        $leftjoin1 .= " left join client as supp on supp.clientid=head.clientid ";
        $leftjoin2 .= " left join client as supp on supp.client=head.client ";
      }
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }


    $filter1 .= "";


    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,deptcode,deptname,ourref,
        ifnull(group_concat(distinct podocno separator '/ '),'') as podocno from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,client.client as wh,head.rem as hrem,cntnum.center,
      dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      where head.doc='RM'  and date(head.dateid) between '$start' and '$end' $filter $filter1
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem,center, deptcode, deptname,ourref
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,deptcode,deptname,ourref,
        ifnull(group_concat(distinct podocno separator '/ '),'') as podocno from ( 
      select head.docno,head.clientname as supplier,
      item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
      ,client.client as wh,head.rem as hrem,cntnum.center, dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      where head.doc='RM' and date(head.dateid) between '$start' and '$end' $filter $filter1
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center, deptcode,deptname,ourref
      order by docno $sorting";
        break;

      default: // sana all
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center, deptcode,deptname,ourref,
        ifnull(group_concat(distinct podocno separator '/ '),'') as podocno from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,client.client as wh,head.rem as hrem,cntnum.center,
      dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin1
      left join client as dept on dept.clientid = head.deptid
      where head.doc='RM' and head.dateid between '$start' and '$end' $filter $filter1
      union all
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
      ,client.client as wh,head.rem as hrem,cntnum.center,dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin2
      left join client as dept on dept.clientid = head.deptid
      where head.doc='RM' and date(head.dateid) between '$start' and '$end' $filter $filter1
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center, deptcode, deptname,ourref
      order by docno $sorting";
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
    $center    = $config['params']['dataparams']['center'];
    $centername    = $config['params']['dataparams']['centername'];

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

    if ($centername == '') {
      $centername = 'ALL';
    }

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1100';
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
    $str .= $this->reporter->col('Material Issuance Report List (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $centername, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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

    // $count=38;
    // $page=40;

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
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Supplier: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '53', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '53', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '78', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '53', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '78', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '53', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '53', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '78', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '53', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '78', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($docno == $data->docno) {
          $total += $data->ext;
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

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
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

    $count = 26;
    $page = 25;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1100';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $str .= $this->tableheader($config, $layoutsize);

    $docno = "";
    $total = 0;
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 17) { //unihome
          $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->dateid, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->whname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->amount, 2), '140', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->hrem, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->whname, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->amount, 2), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->podocno, '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->hrem, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $total = $total + $data->amount;
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($config, $layoutsize);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($companyid == 17) { //unihome
            $str .= $this->reporter->col('', '600', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Grand Total : ', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '140', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '210', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('', '460', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Grand Total : ', '140', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '380', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
      //}
    }
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function tableheader($config, $layoutsize)
  {
    $companyid = $config['params']['companyid'];
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('WAREHOUSE', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('AMOUNT', '120', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('PO DOCUMENT NO.', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('REMARKS', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class