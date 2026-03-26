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

class canvass_sheet_report
{
  public $modulename = 'Canvass Sheet Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dcentername', 'approved'];
    switch ($config['params']['companyid']) {
      case 16: //ati
        array_push($fields, 'ditemname', 'createby');
        break;
    }


    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dclientname.lookupclass', 'wasupplier');

    switch ($config['params']['companyid']) {
      case 16: //ati
        data_set($col1, 'createby.lookupclass', 'lookupcreatebycd');
        break;
    }

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
    if ($config['params']['companyid'] == 16) { //ati
      array_push($fields, 'radiostatus');
    }
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

    if ($config['params']['companyid'] == 16) { //ati
      data_set(
        $col2,
        'radioreporttype.options',
        [
          ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Without Invoice', 'value' => '2', 'color' => 'orange']
        ]
      );

      data_set(
        $col2,
        'radiostatus.options',
        [
          ['label' => 'Approved', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Reject', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]
      );
    }


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '0' as clientid,
    '' as client,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as dclientname,'' as reportusers,
    '0' as status,
    '' as barcode,'' as itemname,'' as itemid,'' as createby, '' as ditemname ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    // $params = $this->reportParams;


    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        switch ($reporttype) {
          case 0: // summarized
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;

          case 1: // detailed
            $result = $this->reportDefaultLayout_afti_DETAILED($config);
            break;

          case 2: // without invoice
            $result = $this->reportDefaultLayout_WOUTINVOICE($config);
            break;
        }

        break;
      case 16: //ati
        switch ($reporttype) {
          case 0: // summarized
            $result = $this->reportDefaultLayout_ati_SUMMARIZED($config);
            break;

          case 1: // detailed
            $result = $this->reportDefaultLayout_ati_DETAILED($config);
            break;

          case 2: // without invoice
            $result = $this->reportDefaultLayout_ati_WOUTINVOICE($config);
            break;
        }

        break;
      default:
        switch ($reporttype) {
          case 0: // summarized
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;

          case 1: // detailed
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;

          case 2: // without invoice
            $result = $this->reportDefaultLayout_WOUTINVOICE($config);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        switch ($reporttype) {
          case 0: //summarized
            $query = $this->default_afti_summary_QUERY($config);
            break;
          case 1: //detailed
            $query = $this->default_afti_detailed_QUERY($config);
            break;
          case 2: //without invoice
            $query = $this->default_afti_inv_QUERY($config);
            break;
        }
        break;
      case 16: //ati
        switch ($reporttype) {
          case 0: //summarized
            $query = $this->default_ati_summary_QUERY($config);
            break;
          case 1: //detailed
            $query = $this->default_ati_detailed_QUERY($config);
            break;
          case 2: //without invoice
            $query = $this->default_ati_inv_QUERY($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case 0: //summarized
            $query = $this->default_def_summary_QUERY($config);
            break;
          case 1: //detailed
            $query = $this->default_def_detailed_QUERY($config);
            break;
          case 2: //without invoice
            $query = $this->default_def_inv_QUERY($config);
            break;
        }
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];


    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reportstatus = "";
    $statusfilter = "";

    if ($config['params']['companyid'] == 16) { //ati
      if ($posttype == 0 || $posttype == 1 || $posttype == 2) { //posted , unposted & all posttype
        $reportstatus = $config['params']['dataparams']['status'];
        if ($reportstatus == 0) { // approved canvass-(status)
          $statusfilter = " and stock.void=0 and stock.status=1";
        } else if ($reportstatus == 1) {  //rejected canvass-(status)
          $statusfilter = " and stock.void=0 and stock.status=2";
        } else {
          $statusfilter = " and stock.void=0 and stock.status in (0,1,2)";  //all(status)
        }
      }
    }

    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $filterdetail = "";


    switch ($config['params']['companyid']) {
      case 16: //ati
        $createby    = $config['params']['dataparams']['createby'];
        if ($createby != "") {
          $filterdetail .= " and head.createby = '$createby' ";
        }
        break;
      default:
        if ($filterusername != "") {
          $filter .= " and head.createby = '$filterusername' ";
        }
        break;
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.client = '$client' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
        $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
        break;
      case 16: //ati
        $itemid    = $config['params']['dataparams']['itemid'];
        $itemname    = $config['params']['dataparams']['itemname'];
        if ($itemname != "") {
          $filterdetail .= " and item.itemid = '$itemid' ";
        }
        $barcodeitemnamefield = ",ifnull(item.barcode,'') as barcode,ifnull(item.itemname,'') as itemname,ifnull(info.itemdesc,'') as itemdesc, ifnull(info.specs,'') as specs";
        $addjoin = "left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline";
        break;
      default:
        $barcodeitemnamefield = ",item.barcode,item.itemname";
        $addjoin = "";
        break;
    }
    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.client = head.wh
          left join client as supp on supp.client = head.client
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD'  and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby,head.dateid,pr.docno
          order by docno $sorting";

            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
            break;
        } // end switch posttype

        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
              left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          " . $addjoin . "
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $filterdetail $statusfilter
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          prinfo.ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          " . $addjoin . "
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $filterdetail $statusfilter
          order by docno $sorting";

            break;


          default: // all
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          " . $addjoin . "
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $filterdetail $statusfilter
          union all
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          " . $addjoin . "
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $filterdetail $statusfilter
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 2: // without invoice
        switch ($posttype) {
          case 0: // posted
            $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      left join client as wh on wh.client = head.wh
                      left join client as supp on supp.client = head.client
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter $statusfilter
                      group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      left join client as supp on supp.client = head.client
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
                      group by head.docno, head.clientname,wh.clientname, head.createby,head.dateid,pr.docno
                      order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                            wh.clientname, head.createby, left(head.dateid,10) as dateid,
                            group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      left join client as supp on supp.client = head.client
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter $statusfilter
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      union all
                      select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      left join client as supp on supp.client = head.client
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter $statusfilter
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";
            break;
        } // end switch posttype

        break;
    } // end switch



    return $query;
  }

  public function default_def_summary_QUERY($config)
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
    $leftjoin = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoin .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          $leftjoin
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD'  and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
         $leftjoin
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  
          group by head.docno, head.clientname,
          wh.clientname, head.createby,head.dateid,pr.docno
          order by docno $sorting";


        break;

      default: // all
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
         $leftjoin
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
         $leftjoin
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
        break;
    } // end switch posttype
    return $query;
  }

  public function default_def_detailed_QUERY($config)
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
    $leftjoin = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoin = "left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoin
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  
          order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          prinfo.ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoin
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  
          order by docno $sorting";

        break;


      default: // all
        $query = "select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoin
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  
          union all
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoin
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  
          order by docno $sorting";
        break;
    } // end switch posttype

    return $query;
  }

  public function default_def_inv_QUERY($config)
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
    $leftjoin = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoin .= "left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.client = head.wh
                      $leftjoin
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";

        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      $leftjoin
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter  
                      group by head.docno, head.clientname,wh.clientname, head.createby,head.dateid,pr.docno
                      order by docno $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                            wh.clientname, head.createby, left(head.dateid,10) as dateid,
                            group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      $leftjoin
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      union all
                      select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      $leftjoin
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";
        break;
    } // end switch posttype

    return $query;
  }


  public function default_ati_summary_QUERY($config)
  {

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $reportstatus = $config['params']['dataparams']['status'];
    $createby    = $config['params']['dataparams']['createby'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $statusfilter = "";

    if ($posttype == 0 || $posttype == 1 || $posttype == 2) { //posted , unposted & all posttype
      if ($reportstatus == 0) { // approved canvass-(status)
        $statusfilter = " and stock.void=0 and stock.status=1";
      } else if ($reportstatus == 1) {  //rejected canvass-(status)
        $statusfilter = " and stock.void=0 and stock.status=2";
      } else {
        $statusfilter = " and stock.void=0 and stock.status in (0,1,2)";  //all(status)
      }
    }

    $fcenter    = $config['params']['dataparams']['center'];
    $filter = "";
    $leftjoy = "";

    if ($createby != "") {
      $filter .= " and head.createby = '$createby' ";
    }


    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoy .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($itemname != "") {
      $leftjoy .= "left join item on item.itemid=stock.itemid";
      $filter .= " and item.itemid = '$itemid' ";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          $leftjoy
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD'  and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          $leftjoy
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby,head.dateid,pr.docno
          order by docno $sorting";

        break;

      default: // all
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          $leftjoy
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          $leftjoy
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
        break;
    } // end switch posttype
    return $query;
  }


  public function default_ati_detailed_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $reportstatus = $config['params']['dataparams']['status'];
    $statusfilter = "";

    if ($posttype == 0 || $posttype == 1 || $posttype == 2) { //posted , unposted & all posttype
      if ($reportstatus == 0) { // approved canvass-(status)
        $statusfilter = " and stock.void=0 and stock.status=1";
      } else if ($reportstatus == 1) {  //rejected canvass-(status)
        $statusfilter = " and stock.void=0 and stock.status=2";
      } else {
        $statusfilter = " and stock.void=0 and stock.status in (0,1,2)";  //all(status)
      }
    }

    $fcenter    = $config['params']['dataparams']['center'];
    $filter = "";
    $leftjoy = "";

    $createby    = $config['params']['dataparams']['createby'];
    if ($createby != "") {
      $filter .= " and head.createby = '$createby' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoy .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($itemname != "") {
      $filter .= " and item.itemid = '$itemid' ";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
          select head.docno,head.clientname as supplier,ifnull(item.barcode,'') as barcode,ifnull(item.itemname,'') as itemname,ifnull(info.itemdesc,'') as itemdesc, ifnull(info.specs,'') as specs,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoy
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
         left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
          order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select head.docno,head.clientname as supplier,ifnull(item.barcode,'') as barcode,ifnull(item.itemname,'') as itemname,ifnull(info.itemdesc,'') as itemdesc, ifnull(info.specs,'') as specs,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          prinfo.ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoy
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
         left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
          order by docno $sorting";

        break;

      default: // all
        $query = "select head.docno,head.clientname as supplier,ifnull(item.barcode,'') as barcode,ifnull(item.itemname,'') as itemname,ifnull(info.itemdesc,'') as itemdesc, ifnull(info.specs,'') as specs,

          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoy
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
         left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
          union all
          select head.docno,head.clientname as supplier,ifnull(item.barcode,'') as barcode,ifnull(item.itemname,'') as itemname,ifnull(info.itemdesc,'') as itemdesc, ifnull(info.specs,'') as specs,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          $leftjoy
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter $statusfilter
          order by docno $sorting";
        break;
    } // end switch posttype

    return $query;
  }


  public function default_ati_inv_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reportstatus = $config['params']['dataparams']['status'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $fcenter    = $config['params']['dataparams']['center'];
    $createby    = $config['params']['dataparams']['createby'];
    $statusfilter = "";
    $filter = "";
    $leftjoy = "";

    if ($posttype == 0 || $posttype == 1 || $posttype == 2) { //posted , unposted & all posttype
      if ($reportstatus == 0) { // approved canvass-(status)
        $statusfilter = " and stock.void=0 and stock.status=1";
      } else if ($reportstatus == 1) {  //rejected canvass-(status)
        $statusfilter = " and stock.void=0 and stock.status=2";
      } else {
        $statusfilter = " and stock.void=0 and stock.status in (0,1,2)";  //all(status)
      }
    }

    if ($createby != "") {
      $filter .= " and head.createby = '$createby' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoy .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($itemname != "") {
      $leftjoy .= " left join item on item.itemid=stock.itemid";
      $filter .= " and item.itemid = '$itemid' ";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      $leftjoy
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter $statusfilter
                      group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";

        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      $leftjoy
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter  $statusfilter
                      group by head.docno, head.clientname,wh.clientname, head.createby,head.dateid,pr.docno
                      order by docno $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                            wh.clientname, head.createby, left(head.dateid,10) as dateid,
                            group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      $leftjoy
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter $statusfilter
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      union all
                      select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      $leftjoy
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter $statusfilter
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";
        break;
    } // end switch posttype
    return $query;
  }


  public function default_afti_summary_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $leftjoy = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoy .= "left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          $leftjoy
          where head.doc='CD'  and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby,head.dateid,pr.docno
          order by docno $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,
          pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,pr.docno
          order by docno $sorting";
        break;
    } // end switch posttype
    return $query;
  }

  public function default_afti_detailed_QUERY($config)
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
    $leftjoy = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoy .= "left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
          select head.docno,head.clientname as supplier,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          left join model_masterfile as model on model.model_id=item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join iteminfo as i on i.itemid  = item.itemid
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select head.docno,head.clientname as supplier,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          prinfo.ctrlno,
          pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          left join model_masterfile as model on model.model_id=item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join iteminfo as i on i.itemid  = item.itemid
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting";

        break;

      default: // all
        $query = "select head.docno,head.clientname as supplier,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from cdstock as stock
          left join cdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          left join model_masterfile as model on model.model_id=item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join iteminfo as i on i.itemid  = item.itemid
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          union all
          select head.docno,head.clientname as supplier,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,prinfo.ctrlno,pr.docno as prdocno
          from hcdstock as stock
          left join hcdhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          left join model_masterfile as model on model.model_id=item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join iteminfo as i on i.itemid  = item.itemid
          $leftjoy
          where head.doc='CD' and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting";
        break;
    } // end switch posttype

    return $query;
  }


  public function default_afti_inv_QUERY($config)
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
      $leftjoy = "left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      left join client as wh on wh.client = head.wh
                      $leftjoy
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";

        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      $leftjoy
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter  
                      group by head.docno, head.clientname,wh.clientname, head.createby,head.dateid,pr.docno
                      order by docno $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                            wh.clientname, head.createby, left(head.dateid,10) as dateid,
                            group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from cdstock as stock
                      left join cdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      $leftjoy
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join headinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      union all
                      select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              wh.clientname, head.createby, left(head.dateid,10) as dateid,
                              group_concat(distinct prinfo.ctrlno separator ', ') as ctrlno,pr.docno as prdocno
                      from hcdstock as stock
                      left join hcdhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      $leftjoy
                      left join client as wh on wh.client = head.wh
                      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                      left join hprhead as pr on pr.trno=prinfo.trno
                      left join hheadinfotrans as info on info.trno=head.trno
                      where head.doc='CD' and info.isinvoice =0 and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,wh.clientname, head.createby, head.dateid,pr.docno
                      order by docno $sorting";
        break;
    } // end switch posttype

    return $query;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Discount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Price', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->disc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
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
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $companyid = $config['params']['companyid'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
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
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        }

        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_WOUTINVOICE($config)
  {
    $result = $this->reportDefault($config);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
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

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

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

    switch ($reporttype) {
      case 0:
        $reporttype = 'Summarized';
        break;
      case 1:
        $reporttype = 'Detailed';
        break;
      case 2:
        $reporttype = 'Without Invoice';
        break;
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Canvass Sheet Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
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

  public function reportDefaultLayout_ati_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_ati_DEFAULT($config);
    $str .= $this->ati_tableheader($layoutsize, $config);

    $totalext = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_ati_DEFAULT($config);
          $str .= $this->ati_tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_ati_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

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

    switch ($reporttype) {
      case 0:
        $reporttype = 'Summarized';
        break;
      case 1:
        $reporttype = 'Detailed';
        break;
      case 2:
        $reporttype = 'Without Invoice';
        break;
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Canvass Sheet Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function ati_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CTRL NO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PR DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_ati_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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
    $layoutsize = '1380';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_ati_DEFAULT($config);
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1480', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Specification', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Discount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Price', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemdesc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->specs, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col(number_format($data->rrqty, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->disc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();
        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1480', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportDefaultLayout_ati_WOUTINVOICE($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_ati_DEFAULT($config);
    $str .= $this->ati_tableheader($layoutsize, $config);
    $totalext = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_ati_DEFAULT($config);
          $str .= $this->ati_tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportDefaultLayout_afti_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Discount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Price', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->disc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class