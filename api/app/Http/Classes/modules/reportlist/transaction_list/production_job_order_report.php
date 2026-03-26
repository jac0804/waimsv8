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

class production_job_order_report
{
  public $modulename = 'Job Order Report';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dcentername', 'approved'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.label', 'Customer');
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
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
         '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'0' as deptid";
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
      case 0: // summarized
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;

      case 1: // detailed
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 27: //nte
      case 36: //rozlab
        $query = $this->others_QUERY($config);
        break;
      case 10:
      case 12: //afti
        $query = $this->afti_QUERY($config);
        break;
      default:
        $query = $this->def_QUERY($config);
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    $add1 = "";
    $stat1 = "";
    $stat2 = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.client = '$client' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }


    if ($companyid == 27 || $companyid == 36) { //nte, rozlab
      $add1 = ",item.itemname,item.uom,date(info.expirydate) as expirydate , format(ifnull((select sum(ext) from glstock where trno=head.trno),0),2) as amt";
      $stat1 = " , 'UNPOSTED' as status";
      $stat2 = " , 'POSTED' as status";
    } else {
      $add1 = "";
      $stat1 = "";
      $stat2 = "";
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
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
      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";
      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select head.trno, head.docno, head.clientname,item.itemname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename $add1 $stat2
        from glhead as head
        left join cntnum on cntnum.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.clientid=head.whid 
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " 
        order by docno " . $sorting;
        break;

      case 1: // unposted
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename $add1 $stat1
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " 
        order by docno " . $sorting;
        break;

      default: // all
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename $add1 $stat1
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . "
      union all
      select head.trno, head.docno, head.clientname,item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename $add1 $stat2
       from glhead as head
       left join cntnum on cntnum.trno=head.trno
       left join hcntnuminfo as info on info.trno=head.trno
       left join item on item.itemid=info.itemid
       left join client as wh on wh.clientid=head.whid 
       where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " 
       order by docno " . $sorting;
        break;
    } // end switch

    return $query;
  }


  public function def_QUERY($config)
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
        $leftjoin1 .= " left join client as cl on cl.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposted
        $leftjoin2 .= " left join client as cl on cl.client=head.client ";
      } else {
        $leftjoin1 .= " left join client as cl on cl.clientid=head.clientid ";
        $leftjoin2 .= " left join client as cl on cl.client=head.client ";
      }
      $filter .= " and cl.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select head.trno, head.docno, head.clientname,item.itemname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename 
        from glhead as head
        left join cntnum on cntnum.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.clientid=head.whid 
        $leftjoin1
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "  
        order by docno " . $sorting;
        break;

      case 1: // unposted
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename 
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
        $leftjoin2
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "  
        order by docno " . $sorting;
        break;

      default: // all
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename 
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
        $leftjoin2
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
      union all
      select head.trno, head.docno, head.clientname,item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename 
       from glhead as head
       left join cntnum on cntnum.trno=head.trno
       left join hcntnuminfo as info on info.trno=head.trno
       left join item on item.itemid=info.itemid
       left join client as wh on wh.clientid=head.whid 
       $leftjoin1
       where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "  
       order by docno " . $sorting;
        break;
    } // end switch

    return $query;
  }


  public function others_QUERY($config)
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
        $leftjoin1 .= " left join client as cl on cl.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposted
        $leftjoin2 .= " left join client as cl on cl.client=head.client ";
      } else {
        $leftjoin1 .= " left join client as cl on cl.clientid=head.clientid ";
        $leftjoin2 .= " left join client as cl on cl.client=head.client ";
      }
      $filter .= " and cl.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select head.trno, head.docno, head.clientname,item.itemname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename,
        item.itemname,item.uom,date(info.expirydate) as expirydate , format(ifnull((select sum(ext) from glstock where trno=head.trno),0),2) as amt, 'POSTED' as status
        from glhead as head
        left join cntnum on cntnum.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.clientid=head.whid 
        $leftjoin1
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        order by docno " . $sorting;
        break;

      case 1: // unposted
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename,
        item.itemname,item.uom,date(info.expirydate) as expirydate , format(ifnull((select sum(ext) from glstock where trno=head.trno),0),2) as amt,'UNPOSTED' as status
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
         $leftjoin2
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        order by docno " . $sorting;
        break;

      default: // all
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename,
        item.itemname,item.uom,date(info.expirydate) as expirydate , format(ifnull((select sum(ext) from glstock where trno=head.trno),0),2) as amt,'UNPOSTED' as status
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
         $leftjoin2
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
      union all
      select head.trno, head.docno, head.clientname,item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename,
        item.itemname,item.uom,date(info.expirydate) as expirydate , format(ifnull((select sum(ext) from glstock where trno=head.trno),0),2) as amt, 'POSTED' as status
       from glhead as head
       left join cntnum on cntnum.trno=head.trno
       left join hcntnuminfo as info on info.trno=head.trno
       left join item on item.itemid=info.itemid
       left join client as wh on wh.clientid=head.whid 
        $leftjoin1
       where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
       order by docno " . $sorting;
        break;
    } // end switch

    return $query;
  }


  public function afti_QUERY($config)
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
    $project = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['deptid'];
    $dept = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];

    $filter = "";
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
        $leftjoin1 .= " left join client as cl on cl.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposted
        $leftjoin2 .= " left join client as cl on cl.client=head.client ";
      } else {
        $leftjoin1 .= " left join client as cl on cl.clientid=head.clientid ";
        $leftjoin2 .= " left join client as cl on cl.client=head.client ";
      }
      $filter .= " and cl.clientid = '$clientid' ";
    }

    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($project != "") {
      $filter .= " and stock.projectid = $projectid";
    }
    if ($dept != "") {
      $filter .= " and head.deptid = $deptid";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select head.trno, head.docno, head.clientname,item.itemname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename  
        from glhead as head
        left join cntnum on cntnum.trno=head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.clientid=head.whid
        $leftjoin1
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        order by docno " . $sorting;
        break;

      case 1: // unposted
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename  
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
        $leftjoin2
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        order by docno " . $sorting;
        break;

      default: // all
        $query = "select head.trno, head.docno, head.clientname, item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from lastock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename  
        from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join item on item.itemid=info.itemid
        left join client as wh on wh.client=head.wh 
        $leftjoin2
        where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      union all
      select head.trno, head.docno, head.clientname,item.barcode, format(ifnull((select sum(ext) / ifnull(info.batchsize,0) from glstock where trno=head.trno),0),4) as costuom,
        head.rem, info.batchsize, info.yield, date(head.dateid) as dateid, info.lotno, wh.clientname as warehousename  
       from glhead as head
       left join cntnum on cntnum.trno=head.trno
       left join hcntnuminfo as info on info.trno=head.trno
       left join item on item.itemid=info.itemid
       left join client as wh on wh.clientid=head.whid 
       $leftjoin1
       where head.doc='JP' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
       order by docno " . $sorting;
        break;
    } // end switch

    return $query;
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

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Order Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable($layoutsize);
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
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
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

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';

    $font = $this->companysetup->getrptfont($config['params']);
    $layoutsize = '1000';
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
        $fontsize = '11';
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
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->clientname, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Product: ' . $data->barcode . ' ' . $data->itemname . '  ' . $data->uom, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Batch Size: ' . $data->batchsize, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Lot No: ' . $data->lotno, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Notes: ' . $data->rem, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Yield: ' . number_format($data->yield, 2) . ' ' . $data->uom, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Expiry: '  . $data->expirydate, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('JO Qty', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Cost', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Cost', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $data2 = $this->getData($data->trno);
        if (!empty($data2)) {
          foreach ($data2 as $d) {
            $fontsize = '10';
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($d->barcode, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($d->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($d->isqty, 4), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($d->isqty2, 4), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($d->uom, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($d->isamt, 4), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($d->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($d->ext, 2), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($d->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($d->loc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($d->expiry, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->addline();
            if ($docno == $data->docno) {
              $total += $d->ext;
            }
          }
        }


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);

          $page = $page + $count;
        } //end if

        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $fontsize = '11';
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

  public function getData($trno)
  {
    $query = "select stock.uom, stock.isqty, stock.isqty2, stock.isamt, stock.disc, stock.ext, stock.loc, stock.rem, stock.ref, item.barcode, item.itemname, stock.expiry, client.clientname
    from lastock as stock left join item on item.itemid=stock.itemid left join client on client.clientid=stock.whid where stock.trno=" . $trno . "
  union all
  select stock.uom, stock.isqty, stock.isqty2,stock.isamt, stock.disc, stock.ext, stock.loc, stock.rem, stock.ref, item.barcode, item.itemname, stock.expiry, client.clientname 
    from glstock as stock left join item on item.itemid=stock.itemid left join client on client.clientid=stock.whid where stock.trno=" . $trno;
    return $this->coreFunctions->opentable($query);
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

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    foreach ($result as $key => $data) {
      $fontsize = '10';
      $str .= $this->reporter->addline();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '260', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->batchsize, '105', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yield, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->amt, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->status, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
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
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Product Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '260', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Batch Size', '105', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Yield', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
}//end class