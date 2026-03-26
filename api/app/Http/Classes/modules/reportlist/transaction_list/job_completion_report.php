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

class job_completion_report
{
  public $modulename = 'Job Completion Report';
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
        break;
    }

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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as `end`,
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
       '' as project, '' as projectid, '' as projectname, '' as ddeptname, '0' as deptid, '' as dept, '' as deptname ";

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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_afti_DETAILED($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10:
      case 12: //afti
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->Afti_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->Afti_QUERY_DETAILED($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->default_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->default_QUERY_DETAILED($config);
            break;
        }
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
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $leftjoin = "";
    $leftjoin1 = "";
    $leftjoin2 = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }

    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($client != "") {
      if ($posttype == 0) { //posted
        $leftjoin .= " left join client as supp on supp.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposes
        $leftjoin .= " left join client as supp on supp.client=head.client ";
      } else { //all
        $leftjoin1 .= " left join client as supp on supp.client=head.client "; //unposted
        $leftjoin2 .= " left join client as supp on supp.clientid=head.clientid "; //posted
      }
      $filter .= " and supp.clientid = '$clientid' ";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as supplier,
        item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
      select head.docno,head.clientname as supplier,
      item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      ) as a order by docno,center $sorting";
        break;

      default: // all
        $query = "select * from ( select head.docno,head.clientname as supplier,
        item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin2
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      union all
      select head.docno,head.clientname as supplier,
      item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin1
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      ) as a order by docno,center $sorting";
        break;
    }

    return $query;
  }


  public function Afti_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $prjid = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['deptid'];
    $deptcode = $config['params']['dataparams']['dept'];
    $project = $config['params']['dataparams']['projectid'];

    $filter = "";
    $leftjoin1 = "";
    $leftjoin2 = "";
    $leftjoin = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }

    if ($client != "") {
      if ($posttype == 0) { //posted
        $leftjoin .= " left join client as supp on supp.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposes
        $leftjoin .= " left join client as supp on supp.client=head.client ";
      } else { //all
        $leftjoin1 .= " left join client as supp on supp.client=head.client "; //unposted
        $leftjoin2 .= " left join client as supp on supp.clientid=head.clientid "; //posted
      }
      $filter .= " and supp.clientid = '$clientid' ";
    }

    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($prjid != "") {
      $filter .= " and stock.projectid = $project";
    }
    if ($deptcode != "") {
      $filter .= " and head.deptid = $deptid";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as supplier,item.partno as barcode, 
        concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
        stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
     left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid = item.brand 
      left join iteminfo as i on i.itemid  = item.itemid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
      select head.docno,head.clientname as supplier,item.partno as barcode, 
      concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
      stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
     left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid = item.brand 
      left join iteminfo as i on i.itemid  = item.itemid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      ) as a order by docno,center $sorting";
        break;

      default: // all
        $query = "select * from ( select head.docno,head.clientname as supplier,item.partno as barcode, 
        concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
        stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
      cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin2
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
     left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid = item.brand 
      left join iteminfo as i on i.itemid  = item.itemid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      union all
      select head.docno,head.clientname as supplier,item.partno as barcode, 
      concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
      stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin1
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
     left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid = item.brand 
      left join iteminfo as i on i.itemid  = item.itemid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      ) as a order by docno,center $sorting";
        break;
    }

    return $query;
  }


  public function default_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $leftjoin = "";
    $leftjoin1 = "";
    $leftjoin2 = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }

    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($client != "") {
      if ($posttype == 0) { //posted
        $leftjoin .= " left join client as supp on supp.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposes
        $leftjoin .= " left join client as supp on supp.client=head.client ";
      } else { //all
        $leftjoin1 .= " left join client as supp on supp.client=head.client "; //unposted
        $leftjoin2 .= " left join client as supp on supp.clientid=head.clientid "; //posted
      }
      $filter .= " and supp.clientid = '$clientid' ";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,branch,dept from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem,center,branch,dept
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,branch,dept from ( 
      select head.docno,head.clientname as supplier,
      item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref
      ,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center,branch,dept
      order by docno $sorting";
        break;

      default: // all
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,branch,dept from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin2
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      union all
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref
      ,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin1
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center,branch,dept
      order by docno $sorting";
        break;
    }

    return $query;
  }


  public function Afti_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $prjcode = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptcode = $config['params']['dataparams']['dept'];
    $deptid = $config['params']['dataparams']['deptid'];

    $filter = "";
    $leftjoin = "";
    $leftjoin1 = "";
    $leftjoin2 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }

    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($prjcode != "") {
      $filter .= " and stock.projectid = $projectid";
    }
    if ($deptcode != "") {
      $filter .= " and head.deptid = $deptid";
    }

    if ($client != "") {
      if ($posttype == 0) { //posted
        $leftjoin .= " left join client as supp on supp.clientid=head.clientid ";
      } elseif ($posttype == 1) { //unposes
        $leftjoin .= " left join client as supp on supp.client=head.client ";
      } else { //all
        $leftjoin1 .= " left join client as supp on supp.client=head.client "; //unposted
        $leftjoin2 .= " left join client as supp on supp.clientid=head.clientid "; //posted
      }
      $filter .= " and supp.clientid = '$clientid' ";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,branch,dept from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem,center,branch,dept
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,branch,dept from ( 
      select head.docno,head.clientname as supplier,
      item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref
      ,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center,branch,dept
      order by docno $sorting";
        break;

      default: // all
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,branch,dept from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin2
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and head.dateid between '$start' and '$end' $filter 
      union all
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref
      ,client.client as wh,head.rem as hrem,cntnum.center,branch.clientname as branch,dep.clientname as dept
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      $leftjoin1
      left join client as branch on branch.clientid=head.branch
      left join client as dep on dep.clientid=head.deptid
      where head.doc='AC' and date(head.dateid) between '$start' and '$end' $filter 
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center,branch,dept
      order by docno $sorting";
        break;
    }

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

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Completion Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sorting by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function headers_DEFAULT($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti
        return $this->afti_header_DEFAULT($config);
        break;
      default:
        return $this->header_DEFAULT($config);
        break;
    }
  }

  public function afti_header_DEFAULT($config)
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
    $deptcode   = $config['params']['dataparams']['dept'];
    $proj   = $config['params']['dataparams']['project'];

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

    if ($deptcode != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
    }

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Completion Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User : ' . $user, '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix : ' . $prefix, '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sorting by : ' . $sorting, '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_detailed_DEFAULT($config)
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


    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Completion Report (' . $reporttype . ')', '1000', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sorting by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function headers_detailed_DEFAULT($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti
        return $this->afti_header_detailed_DEFAULT($config);
        break;
      default:
        return $this->header_detailed_DEFAULT($config);
        break;
    }
  }

  public function afti_header_detailed_DEFAULT($config)
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
    $deptcode   = $config['params']['dataparams']['dept'];
    $proj   = $config['params']['dataparams']['project'];

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


    if ($deptcode != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }
    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
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


    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Completion Report (' . $reporttype . ')', '1000', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sorting by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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
    $str .= $this->headers_detailed_DEFAULT($config);
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '',  $font, $fontsize, 'B', '', '', '');
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
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Branch: ' . $data->branch, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Department: ' . $data->dept, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();


          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
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
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->headers_detailed_DEFAULT($config);
          $page = $page + $count;
        } //end if
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
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->headers_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $docno = "";
    $total = 0;
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->supplier, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->whname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->branch, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dept, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->hrem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $total = $total + $data->amount;
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
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
    }
    $str .= $this->reporter->endreport();

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
    $str .= $this->reporter->col('Document No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Name', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Branch', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Department', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  public function reportDefaultLayout_afti_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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
    $str .= $this->headers_detailed_DEFAULT($config);
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '',  $font, $fontsize, 'B', '', '', '');
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

          $str .= '<br/>';
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Branch: ' . $data->branch, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Department: ' . $data->dept, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('SKU/Part No.', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
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
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->headers_detailed_DEFAULT($config);
          $page = $page + $count;
        } //end if
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
}//end class