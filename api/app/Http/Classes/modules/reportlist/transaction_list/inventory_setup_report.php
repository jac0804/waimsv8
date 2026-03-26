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
use App\Http\Classes\modules\calendar\em;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class inventory_setup_report
{
  public $modulename = 'Inventory Setup Report';
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
      $fields = ['radioprint', 'start', 'end', 'dwhname', 'reportusers', 'approved'];
    } else {
      $fields = ['radioprint', 'start', 'end', 'dwhname', 'dcentername', 'reportusers', 'approved'];
    }
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


    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
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
    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'0' as whid,'' as wh,'' as whname, '' as userid, '' as username,'' as approved,
                        '0' as posttype,'0' as reporttype,'ASC' as sorting,'' as dwhname,'' as reportusers,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as project, '' as projectid, '' as projectname, 
                        '' as ddeptname, '' as dept, '' as deptname, '0' as deptid";

    // switch ($companyid) {
    //   case 10: //afti
    //   case 12: //afti usd
    //     $paramstr .= " ,'' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname";
    //     break;
    //   case 21: //kinggeorge
    //     break;
    // }

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
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
          case '2':
            $result = $this->SUMMIT_summarized_per_item($config);
            break;
        }
        break;

      case 10:
      case 12: //afti
        switch ($reporttype) {
          case 0:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case 1:
            $result = $this->reportDefaultLayout_afti_DETAILED($config);
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
      case 10:
      case 12: //afti
        $query = $this->AFTI_QUERY($config); 
        break;
      case 21: //kinggeorge
        $query = $this->KING_QUERY($config);
        break;
      default:
        $query = $this->default_QUERY($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh         = $config['params']['dataparams']['wh'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $whid         = $config['params']['dataparams']['whid'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid' ";
    }

    switch ($reporttype) {
      case 0:
        switch ($posttype) {
          case 0:
            $query = "select * from (
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;

          case 1:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;

          default:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          UNION ALL
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0:
            $query = "select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting
          ";
            break;

          case 1:
            $query = "select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting
          ";
            break;

          default:
            $query = "select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          union all
          select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client, dept.clientname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting
          ";
            break;
        }
        break;
    }

    return $query;
  }

  public function AFTI_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $prjid = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['deptid'];
    $project = $config['params']['dataparams']['projectid'];
    $dept = $config['params']['dataparams']['dept']; //deptcode

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

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid' ";
    }

    if ($prjid != "") {
      $filter .= " and stock.projectid = $project";
    }
    if ($dept != "") {
      $filter .= " and head.deptid = $deptid";
    }


    switch ($reporttype) {
      case 0:
        switch ($posttype) {
          case 0:
            $query = "select * from (
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;

          case 1:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;

          default:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          UNION ALL
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0:
            $query = "select head.docno,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
            stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client as dept on dept.clientid = head.deptid
         left join model_masterfile as model on model.model_id=item.model 
         left join frontend_ebrands as brand on brand.brandid = item.brand 
         left join iteminfo as i on i.itemid  = item.itemid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting
          ";
            break;

          case 1:
            $query = "select head.docno,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
            stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as dept on dept.clientid = head.deptid
         left join model_masterfile as model on model.model_id=item.model 
         left join frontend_ebrands as brand on brand.brandid = item.brand 
         left join iteminfo as i on i.itemid  = item.itemid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting
          ";
            break;

          default:
            $query = "select head.docno,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
            stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client as dept on dept.clientid = head.deptid
         left join model_masterfile as model on model.model_id=item.model 
         left join frontend_ebrands as brand on brand.brandid = item.brand 
         left join iteminfo as i on i.itemid  = item.itemid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          union all
          select head.docno,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client, dept.clientname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as dept on dept.clientid = head.deptid
         left join model_masterfile as model on model.model_id=item.model 
         left join frontend_ebrands as brand on brand.brandid = item.brand 
         left join iteminfo as i on i.itemid  = item.itemid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter 
          order by docno $sorting
          ";
            break;
        }
        break;
    }

    return $query;
  }

  public function KING_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid' ";
    }


    switch ($reporttype) {
      case 0:
        switch ($posttype) {
          case 0:
            $query = "select * from (
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'
          and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;

          case 1:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS' and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;

          default:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS' and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          UNION ALL
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'
          and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0:
            $query = "select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          case 1:
            $query = "select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          default:
            $query = "select head.docno,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter
          union all
          select head.docno,item.barcode,item.itemname,stm,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client, dept.clientname
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as dept on dept.clientid = head.deptid
          where head.doc='IS'  and date(head.dateid) between '$start' and '$end' $filter
          order by docno $sorting";
            break;
        }
        break;
    }

    return $query;
  }

  public function SUMMIT_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh         = $config['params']['dataparams']['wh'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $whid         = $config['params']['dataparams']['whid'];

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
    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid' ";
    }

    switch ($reporttype) {
      case 0:
        switch ($posttype) {
          case 0:
            $query = "select * from (
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid 
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          where head.doc='IS'
          and head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";
            break;

          case 1:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='IS' and head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";
            break;

          default:
            $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier,head.createby,
          left(head.dateid,10) as dateid from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='IS' and head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid
          UNION ALL
          select 'POSTED' as status,head.docno,
          sum(stock.ext) as ext, wh.clientname as supplier, head.createby,
          left(head.dateid,10) as dateid from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid = head.clientid
          where head.doc='IS'
          and head.dateid between '$start' and '$end' $filter 
          group by head.docno,head.clientname,wh.clientname, head.createby, head.dateid
          ) as g order by g.docno $sorting";
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0:
            $query = "select head.docno,
          item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc='IS'  and head.dateid between '$start' and '$end' $filter
          order by docno $sorting
          ";
            break;

          case 1:
            $query = "select head.docno,
          item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          where head.doc='IS'  and head.dateid between '$start' and '$end' $filter
          order by docno $sorting
          ";
            break;

          default:
            $query = "select head.docno,
          item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc='IS'  and head.dateid between '$start' and '$end' $filter
          union all
          select head.docno,
          item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          wh.clientname ,head.createby,stock.expiry,stock.loc,stock.rem,head.dateid,stock.ref
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          where head.doc='IS'  and head.dateid between '$start' and '$end' $filter
          order by docno $sorting
          ";
            break;
        }
        break;
      case 2:
      case '2':
      case 2: // summarize per item
        switch ($posttype) {
          case 0: // posted
            $query = "
                select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
                from (
                select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
                item.uom, stock.qty as qty, 
                stock.ext as amount,
                wh.client as whcode, wh.clientname as whname
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item as item on item.itemid = stock.itemid
                left join client as wh on wh.clientid = head.whid
                left join cntnum on cntnum.trno=head.trno
                where head.doc = 'IS' and head.dateid between '$start' and '$end' $filter ) as a
                group by whname, itemname, uom
                order by whname " . $sorting . "";
            break;
          case 1: // unposted
            $query = "
                select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
                from (
                select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
                item.uom, stock.qty as qty, 
                stock.ext as amount,
                wh.client as whcode, wh.clientname as whname
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join item as item on item.itemid = stock.itemid
                left join client as wh on wh.client = head.wh
                left join cntnum on cntnum.trno=head.trno
                where head.doc = 'IS' and head.dateid between '$start' and '$end' $filter ) as a
                group by whname, itemname, uom
                order by whname " . $sorting . "";
            break;
          case 2: // all
            $query = "
                select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
                from (
                select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
                item.uom, stock.qty as qty, 
                stock.ext as amount,
                wh.client as whcode, wh.clientname as whname
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join item as item on item.itemid = stock.itemid
                left join client as wh on wh.client = head.wh
                left join cntnum on cntnum.trno=head.trno
                where head.doc = 'IS' and head.dateid between '$start' and '$end' $filter 
                union all
                select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
                item.uom, stock.qty as qty, 
                stock.ext as amount,
                wh.client as whcode, wh.clientname as whname
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item as item on item.itemid = stock.itemid
                left join client as wh on wh.clientid = head.whid
                left join cntnum on cntnum.trno=head.trno
                where head.doc = 'IS' and head.dateid between '$start' and '$end' $filter ) as a
                group by whname, itemname, uom
                order by whname " . $sorting . "";
            break;
        }
        break;
        break;
        break;
    }

    return $query;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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

    $i = 0;
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
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
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('Item Description', '195', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Quantity', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Discount', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Location', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '195', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->disc, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loc, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
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

  public function headers_DEFAULT($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        return $this->afti_header_DEFAULT($config);
        break;
      default:
        return $this->header_DEFAULT($config);
        break;
    }
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
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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


    $layoutsize = '1000';
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
    $str .= $this->reporter->col('Inventory Setup Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function afti_header_DEFAULT($config)
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
    $dept   = $config['params']['dataparams']['dept'];
    $proj   = $config['params']['dataparams']['project'];
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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


    $layoutsize = '1000';
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
    $str .= $this->reporter->col('Inventory Setup Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 64;
    $page = 63;
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
    $str .= $this->headers_DEFAULT($config);
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
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
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
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .=  $this->reporter->col('WAREHOUSE', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_summarized_per_item($config)
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
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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

    $layoutsize = '1000';
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
    $str .= $this->reporter->col('Inventory Setup Summarized Per Item', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border,  '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();
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

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_summarized_per_item($config);
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
          $str .= $this->reporter->col("Warehouse: " . $data->whname, '100', null, false, $border, '', 'L', $font, '10', 'B', '', '');
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
          $str .= $this->header_summarized_per_item($config);
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

  public function reportDefaultLayout_afti_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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

    $i = 0;
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
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
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Item Description', '195', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Quantity', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Discount', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Location', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '195', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->disc, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loc, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
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