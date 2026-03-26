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

class inventory_adjustment_report
{
  public $modulename = 'Inventory Adjustment Report';
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

    switch ($companyid) {
      case 21: //kinggeorge
        $fields = ['radioprint', 'start', 'end', 'reportusers', 'approved', 'dwhname'];
        break;
      case 17: //unihome
      case 39: //CBBSI
        $fields = ['radioprint', 'start', 'end', 'dwhname', 'reportusers', 'approved'];
        break;
      case 15: //nathina
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'dwhname', 'reportusers', 'approved'];
        break;
      default:
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'approved'];
        break;
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
      case 11: //summit
        array_push($fields, 'dwhref');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 56: // homeworks
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
          // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
          ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
          ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
        ]);
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
    $paramstr = "select 'default' as print,adddate(left(now(),10),-30) as start, left(now(),10) as end,'' as userid,'' as username,'0' as posttype,
                '0' as reporttype, 'ASC' as sorting, '' as reportusers,'' as approved,
                '" . $defaultcenter[0]['center'] . "' as center,
                '" . $defaultcenter[0]['centername'] . "' as centername,
                '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

    // ,
    // '' as project, '' as projectid, '' as projectname, '0' as deptid, '' as ddeptname, '' as dept, '' as deptname,
    // '' as dwhref, '' as whref, '' as whnameref,
    // '0' as whid, '' as wh,'' as whname,'' as dwhname

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= " ,'' as project, '' as projectid, '' as projectname, '0' as deptid, '' as ddeptname, '' as dept, '' as deptname";
        break;
      case 11: //summit
        $paramstr .= ", '' as dwhref, '' as whref, '' as whnameref";
        break;
      case 17: //unihome
      case 39: //CBBSI
      case 15: //nathina
      case 21: //kinggeorge
        $paramstr .= ", '0' as whid, '' as wh,'' as whname,'' as dwhname";
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
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {

      case 10:
      case 12: //afti
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->afti_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->afti_DETAILED($config);
            break;
        }
        break;

      case 11: // summit
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->summit_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->summit_DETAILED($config);
            break;
          case '2':
            $result = $this->SUMMIT_summarized_per_item($config);
            break;
        }
        break;
      case 15: //nathina
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->nathina_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->nathina_DETAILED($config);
            break;
        }
        break;
      case 17: //unihome
      case 39: //CBBSI
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportUnihomeLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;


      case 17: //unihome
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportUnihomeLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;

      case 39: //cbbsi
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportcbbsiLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->cbbsi_DETAILED($config);
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
    $companyid = $config['params']['companyid'];
    // QUERY
    switch ($companyid) {
      case 10:
      case 12: //afti
        $query = $this->AFTI_QUERY($config);
        break;
      case 11: //summit
        $query = $this->SUMMIT_QUERY($config);
        break;
      case 17: //unihome
      case 39: //CBBSI
        $query = $this->UNIHOME_QUERY($config);
        break;
      case 15: //NATHINA
        $query = $this->NATHINA_QUERY($config);
        break;
      case 21: //kinggeorge
        $query = $this->KINGGEORGE_QUERY($config);
        break;
      default:
        $query = $this->default_QUERY($config);
        break;
    }

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
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by docno $sorting";

            break;
          case '1': // UNPOSTED
            $query = "select * 
            from (select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a  order by docno $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by docno $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED
        // $addedfields = '';
        // $joins = '';
        // $addedgrp = '';
        // $addfield = '';
        // switch ($companyid) {
        //   case 10: //afti
        //   case 12: //afti usd
        //     $addfield .= ", stockgrp_name";
        //     $addedfields .= ", p.name as stockgrp_name ";
        //     $joins = "left join projectmasterfile as p on p.line = stock.projectid ";
        //     $addedgrp = ",p.name ";
        //     break;
        //     // case 15: //nathina
        //     //   $addfield .= ", swh, swhname,notes";
        //     //   $addedfields .= ',wh.client as swh,wh.clientname as swhname,stock.rem as notes';
        //     //   $joins .= 'left join client as wh on wh.clientid=stock.whid';
        //     //   break;
        //     // case 21: //kinggeorge
        //     //   $joins .= 'left join client as wh on wh.clientid=stock.whid';
        //     //   break;
        // }
        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            left(head.dateid, 10) as dateid, sum(stock.ext) as ext, p.name as stockgrp_name
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            group by head.docno, source.clientname, head.clientname, head.dateid, stockgrp_name
          ) as g order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination, stockgrp_name
            from (select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, p.name as stockgrp_name
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a 
            group by docno, a.dateid, whsource, whdestination, status, stockgrp_name
            order by docno 
            $sorting";
            break;
          case '2': // ALL
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination, stockgrp_name
            from ( select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, p.name as stockgrp_name
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, p.name as stockgrp_name
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a 
            group by docno, a.dateid, whsource, whdestination, status, stockgrp_name
            order by docno $sorting";
            break;
        }
        break;
    }

    return $query;
  }

  public function KINGGEORGE_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid     = $config['params']['dataparams']['whid'];

    $filter = "";
    $joins = '';

    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($wh != "") {
      $joins .= 'left join client as wh on wh.clientid=stock.whid';
      $filter .= " and wh.clientid= '" . $whid . "'";
    }

    switch ($reporttype) {
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter   order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select * 
            from (select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a  order by docno $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by docno $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            left(head.dateid, 10) as dateid, sum(stock.ext) as ext 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            group by head.docno, source.clientname, head.clientname, head.dateid 
          ) as g order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination 
            from (select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a 
            group by docno, a.dateid, whsource, whdestination, status 
            order by docno 
            $sorting";
            break;
          case '2': // ALL
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination 
            from ( select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno $joins
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a 
            group by docno, a.dateid, whsource, whdestination, status  
            order by docno $sorting";
            break;
        }
        break;
    }

    return $query;
  }


  public function NATHINA_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $whid     = $config['params']['dataparams']['whid'];
    $wh         = $config['params']['dataparams']['wh'];

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
      $filter .= " and wh.clientid= '" . $whid . "'";
    }

    switch ($reporttype) {
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,wh.client as swh,wh.clientname as swhname,
            head.yourref,head.ourref,head.rem as notes,source.client as sourcewh
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by docno $sorting";

            break;
          case '1': // UNPOSTED
            $query = "select * 
            from (select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,wh.client as swh,wh.clientname as swhname,
            head.yourref,head.ourref,head.rem as notes,source.client as sourcewh
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a  order by docno $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,wh.client as swh,wh.clientname as swhname,
            head.yourref,head.ourref,head.rem as notes,source.client as sourcewh
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,wh.client as swh,wh.clientname as swhname,
            head.yourref,head.ourref,head.rem as notes,source.client as sourcewh
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by docno $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            left(head.dateid, 10) as dateid, sum(stock.ext) as ext ,wh.client as swh,wh.clientname as swhname,stock.rem as notes
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter
            group by head.docno, source.clientname, head.clientname, head.dateid , swh, swhname,notes
          ) as g order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination , swh, swhname,notes
            from (select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,wh.client as swh,wh.clientname as swhname,stock.rem as notes
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a 
            group by docno, a.dateid, whsource, whdestination, status , swh, swhname,notes
            order by docno 
            $sorting";
            break;
          case '2': // ALL
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination , swh, swhname,notes
            from ( select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,wh.client as swh,wh.clientname as swhname,stock.rem as notes
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter
            union all
            select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,wh.client as swh,wh.clientname as swhname,stock.rem as notes
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join client as wh on wh.clientid=stock.whid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a 
            group by docno, a.dateid, whsource, whdestination, status  , swh, swhname,notes
            order by docno $sorting";
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
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $prjcode = $config['params']['dataparams']['project'];
    $deptcode = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];

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

    if ($prjcode != "") {
      $filter .= " and stock.projectid = $projectid";
    }
    if ($deptcode != "") {
      $filter .= " and head.deptid = $deptid";
    }

    switch ($reporttype) {
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,item.partno,
             concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemdescription,
            ifnull(group_concat(rr.serial separator ' / '),'') as serialno 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join model_masterfile as model on model.model_id = item.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join iteminfo as i on i.itemid  = item.itemid 
            left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
            where head.doc='AJ' and head.dateid between '$start' and '$end' $filter 
            group by head.docno,source.clientname,head.clientname,
            head.dateid,item.barcode,stock.qty,stock.iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, model.model_name ,
            brand.brand_desc,i.itemdescription,item.partno order by docno $sorting";

            break;
          case '1': // UNPOSTED
            $query = "select * 
            from (select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,item.partno,
             concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemdescription,
            ifnull(group_concat(rr.serial separator ' / '),'') as serialno 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            
            left join model_masterfile as model on model.model_id = item.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join iteminfo as i on i.itemid  = item.itemid 
            left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line


            where head.doc='AJ' and head.dateid between '$start' and '$end' $filter 
            group by head.docno,source.clientname,head.clientname,
            head.dateid,item.barcode,stock.qty,stock.iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, model.model_name ,
            brand.brand_desc,i.itemdescription,item.partno) as a  order by docno $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,item.partno,
             concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemdescription,
            ifnull(group_concat(rr.serial separator ' / '),'') as serialno 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            
            left join model_masterfile as model on model.model_id = item.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join iteminfo as i on i.itemid  = item.itemid 
            left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line


            where head.doc='AJ' and head.dateid between '$start' and '$end' $filter 
            group by head.docno,source.clientname,head.clientname,
            head.dateid,item.barcode,stock.qty,stock.iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, model.model_name ,
            brand.brand_desc,i.itemdescription,item.partno
            union all
            select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,item.partno,
             concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemdescription,
            ifnull(group_concat(rr.serial separator ' / '),'') as serialno 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            
            left join model_masterfile as model on model.model_id = item.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join iteminfo as i on i.itemid  = item.itemid 
            left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line

            where head.doc='AJ' and head.dateid between '$start' and '$end' $filter 
            group by head.docno,source.clientname,head.clientname,
            head.dateid,item.barcode,stock.qty,stock.iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, model.model_name ,
            brand.brand_desc,i.itemdescription,item.partno order by docno $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            left(head.dateid, 10) as dateid, sum(stock.ext) as ext, p.name as stockgrp_name 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter
            group by head.docno, source.clientname, head.clientname, head.dateid, stockgrp_name
          ) as g order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination, stockgrp_name
            from (select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, p.name as stockgrp_name 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a 
            group by docno, a.dateid, whsource, whdestination, status, stockgrp_name
            order by docno 
            $sorting";
            break;
          case '2': // ALL
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination, stockgrp_name
            from ( select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            date(head.dateid) as dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, p.name as stockgrp_name 
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter
            union all
            select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            date(head.dateid) as dateid,item.barcode,stock.rrqty as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost, p.name as stockgrp_name 
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            left join projectmasterfile as p on p.line = stock.projectid
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a 
            group by docno, a.dateid, whsource, whdestination, status, stockgrp_name
            order by docno $sorting";
            break;
        }
        break;
    }

    return $query;
  }


  public function UNIHOME_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname         = $config['params']['dataparams']['whname'];

    $filter = "";
    $filter1 = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($whname != '') {
      $filter .= " and source.client = '$wh' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }


    $filter1 .= "";


    $query = "";

    switch ($reporttype) {
      case '1': // DETAILED

        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
              head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
              stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
              from glstock as stock
              left join glhead as head on head.trno=stock.trno
              left join client as source on source.clientid=head.whid
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1  order by docno $sorting";

            break;
          case '1': // UNPOSTED
            $query = "select * 
              from (select head.docno,source.clientname as whsource,head.clientname as whdestination,
              head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
              stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
              from lastock as stock
              left join lahead as head on head.trno=stock.trno
              left join client as source on source.client=head.wh
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid=stock.itemid 
              where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1 ) as a  order by docno $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
              head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
              stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
              from lastock as stock
              left join lahead as head on head.trno=stock.trno
              left join client as source on source.client=head.wh
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid=stock.itemid 
              where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1 
              union all
              select head.docno,source.clientname as whsource,head.clientname as whdestination,
              head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
              stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost 
              from glstock as stock
              left join glhead as head on head.trno=stock.trno
              left join client as source on source.clientid=head.whid
              left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1  order by docno $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED

        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            left(head.dateid, 10) as dateid, sum(stock.ext) as ext,head.rem as headrem
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1
            group by head.docno, source.clientname, head.clientname, head.dateid,head.rem 
          ) as g order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination,headrem
            from (select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.rem as headrem
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1) as a 
            group by docno, dateid, whsource, whdestination, status,headrem
            order by docno 
            $sorting";
            break;
          case '2': // ALL
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination,headrem 
            from ( select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,head.rem as headrem
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1
            union all
            select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost ,head.rem as headrem
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter $filter1) as a 
            group by docno, dateid, whsource, whdestination, status  ,headrem
            order by docno $sorting";
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

    $whref = "";

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

    if ($whref != "") {
      $filter .= " and head.whref = '$whref'";
    }

    $query = "";
    switch ($reporttype) {
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select * 
            from (select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a order by docno $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter
            union all
            select head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter order by docno $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            left(head.dateid, 10) as dateid, sum(stock.ext) as ext,head.whref,whref.clientname as layinghouseref
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            group by head.docno, source.clientname, head.clientname, head.dateid ,head.whref,whref.clientname
          ) as g order by docno $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination,whref,layinghouseref
            from (select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a 
            group by docno, dateid, whsource, whdestination, status,whref,layinghouseref
            order by docno 
            $sorting";
            break;
          case '2': // ALL
            $query = "select docno, left(dateid, 10) as dateid, status, sum(ext) as ext, whsource, whdestination,whref,layinghouseref
            from ( select 'UNPOSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join client as source on source.client=head.wh
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter
            union all
            select 'POSTED' as status, head.docno,source.clientname as whsource,head.clientname as whdestination,
            head.dateid,item.barcode,(stock.qty-stock.iss) as iss,stock.uom,item.itemname,stock.isamt,
            stock.ext,stock.loc,stock.expiry,stock.rem,stock.rrqty,stock.rrcost,head.whref,whref.clientname as layinghouseref
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno
            left join client as whref on whref.client = head.whref
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter) as a 
            group by docno, dateid, whsource, whdestination, status ,whref,layinghouseref
            order by docno $sorting";
            break;
        }
        break;
      case '2':
      case 2: // summarize per item
        switch ($posttype) {
          case 0: // posted
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount,layinghouseref
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, (stock.qty-stock.iss) as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname,head.whref,ifnull(whref.clientname,' ') as layinghouseref
              from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.clientid = head.whid
              left join cntnum on cntnum.trno=head.trno
              left join client as whref on whref.client = head.whref
              where head.doc = 'AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a
              where itemname is not null
              group by whname, itemname, uom,layinghouseref
              order by whname,layinghouseref,itemname,uom " . $sorting . "";
            break;
          case 1: // unposted
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount,layinghouseref
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, (stock.qty-stock.iss) as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname,head.whref,ifnull(whref.clientname,' ') as layinghouseref
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join cntnum on cntnum.trno=head.trno
              left join client as whref on whref.client = head.whref
              where head.doc = 'AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a
              where itemname is not null
              group by whname, itemname, uom, layinghouseref
              order by whname,layinghouseref,itemname,uom " . $sorting . "";
            break;
          case 2: // all
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount,layinghouseref 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, (stock.qty-stock.iss) as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname,head.whref,ifnull(whref.clientname,' ') as layinghouseref
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join cntnum on cntnum.trno=head.trno
              left join client as whref on whref.client = head.whref
              where head.doc = 'AJ' and date(head.dateid) between '$start' and '$end' $filter 
              union all
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, (stock.qty-stock.iss) as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname,head.whref,ifnull(whref.clientname,' ') as layinghouseref
              from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.clientid = head.whid
              left join cntnum on cntnum.trno=head.trno
              left join client as whref on whref.client = head.whref
              where head.doc = 'AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a
              where itemname is not null
              group by whname, itemname, uom, layinghouseref
              order by whname,layinghouseref,itemname,uom " . $sorting . "";
            break;
        }
        break;
        break;
    }

    return $query;
  }

  public function reportUnihomeLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

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
    $str .= $this->headers_DEFAULT($config);
    $str .= $this->tableheaders($layoutsize, $config);

    $totalext = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->whsource, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->headrem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheaders($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();



    $str .= $this->reporter->col('TOTAL :', '500', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');



    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportcbbsiLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

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
    $str .= $this->headers_DEFAULT($config);
    $str .= $this->tableheaders($layoutsize, $config);

    $totalext = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->whsource, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->headrem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheaders($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();



    $str .= $this->reporter->col('TOTAL :', '500', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');



    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function default_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $str .= $this->reporter->col('Inventory Adjustment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    // if (empty($result)) {
    //   return $this->othersClass->emptydata($config);
    // }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_headers_detailed($config);

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;
    $grandtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
          $str .= $this->reporter->endtable();



          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', $fontsize, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        if ($data->iss > 0) {
          $str .= $this->reporter->col(number_format($data->iss, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');



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
        $grandtotal += $data->ext;
        $i++;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->endrow();
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
    $str .= $this->reporter->col('Inventory Adjustment Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

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
    $str .= $this->headers_DEFAULT($config);
    $str .= $this->tableheaders($layoutsize, $config);

    $totalext = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->whdestination, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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
          $str .= $this->tableheaders($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('TOTAL :', '500', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
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

    $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
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
    $whnameref = $config['params']['dataparams']['whnameref'];
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

    if ($whnameref == '') {
      $whnameref = 'ALL';
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
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Inventory Adjustment Summarized Per Item', '1000', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function SUMMIT_summarized_per_item($config)
  {
    $result = $this->reportDefault($config);
    $count = 36;
    $page = 35;
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
    $str .= $this->header_summarized_per_item($config);
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $whname = "";
    $whref = "";
    $subtotal = 0;
    $grandtotal = 0;

    $counter = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        if ($whname != $data->whname || $whref != $data->layinghouseref) {
          if ($whname != "") {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col("SUBTOTAL: ", '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $subtotal = 0;
          }
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("Warehouse: " . $data->whname, '500', null, false, $border, '', 'L', $font, '10', 'B', '', '');

          $str .= $this->reporter->col("Laying House Reference: " . $data->layinghouseref, '500', null, false, $border, '', 'L', $font, '10', 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ITEMNAME', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('QTY', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_summarized_per_item($config);
          $str .= $this->reporter->begintable($layoutsize);
          $page = $page + $count;
        } //end if

        $whname = $data->whname;
        $whref = $data->layinghouseref;
        $subtotal += $data->amount;
        $grandtotal += $data->amount;
        $counter++;

        if ($counter == count($result)) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("", '400', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '200', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("SUBTOTAL: ", '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summit_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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

    if ($companyid == 11) { //summit
      $whnameref = $config['params']['dataparams']['whnameref'];
      if ($whnameref == '') {
        $whnameref = 'ALL';
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Inventory Adjustment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow(NULL, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->endrow();
      if ($companyid == 11) { //summit                                                                                                                                                                                                                                                                                                                                                                                                                                                        
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function summit_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

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
    $str .= $this->default_headers_detailed($config);

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse : ' . $data->whsource, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Laying House Reference : ' . $data->layinghouseref, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '350', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        if ($data->iss > '0') {
          $str .= $this->reporter->col(number_format($data->iss, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
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

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_headers_detailed($config);
          $str .= $this->reporter->addline();
          $page = $page + $count;

          $str .= $this->reporter->printline();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse : ' . $data->whsource, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Laying House Reference : ' . $data->layinghouseref, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '350', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', $fontsize, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summit_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

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
    $str .= $this->headers_DEFAULT($config);
    $str .= $this->tableheaders($layoutsize, $config);

    $totalext = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->whsource, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->layinghouseref, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheader($companyid, $layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('TOTAL :', '750', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function nathina_DETAILED($config)
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
    $str .= $this->default_headers_detailed($config); //default

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;
    $grandtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse : ' . $data->sourcewh, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Yourref : ' . $data->yourref, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Ourref : ' . $data->ourref, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes : ' . $data->notes, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', $fontsize, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        if ($data->iss > 0) {
          $str .= $this->reporter->col(number_format($data->iss, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');

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
        $grandtotal += $data->ext;
        $i++;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function nathina_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
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
    $str .= $this->headers_DEFAULT($config); //default
    $str .= $this->tableheaders($layoutsize, $config);

    $totalext = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->swh, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->notes, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheaders($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL :', '450', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function afti_DETAILED($config)
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
    $str .= $this->default_headers_detailed($config);

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;
    $grandtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Serial', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->partno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemdescription, '190', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->serialno, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(round($data->iss), '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }

        $str .= $this->reporter->endtable();
        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1000', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $grandtotal += $data->ext;
        $i++;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function default_headers_detailed($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        return $this->default_afti_header_detailed($config);
        break;
      case 11: //summit
        return $this->default_summit_header_detailed($config);
        break;
      case 21: //kinggeorge
        return $this->default_kinggeorge_header_detailed($config);
        break;
      default:
        return $this->default_header_detailed($config);
        break;
    }
  }

  public function default_afti_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $deptname   = $config['params']['dataparams']['ddeptname'];
    $dept   = $config['params']['dataparams']['dept']; //deptcode
    $proj   = $config['params']['dataparams']['project'];
    // $projname = $config['params']['dataparams']['projectname'];

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $dept   = $config['params']['dataparams']['ddeptname'];
    //   $proj   = $config['params']['dataparams']['project'];

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
    // }

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

    // if ($companyid == 11) { //summit
    //   $whnameref = $config['params']['dataparams']['whnameref'];
    //   if ($whnameref == '') {
    //     $whnameref = 'ALL';
    //   }
    // }


    // if ($companyid == 21) { //kinggeorge
    //   $wh   = $config['params']['dataparams']['whname'];
    //   if ($wh == '') {
    //     $wh = 'ALL';
    //   }
    // }

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
    $str .= $this->reporter->col('Inventory Adjustment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    // } else {
    //   $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    //   $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   if ($companyid == 21) { //kinggeorge
    //     $str .= $this->reporter->col('WH: ' . $wh, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   }
    //   $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //   $str .= $this->reporter->endrow();
    //   if ($companyid == 11) { //summit
    //     $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    //     $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //     $str .= $this->reporter->endrow();
    //   }
    // }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function default_summit_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $whnameref = $config['params']['dataparams']['whnameref'];

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $dept   = $config['params']['dataparams']['ddeptname'];
    //   $proj   = $config['params']['dataparams']['project'];

    //   if ($dept != "") {
    //     $deptname = $config['params']['dataparams']['deptname'];
    //   } else {
    //     $deptname = "ALL";
    //   }

    //   if ($proj != "") {
    //     $projname = $config['params']['dataparams']['projectname'];
    //   } else {
    //     $projname = "ALL";
    //   }
    // }

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

    // if ($companyid == 11) { //summit

    if ($whnameref == '') {
      $whnameref = 'ALL';
    }
    // }


    // if ($companyid == 21) { //kinggeorge
    //   $wh   = $config['params']['dataparams']['whname'];
    //   if ($wh == '') {
    //     $wh = 'ALL';
    //   }
    // }

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
    $str .= $this->reporter->col('Inventory Adjustment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    // } else {
    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // if ($companyid == 21) { //kinggeorge
    //   $str .= $this->reporter->col('WH: ' . $wh, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // }
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    // if ($companyid == 11) { //summit
    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    // }
    // }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function default_kinggeorge_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh   = $config['params']['dataparams']['whname'];

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $dept   = $config['params']['dataparams']['ddeptname'];
    //   $proj   = $config['params']['dataparams']['project'];

    //   if ($dept != "") {
    //     $deptname = $config['params']['dataparams']['deptname'];
    //   } else {
    //     $deptname = "ALL";
    //   }

    //   if ($proj != "") {
    //     $projname = $config['params']['dataparams']['projectname'];
    //   } else {
    //     $projname = "ALL";
    //   }
    // }

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

    // if ($companyid == 11) { //summit
    //   $whnameref = $config['params']['dataparams']['whnameref'];
    //   if ($whnameref == '') {
    //     $whnameref = 'ALL';
    //   }
    // }


    // if ($companyid == 21) { //kinggeorge

    if ($wh == '') {
      $wh = 'ALL';
    }
    // }

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
    $str .= $this->reporter->col('Inventory Adjustment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    // } else {
    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // if ($companyid == 21) { //kinggeorge
    $str .= $this->reporter->col('WH: ' . $wh, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // }
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    // if ($companyid == 11) { //summit
    //   $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    //   $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //   $str .= $this->reporter->endrow();
    // }
    // }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function tableheaders($layoutsize, $config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        return $this->afti_tableheader($layoutsize, $config);
        break;
      case 11: //summit
        return $this->summit_tableheader($layoutsize, $config);
        break;

      case 17: //unihome
      case 39: //cbbsi
        return $this->other2_tableheader($layoutsize, $config);
        break;

      case 15: //nathina
        return $this->nathina_tableheader($layoutsize, $config);
        break;
      default:
        return $this->tableheader($layoutsize, $config);
        break;
    }
  }



  public function headers_DEFAULT($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        return $this->afti_headers_DEFAULT($config);
        break;
      case 11: //summit
        return $this->summit_headers_DEFAULT($config);
        break;
      case 21: //kinggeorge
        return $this->kinggeorge_headers_DEFAULT($config);
        break;
      default:
        return $this->header_DEFAULT($config);
        break;
    }
  }


  public function afti_headers_DEFAULT($config)
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
    $dept   = $config['params']['dataparams']['dept']; //deptcode
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

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd



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
    // }

    // if ($companyid == 11) { //summit
    //   $whnameref = $config['params']['dataparams']['whnameref'];
    //   if ($whnameref == '') {
    //     $whnameref = 'ALL';
    //   }
    // }

    // if ($companyid == 21) { //kinggeorge
    //   $wh   = $config['params']['dataparams']['whname'];
    //   if ($wh == '') {
    //     $wh = 'ALL';
    //   }
    // }


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
    $str .= $this->reporter->col('Inventory Adjustment Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    // switch ($companyid) {
    //   case 10: //afti
    //   case 12: //afti usd
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    //     break;
    //   default:
    //     $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    //     $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //     if ($companyid == 21) { //kinggeorge
    //       $str .= $this->reporter->col('WH: ' . $wh, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //     }
    //     $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //     $str .= $this->reporter->endrow();
    //     if ($companyid == 11) { //summit
    //       $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //       $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //       $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //       $str .= $this->reporter->endrow();
    //     }
    //     break;
    // }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function summit_headers_DEFAULT($config)
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

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $dept   = $config['params']['dataparams']['ddeptname'];
    //   $proj   = $config['params']['dataparams']['project'];

    //   if ($dept != "") {
    //     $deptname = $config['params']['dataparams']['deptname'];
    //   } else {
    //     $deptname = "ALL";
    //   }

    //   if ($proj != "") {
    //     $projname = $config['params']['dataparams']['projectname'];
    //   } else {
    //     $projname = "ALL";
    //   }
    // }

    if ($companyid == 11) { //summit
      $whnameref = $config['params']['dataparams']['whnameref'];
      if ($whnameref == '') {
        $whnameref = 'ALL';
      }
    }

    // if ($companyid == 21) { //kinggeorge
    //   $wh   = $config['params']['dataparams']['whname'];
    //   if ($wh == '') {
    //     $wh = 'ALL';
    //   }
    // }


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
    $str .= $this->reporter->col('Inventory Adjustment Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    // switch ($companyid) {
    // case 10: //afti
    // case 12: //afti usd
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   break;
    // default:
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // if ($companyid == 21) { //kinggeorge
    //   $str .= $this->reporter->col('WH: ' . $wh, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // }
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    // if ($companyid == 11) { //summit
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    // }
    // break;
    // }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function kinggeorge_headers_DEFAULT($config)
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

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $dept   = $config['params']['dataparams']['ddeptname'];
    //   $proj   = $config['params']['dataparams']['project'];

    //   if ($dept != "") {
    //     $deptname = $config['params']['dataparams']['deptname'];
    //   } else {
    //     $deptname = "ALL";
    //   }

    //   if ($proj != "") {
    //     $projname = $config['params']['dataparams']['projectname'];
    //   } else {
    //     $projname = "ALL";
    //   }
    // }

    // if ($companyid == 11) { //summit
    //   $whnameref = $config['params']['dataparams']['whnameref'];
    //   if ($whnameref == '') {
    //     $whnameref = 'ALL';
    //   }
    // }

    // if ($companyid == 21) { //kinggeorge
    $wh   = $config['params']['dataparams']['whname'];
    if ($wh == '') {
      $wh = 'ALL';
    }
    // }


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
    $str .= $this->reporter->col('Inventory Adjustment Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    // switch ($companyid) {
    // case 10: //afti
    // case 12: //afti usd
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   break;
    // default:
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // if ($companyid == 21) { //kinggeorge
    $str .= $this->reporter->col('WH: ' . $wh, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    // }
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    // if ($companyid == 11) { //summit
    //   $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    //   $str .= $this->reporter->col('Laying House WH: ' . $whnameref, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //   $str .= $this->reporter->endrow();
    // }
    //     break;
    // }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function summit_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    // switch ($companyid) {
    //   case 11: //summit
    $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LAYING HOUSE REFERENCE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 10: //afti
    //   case 12: //afti usd
    //     $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('ITEM GROUP', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 17: //unihome
    //   case 39: //CBBSI
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 15: //nathina
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   default:
    //     $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    // }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function afti_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    // switch ($companyid) {
    // //   case 11: //summit
    // $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('LAYING HOUSE REFERENCE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 10: //afti
    //   case 12: //afti usd
    $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 17: //unihome
    //   case 39: //CBBSI
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 15: //nathina
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   default:
    //     $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    // }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function other2_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    // switch ($companyid) {
    // //   case 11: //summit
    // $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('LAYING HOUSE REFERENCE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 10: //afti
    //   case 12: //afti usd
    // $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('ITEM GROUP', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 17: //unihome
    //   case 39: //CBBSI
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('WAREHOUSE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 15: //nathina
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   default:
    //     $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    // }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function nathina_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    // switch ($companyid) {
    //   case 11: //summit
    //     $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('LAYING HOUSE REFERENCE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 10: //afti
    //   case 12: //afti usd
    //     $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('ITEM GROUP', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 17: //unihome
    //   case 39: //CBBSI
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('WAREHOUSE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   case 15: //nathina
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('WAREHOUSE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOTES', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    //   default:
    //     $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    //     break;
    // }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function afti_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
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
    $str .= $this->headers_DEFAULT($config);
    $str .= $this->tableheaders($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->whsource, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->stockgrp_name, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->headers_DEFAULT($config);
          $str .= $this->tableheaders($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL :', '750', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function cbbsi_DETAILED($config)
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
    $str .= $this->default_headers_detailed($config); //def

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;
    $grandtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', $fontsize, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        if ($data->iss > 0) {
          $str .= $this->reporter->col(number_format($data->iss, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');

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
        $grandtotal += $data->ext;
        $i++;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= '<br><br>';
    $str .= $this->reporter->col('Grand Total: ' . number_format($grandtotal, 2), '600', null, false, $border, '', 'R', $font, '10', 'B', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function reportdatacsv($config)
  {
    $companyid = $config['params']['companyid'];
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
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select head.docno as `Doc#`,head.dateid as Date,item.barcode as Barcode,item.itemname as `Item Description`,
            stock.rrqty as Quantity,stock.uom as UOM,(case when stock.rrqty > 0 then stock.isamt else stock.rrcost end) as Price,
            stock.loc as Location,stock.expiry as Expiry,stock.rem as Notes
          
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join client as source on source.clientid=head.whid
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by head.docno $sorting";

            break;
          case '1': // UNPOSTED
            $query = "select * 
            from (select head.docno as `Doc#`,head.dateid as Date,item.barcode as Barcode,item.itemname as `Item Description`,
            stock.rrqty as Quantity,stock.uom as UOM,(case when stock.rrqty > 0 then stock.isamt else stock.rrcost end) as Price,
            stock.loc as Location,stock.expiry as Expiry,stock.rem as Notes
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a  order by `Doc#` $sorting";
            break;
          case '2': // ALL
            $query = "select head.docno as `Doc#`,head.dateid as Date,item.barcode as Barcode,item.itemname as `Item Description`,
            stock.rrqty as Quantity,stock.uom as UOM,(case when stock.rrqty > 0 then stock.isamt else stock.rrcost end) as Price,
            stock.loc as Location,stock.expiry as Expiry,stock.rem as Notes
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select head.docno as `Doc#`,head.dateid as Date,item.barcode as Barcode,item.itemname as `Item Description`,
            stock.rrqty as Quantity,stock.uom as UOM,(case when stock.rrqty > 0 then stock.isamt else stock.rrcost end) as Price,
            stock.loc as Location,stock.expiry as Expiry,stock.rem as Notes
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter  order by `Doc#` $sorting ";
            break;
        }
        break;
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // POSTED
            $query = "select * 
            from ( select left(head.dateid, 10) as `DATE`,head.clientname as `CUSTOMER`,head.docno `DOCUMENT #`,
            format(sum(stock.ext),2) as `AMOUNT`, 'POSTED' `STATUS`
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join cntnum on cntnum.trno=head.trno
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            group by head.docno,head.clientname, head.dateid
          ) as g order by `DOCUMENT #` $sorting";
            break;
          case '1': // UNPOSTED
            $query = "select left(dateid, 10) as `DATE`, whdestination as `CUSTOMER`, docno as `DOCUMENT #`, sum(ext) as `AMOUNT`,status as `STATUS`
            from (select 'UNPOSTED' as status, head.docno,head.clientname as whdestination,
            head.dateid,stock.ext
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join cntnum on cntnum.trno=head.trno
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a 
            group by `DOCUMENT #`, `DATE`,`CUSTOMER`, `STATUS`
            order by `DOCUMENT #` $sorting";
            break;
          case '2': // ALL
            $query = "select docno as `DOCUMENT #`, left(dateid, 10) as `DATE`, status as `STATUS`, sum(ext) as `AMOUNT`, whdestination as `CUSTOMER`
            from ( select 'UNPOSTED' as status, head.docno,head.clientname as whdestination,
            head.dateid,stock.ext
            from lastock as stock
            left join lahead as head on head.trno=stock.trno
            left join cntnum on cntnum.trno=head.trno
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select 'POSTED' as status, head.docno,head.clientname as whdestination,
            head.dateid,stock.ext
            from glstock as stock
            left join glhead as head on head.trno=stock.trno
            left join cntnum on cntnum.trno=head.trno
            where head.doc='AJ' and date(head.dateid) between '$start' and '$end' $filter ) as a 
            group by `DOCUMENT #`, `DATE`,`CUSTOMER`, `STATUS`
            order by `DOCUMENT #` $sorting";
            break;
        }
        break;
    }
    // var_dump($query);
    $data = $this->coreFunctions->opentable($query);
    $status =  true;
    $msg = 'Generating CSV successfully';
    if (empty($data)) {
      $status =  false;
      $msg = 'No data Found';
    }
    return ['status' => $status, 'msg' => $msg, 'data' => $data, 'params' => $this->reportParams, 'name' => 'ItemList'];
  }
}//end class