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

class purchase_order_report
{
  public $modulename = 'Purchase Order Report';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname'];

    switch ($companyid) {
      case 8: //maxipro
        array_push($fields, 'dprojectname', 'subprojectname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'subprojectname.required', false);
        data_set($col1, 'subprojectname.readonly', false);
        data_set($col1, 'dprojectname.lookupclass', 'projectcode');
        break;
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'reportusers', 'dcentername', 'approved', 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'dcentername.required', true);
        break;
      case 16: //ati
        array_push($fields, 'reportusers', 'dcentername', 'approved', 'potype');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'potype.required', false);
        break;
      default:
        array_push($fields, 'reportusers', 'dcentername', 'approved');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'dcentername.required', true);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'wasupplier');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioreporttype', 'radioposttype'];

    switch ($companyid) {
      case 8: //maxipro
        break;
      case 16: //ati
        array_push($fields, 'radioisassettag', 'radiosorting');
        break;
      default:
        array_push($fields, 'radiosorting');
        break;
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

    switch ($companyid) {
      case 11: //summit
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summary Per Document', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'teal'],
            ['label' => 'Summarized Per Item', 'value' => '2', 'color' => 'teal']

          ]
        );
        break;
      case 16: //ati
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
            ['label' => 'Posted PO - Is Asset', 'value' => '2', 'color' => 'orange'],
            ['label' => 'Detailed - Listing', 'value' => '3', 'color' => 'orange']
          ]
        );
        break;
    }


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

    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,
                 '' as client,'' as clientname,'0' as posttype,'0' as reporttype,'' as dclientname,'0' as clientid,
                 '' as potype,'" . $defaultcenter[0]['center'] . "' as center,
                '" . $defaultcenter[0]['centername'] . "' as centername,
                '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                '' as dprojectname, '' as projectname, '' as projectcode,'' as subprojectname,
                'ASC' as sorting,'' as userid,'' as username,'' as approved,'' as reportusers,
                '' as project, '' as projectid,'' as ddeptname,'' as dept, '' as deptname,
                '0' as isassettag";

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
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0: // summarized
        switch ($companyid) {
          case 24: // goodfound
            $result = $this->gfc_summarized_layout($config);
            break;
          case 8: //maxipro
            $result = $this->reportmaxiproLayout_SUMMARIZED($config);
            break;
          case 16: //ati
            $result = $this->reportDefaultLayout_ATI_SUMMARIZED($config);
            break;
          default:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
        }

        break;

      case 1: // detailed
        switch ($companyid) {
          case 8: // maxipro
            $result = $this->reportmaxiproLayout_DETAILED($config);
            break;

          case 24: // goodfound
            $result = $this->gfc_detailed_layout($config);
            break;

          case 19: // housegem
            $result = $this->housegem_detailed_layout($config);
            break;

          case 16: //ati
            $result = $this->reportDefaultLayout_ATI_DETAILED($config);
            break;
          // case 3://separate layout for conti, make it one continuous report instead 
          //   break;
          case 36: //rozlab
          case 27: //nte
            $result = $this->rozlabnte_DETAILED_layout($config);
            break;
          default: // default
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        } // end switch
        break;

      case 2:
        switch ($companyid) {
          case 16: //ati
            $result = $this->reportPostedPO_ISASSET($config);
            break;

          default:
            $result = $this->reportDefaultLayout_SUMMARYPERITEM($config);
            break;
        }
        break;
      case 3: //FOR ATI
        $result = $this->reportDetailedListing($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    switch ($reporttype) {
      case 3: // FOR ATI
        $query = $this->DETAILEDLISTING_QUERY($config);
        break;
      case 2:
        switch ($companyid) {
          case 16: //ati
            $query = $this->POPOSTEDISASSET_QUERY($config);
            break;

          default:
            $query = $this->SUMMIT_QUERY($config);
            break;
        }

        break;
      default:
        switch ($companyid) {
          case 8: //maxipro
            $query = $this->maxipro_QUERY($config);
            break;

          case 19: //housegem
            $query = $this->housegem_QUERY($config);
            break;

          case 24: //goodfound
            $query = $this->gfc_QUERY($config);
            break;
          case 16:
            $query = $this->ati_QUERY($config);
            break;
          default:
            $query = $this->default_QUERY($config);
            break;
        }

        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function ati_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $ptype    = $config['params']['dataparams']['potype'];

    $filter = "";
    $filter1 = "";
    $addedfield = ""; //summary
    $addedfield2 = ""; //detailed

    $addgrp = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $clientid     = $config['params']['dataparams']['clientid'];
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $brgrp = ",item.barcode,item.itemname";

    if ($reporttype == 1) {
      $addgrp .= ",info.ctrlno,info.requestorname,stock.cdrefx,stock.cdlinex";
    } else {
      $addgrp .= ",info.ctrlno,cat.category";
    }

    if ($ptype != "") {
      $ptype    = " and head.ourref = '$ptype'";
    } else {
      $ptype = "";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                            head.ourref,head.createby, left(head.dateid,10) as dateid,
                            head.yourref,
                            group_concat(distinct prh.docno separator ', ') as prdocno,
                            group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                            ifnull(cat.category,'') as categoryname
                      from hpostock as stock
                      left join hpohead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as supp on supp.client = head.client
                      left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                      left join hprhead as prh on prh.trno=prs.trno
                      left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                      left join reqcategory as cat on cat.line=prh.ourref
                      where head.doc='PO'  and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                      group by head.docno, head.clientname,head.createby, head.dateid, head.yourref,head.ourref ,
                              info.ctrlno,cat.category
                      order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              head.ourref,head.createby, left(head.dateid,10) as dateid,head.yourref,
                              group_concat(distinct prh.docno separator ', ') as prdocno,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              ifnull(cat.category,'') as categoryname
                      from postock as stock
                      left join pohead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as supp on supp.client = head.client
                      left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                      left join hprhead as prh on prh.trno=prs.trno
                      left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                      left join reqcategory as cat on cat.line=prh.ourref
                      where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                      group by head.docno, head.clientname,head.createby, head.dateid,head.yourref,head.ourref ,
                               info.ctrlno,cat.category
                      order by docno $sorting";
            break;

          default: // all
            $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                            head.ourref,head.createby, left(head.dateid,10) as dateid,
                            head.yourref,
                            group_concat(distinct prh.docno separator ', ') as prdocno,
                            group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                            ifnull(cat.category,'') as categoryname
                      from hpostock as stock
                      left join hpohead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as supp on supp.client = head.client
                      left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                      left join hprhead as prh on prh.trno=prs.trno
                      left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                      left join reqcategory as cat on cat.line=prh.ourref
                      where head.doc='PO'  and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                      group by head.docno, head.clientname,head.createby, head.dateid, head.yourref,head.ourref ,
                              info.ctrlno,cat.category
                      union all
                      select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                              head.ourref,head.createby, left(head.dateid,10) as dateid,head.yourref,
                              group_concat(distinct prh.docno separator ', ') as prdocno,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              ifnull(cat.category,'') as categoryname
                      from postock as stock
                      left join pohead as head on head.trno=stock.trno
                      left join transnum on transnum.trno=head.trno
                      left join client as supp on supp.client = head.client
                      left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                      left join hprhead as prh on prh.trno=prs.trno
                      left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                      left join reqcategory as cat on cat.line=prh.ourref
                      where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                      group by head.docno, head.clientname,head.createby, head.dateid,head.yourref,head.ourref ,
                               info.ctrlno,cat.category
                      order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select head.docno,head.clientname as supplier ,item.barcode,item.itemname,
                            stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                            wh.clientname,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                            head.yourref,  group_concat(distinct prh.docno separator ', ') as prdocno,
                            group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                            ifnull(info.requestorname,'') as requestorname,
                            ifnull((select group_concat(distinct dept.clientname) from client as dept
                            left join hcdstock as cd on cd.deptid=dept.clientid
                            where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department
                      from hpostock as stock
                      left join hpohead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.clientid=stock.whid
                      left join client as supp on supp.client = head.client
                      left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                      left join hprhead as prh on prh.trno=prs.trno
                      left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                      where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                      group by head.docno,head.clientname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                              wh.clientname,stock.rem,head.dateid,stock.ref,
                              head.yourref " . $brgrp . " $addgrp 
                      order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier ,item.barcode,item.itemname,
                              stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                              wh.clientname,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                              head.yourref, group_concat(distinct prh.docno separator ', ') as prdocno,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              ifnull(info.requestorname,'') as requestorname,
                              ifnull((select group_concat(distinct dept.clientname)
                              from client as dept left join hcdstock as cd on cd.deptid=dept.clientid
                              where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department
                        from postock as stock
                        left join pohead as head on head.trno=stock.trno
                        left join item on item.itemid=stock.itemid
                        left join transnum on transnum.trno=head.trno
                        left join client as wh on wh.clientid=stock.whid
                        left join client as supp on supp.client = head.client
                        left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                        left join hprhead as prh on prh.trno=prs.trno
                        left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                        where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                        group by head.docno,head.clientname,
                                stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                                wh.clientname,stock.rem,head.dateid,stock.ref,
                                head.yourref " . $brgrp . " $addgrp 
                        order by docno $sorting";
            break;
          default: // all
            $query = "select head.docno,head.clientname as supplier ,item.barcode,item.itemname,
                            stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                            wh.clientname,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                            head.yourref,  group_concat(distinct prh.docno separator ', ') as prdocno,
                            group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                            ifnull(info.requestorname,'') as requestorname,
                            ifnull((select group_concat(distinct dept.clientname) from client as dept
                            left join hcdstock as cd on cd.deptid=dept.clientid
                            where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department
                      from hpostock as stock
                      left join hpohead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client as wh on wh.clientid=stock.whid
                      left join client as supp on supp.client = head.client
                      left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                      left join hprhead as prh on prh.trno=prs.trno
                      left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                      where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                      group by head.docno,head.clientname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                              wh.clientname,stock.rem,head.dateid,stock.ref,
                              head.yourref " . $brgrp . " $addgrp 
                      union all
                      select head.docno,head.clientname as supplier ,item.barcode,item.itemname,
                              stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                              wh.clientname,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                              head.yourref, group_concat(distinct prh.docno separator ', ') as prdocno,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              ifnull(info.requestorname,'') as requestorname,
                              ifnull((select group_concat(distinct dept.clientname)
                              from client as dept left join hcdstock as cd on cd.deptid=dept.clientid
                              where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department
                        from postock as stock
                        left join pohead as head on head.trno=stock.trno
                        left join item on item.itemid=stock.itemid
                        left join transnum on transnum.trno=head.trno
                        left join client as wh on wh.clientid=stock.whid
                        left join client as supp on supp.client = head.client
                        left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                        left join hprhead as prh on prh.trno=prs.trno
                        left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
                        where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
                        group by head.docno,head.clientname,
                                stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                                wh.clientname,stock.rem,head.dateid,stock.ref,
                                head.yourref " . $brgrp . " $addgrp 
                        order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }

  public function default_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $ptype    = $config['params']['dataparams']['potype'];

    $filter = "";
    $filter1 = "";
    $addedfield = ""; //summary
    $addedfield2 = ""; //detailed
    $addgrp = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjname = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $deptcode = $config['params']['dataparams']['dept'];
      $projectid = $config['params']['dataparams']['projectid'];

      if ($prjname != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptcode != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
      $brgrp = ",item.partno,model.model_name,brand.brand_desc,i.itemdescription";
    } else {
      $filter1 .= "";
      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $brgrp = ",item.barcode,item.itemname";
      $addjoin = "";
    }
    if ($companyid == 16) { //ati
      $addedfield .= ", group_concat(distinct prh.docno separator ', ') as prdocno, 
                         group_concat(distinct info.ctrlno separator ', ') as ctrlno, ifnull(cat.category,'') as categoryname";
      $addedfield2 .= ", group_concat(distinct prh.docno separator ', ') as prdocno, 
                         group_concat(distinct info.ctrlno separator ', ') as ctrlno, ifnull(cat.category,'') as categoryname,
                         ifnull(info.requestorname,'') as requestorname,ifnull((select group_concat(distinct dept.clientname)
                                from client as dept left join hcdstock as cd on cd.deptid=dept.clientid
                                where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department";
      $addjoin .= " left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline 
                    left join hprhead as prh on prh.trno=prs.trno 
                    left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line 
                    left join reqcategory as cat on cat.line=prh.ourref ";
      if ($reporttype == 1) {
        $addgrp .= ",info.ctrlno,cat.category,info.requestorname,stock.cdrefx,stock.cdlinex";
      } else {
        $addgrp .= ",info.ctrlno,cat.category";
      }
    }
    if ($ptype != "") {
      $ptype    = " and head.ourref = '$ptype'";
    } else {
      $ptype = "";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,head.ourref,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode,head.yourref, dept.clientname as deptname " . $addedfield . "
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.client = head.wh
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO'  and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref,head.ourref " . $addgrp . "
          order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,head.ourref,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode,head.yourref, dept.clientname as deptname " . $addedfield . "
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref,head.ourref " . $addgrp . "
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,head.ourref,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode,head.yourref, dept.clientname as deptname " . $addedfield . "
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname ,head.yourref,head.ourref " . $addgrp . "
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,head.ourref,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode,head.yourref, dept.clientname as deptname " . $addedfield . "
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref,head.ourref " . $addgrp . "
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
            select head.docno,head.clientname as supplier " . $barcodeitemnamefield . ",
                            stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                            client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                            dept.client as deptcode,head.yourref, dept.clientname as deptname,head.terms " . $addedfield2 . "
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype 
          group by head.docno,head.clientname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client,head.yourref, dept.clientname,head.terms " . $brgrp . " $addgrp 
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "
            select head.docno,head.clientname as supplier " . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode,head.yourref, dept.clientname as deptname,head.terms " . $addedfield2 . "
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
          group by head.docno,head.clientname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client,head.yourref, dept.clientname,head.terms " . $brgrp . " $addgrp 
          order by docno $sorting";
            break;
          default: // all
            $query = "
          select head.docno,head.clientname as supplier " . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode,head.yourref, dept.clientname as deptname,head.terms " . $addedfield2 . "
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype  
          group by head.docno,head.clientname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client,head.yourref, dept.clientname,head.terms " . $brgrp . " $addgrp 
          
          union all

         select  head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode,head.yourref, dept.clientname as deptname,head.terms " . $addedfield2 . "
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1 $ptype
          group by head.docno,head.clientname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
          dept.client,head.yourref, dept.clientname,head.terms " . $brgrp . " $addgrp 
           order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }

  public function maxipro_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $project = $config['params']['dataparams']['dprojectname'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    if ($project != "") {
      $projectid = $config['params']['dataparams']['projectid'];
    }
    if ($subprojectname != "") {
      $subproject = $config['params']['dataparams']['subproject'];
    }

    $reporttype = $config['params']['dataparams']['reporttype'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";

    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }
    if ($subprojectname != "") {
      $filter .= " and head.subproject = '" . $subproject . "' ";
    }

    $filter1 .= "";

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,
          ifnull((select sum(ext) from (
                  select ((rrqty - qa) * rrcost) as ext,p.trno from hpohead as p left join hpostock as s on s.trno=p.trno
                  where p.doc='PO' and s.void <> 0) as a where a.trno=head.trno),0) as voidamt,
          ifnull((select sum(ext) from (
                  select s.ext,s.refx from lahead as h left join lastock as s on s.trno=h.trno
                  where h.doc='RR'
                  union all
                  select s.ext,s.refx from glhead as h left join glstock as s on s.trno=h.trno
                  where h.doc='RR') as a where a.refx=head.trno),0) as rramt,
          ifnull((select sum(rrqty) from (
                  select s.rrqty,s.refx from lahead as h left join lastock as s on s.trno=h.trno
                  where h.doc='RR'
                  union all
                  select s.rrqty,s.refx from glhead as h left join glstock as s on s.trno=h.trno
                  where h.doc='RR' ) as a where a.refx=head.trno),0) as rrctr,
          sum(stock.rrqty) as postockctr,
          ifnull((select group_concat(distinct docno SEPARATOR '\r') from (
                     select h.docno,d.refx,rrs.refx as rrrefx
                     from lahead as h
                     left join ladetail as d on d.trno=h.trno
                     left join glhead as rrh on rrh.trno=d.refx
                     left join glstock as rrs on d.refx=rrs.trno and rrs.trno
                     where h.doc='CV' and d.refx <> 0
                     group by h.docno,d.refx,rrs.refx
                     union all
                     select h.docno,d.refx,rrs.refx as rrrefx from glhead as h left join gldetail as d on d.trno=h.trno
                     left join glhead as rrh on rrh.trno=d.refx
                     left join glstock as rrs on d.refx=rrs.trno
                     where h.doc='CV' and d.refx <> 0
                     group by h.docno,d.refx,rrs.refx
          )as a where a.rrrefx=head.trno),'') as cvdocno,
          ifnull((select sum(ext) from (
                  select rrs.ext,d.refx,rrs.refx as rrrefx from lahead as h left join ladetail as d on d.trno=h.trno
                  left join glhead as rrh on rrh.trno=d.refx
                  left join glstock as rrs on d.refx=rrs.trno and rrs.trno
                  where h.doc='CV' and d.refx <> 0
                  group by d.refx,rrs.refx,rrs.ext
                  union all
                  select rrs.ext,d.refx,rrs.refx as rrrefx from glhead as h left join gldetail as d on d.trno=h.trno
                  left join glhead as rrh on rrh.trno=d.refx
                  left join glstock as rrs on d.refx=rrs.trno
                  where h.doc='CV' and d.refx <> 0
                  group by d.refx,rrs.refx,rrs.ext
                  ) as a where a.rrrefx=head.trno ),0) as billingamt,stat.status  as postatus,head.rem
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.client = head.wh
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join trxstatus as stat on stat.line=transnum.statid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.trno,head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,stat.status,head.rem
          order by docno ";


            break;
          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,
          ifnull((select sum(ext) from (
                  select ((rrqty - qa) * rrcost) as ext,p.trno from pohead as p left join postock as s on s.trno=p.trno
                  where p.doc='PO' and s.void <> 0) as a where a.trno=head.trno),0) as voidamt,
          0 as rramt,0 as rrctr,sum(stock.rrqty) as postockctr,'' as cvdocno,0 as billingamt,stat.status as postatus,head.rem
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          left join trxstatus as stat on stat.line=transnum.statid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.trno,head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,stat.status,head.rem
          order by docno ";
            break;

          default: // all
            $query = "select 'UNPOSTEDs' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,
          ifnull((select sum(ext) from (
                  select ((rrqty - qa) * rrcost) as ext,p.trno from pohead as p left join postock as s on s.trno=p.trno
                  where p.doc='PO' and s.void <> 0) as a where a.trno=head.trno),0) as voidamt,
          0 as rramt,0 as rrctr,sum(stock.rrqty) as postockctr,'' as cvdocno,0 as billingamt,stat.status as postatus,head.rem
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          left join trxstatus as stat on stat.line=transnum.statid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.trno,head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,stat.status,head.rem
          union all
          select 'POSTEDs' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,
          ifnull((select sum(ext) from (
                  select ((rrqty - qa) * rrcost) as ext,p.trno from hpohead as p left join hpostock as s on s.trno=p.trno
                  where p.doc='PO' and s.void <> 0) as a where a.trno=head.trno),0) as voidamt,
          ifnull((select sum(ext) from (
                  select s.ext,s.refx from lahead as h left join lastock as s on s.trno=h.trno
                  where h.doc='RR'
                  union all
                  select s.ext,s.refx from glhead as h left join glstock as s on s.trno=h.trno
                  where h.doc='RR') as a where a.refx=head.trno),0) as rramt,
          ifnull((select sum(rrqty) from (
                  select s.rrqty,s.refx from lahead as h left join lastock as s on s.trno=h.trno
                  where h.doc='RR'
                  union all
                  select s.rrqty,s.refx from glhead as h left join glstock as s on s.trno=h.trno
                  where h.doc='RR' ) as a where a.refx=head.trno),0) as rrctr,
          sum(stock.rrqty) as postockctr,
          ifnull((select group_concat(distinct docno SEPARATOR '\r') from (
                     select h.docno,d.refx,rrs.refx as rrrefx
                     from lahead as h
                     left join ladetail as d on d.trno=h.trno
                     left join glhead as rrh on rrh.trno=d.refx
                     left join glstock as rrs on d.refx=rrs.trno and rrs.trno
                     where h.doc='CV' and d.refx <> 0
                     group by h.docno,d.refx,rrs.refx
                     union all
                     select h.docno,d.refx,rrs.refx as rrrefx from glhead as h left join gldetail as d on d.trno=h.trno
                     left join glhead as rrh on rrh.trno=d.refx
                     left join glstock as rrs on d.refx=rrs.trno
                     where h.doc='CV' and d.refx <> 0
                     group by h.docno,d.refx,rrs.refx
          )as a where a.rrrefx=head.trno),'') as cvdocno,
          ifnull((select sum(ext) from (
                  select rrs.ext,d.refx,rrs.refx as rrrefx from lahead as h left join ladetail as d on d.trno=h.trno
                  left join glhead as rrh on rrh.trno=d.refx
                  left join glstock as rrs on d.refx=rrs.trno and rrs.trno
                  where h.doc='CV' and d.refx <> 0
                  group by d.refx,rrs.refx,rrs.ext
                  union all
                  select rrs.ext,d.refx,rrs.refx as rrrefx from glhead as h left join gldetail as d on d.trno=h.trno
                  left join glhead as rrh on rrh.trno=d.refx
                  left join glstock as rrs on d.refx=rrs.trno
                  where h.doc='CV' and d.refx <> 0
                  group by d.refx,rrs.refx,rrs.ext
                  ) as a where a.rrrefx=head.trno ),0) as billingamt,stat.status  as postatus,head.rem
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          left join trxstatus as stat on stat.line=transnum.statid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.trno,head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,stat.status,head.rem
          order by docno ";
            break;
        } // end switch posttype
        break;
      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select trno,podate,docno,transtype,supplier,prdate,prdocno,rrdocno,barcode,
                                   itemname,poqty,poamt,pototal,rrdate,rrqty,rramt,
                            postat, projcode,projname,subproject,porem ,client,voidqty from (
                            select head.trno,left(head.dateid,10) as podate,head.docno,
                                  'PURCHASE ORDER' as transtype,head.clientname as supplier,
                                  left(pr.dateid,10) as prdate,head.yourref as prdocno,
                                  (select group_concat(distinct docno SEPARATOR '\r') from (
                                  select rr.docno,rrs.refx,rrs.itemid,rrs.linex from lahead as rr
                                  left join lastock as rrs on rrs.trno=rr.trno
                                  where rr.doc='RR'
                                  union all
                                  select rr.docno,rrs.refx,rrs.itemid,rrs.linex from glhead as rr
                                  left join glstock as rrs on rrs.trno=rr.trno
                                  where rr.doc='RR'
                                  )as a where refx =stock.trno and linex=stock.line) as rrdocno,
                                  item.barcode, item.itemname,stock.rrqty as poqty,stock.rrcost as poamt,stock.ext as pototal,       
                                  (select group_concat(distinct dateid separator '\r') 
                                  from (select left(rr.dateid,10) as dateid,rrs.refx,rrs.itemid,rrs.linex
                                       from lahead as rr left join lastock as rrs on rrs.trno=rr.trno where rr.doc='RR'
                                       union all
                                       select left(rr.dateid,10) as dateid,rrs.refx,rrs.itemid,rrs.linex
                                       from glhead as rr left join glstock as rrs on rrs.trno=rr.trno where rr.doc='RR')as a
                                  where refx =stock.trno and linex=stock.line) as rrdate,
                                  (select format(sum(rrqty),2) as rrqty 
                                  from (select rrs.rrqty,rrs.refx,rrs.itemid,rrs.linex 
                                        from lahead as rr left join lastock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR'
                                        union all
                                        select rrs.rrqty,rrs.refx,rrs.itemid,rrs.linex from glhead as rr
                                        left join glstock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR') as a
                                        where refx = stock.trno and linex=stock.line) as rrqty,
                                  (select group_concat(distinct format(rrcost,2) separator ',') as rrcost 
                                  from (select rrs.rrcost,rrs.refx,rrs.itemid,rrs.linex from lahead as rr
                                        left join lastock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR'
                                        union all
                                        select rrs.rrcost,rrs.refx,rrs.itemid,rrs.linex from glhead as rr
                                        left join glstock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR') as a
                                        where refx = stock.trno and linex=stock.line) as rramt,
                                  stat.status as postat,proj.code as projcode, 
                                  proj.name as projname,subproj.subproject,head.rem as porem,head.client,
                                   ifnull((select sum(a.rrqty-a.qa) from (
                            select s.rrqty,h.trno,s.itemid,s.qa from hpohead as h left join hpostock as s on s.trno=h.trno
                            where s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                            from hpostock as stock
                            left join hpohead as head on head.trno=stock.trno
                            left join client as supp on supp.client=head.client
                            left join hprhead as pr on pr.docno=head.yourref
                            left join item on item.itemid =stock.itemid
                            left join transnum as num on num.trno=head.trno
                            left join trxstatus as stat on stat.line=num.statid
                            left join projectmasterfile as proj on proj.line=head.projectid
                            left join subproject as subproj on subproj.line = head.subproject 
                                  and subproj.projectid=proj.line
                             where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1 ) as k
                             order by podate,supplier,projname,subproject";
            break;

          case 1: // unposted
            $query = "select head.trno,left(head.dateid,10) as podate,head.docno,
                                   'PURCHASE ORDER' as transtype,head.clientname as supplier,
                                   pr.dateid as prdate,head.yourref as prdocno,'' as rrdocno,
                                   item.barcode, item.itemname,stock.rrqty as poqty,stock.rrcost as poamt,
                                   stock.ext as pototal,'' as rrdate, '' as rrqty, '' as rramt, 
                                   'Pending' as postat,proj.code as projcode, proj.name as projname,
                                   subproj.subproject,head.rem as porem,head.client ,
                                   ifnull((select sum(a.rrqty-a.qa) from (
                            select s.rrqty,h.trno,s.itemid,s.qa from pohead as h left join postock as s on s.trno=h.trno
                            where s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                            from postock as stock
                            left join pohead as head on head.trno=stock.trno
                            left join client as supp on supp.client=head.client
                            left join hprhead as pr on pr.docno=head.yourref
                            left join item on item.itemid =stock.itemid
                            left join transnum as num on num.trno=head.trno
                            left join trxstatus as stat on stat.line=num.statid
                            left join projectmasterfile as proj on proj.line=head.projectid
                            left join subproject as subproj on subproj.line = head.subproject and subproj.projectid=proj.line
                            where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
                      order by head.dateid,head.clientname,proj.name,subproj.subproject";
            break;

          default: // all
            $query = "select trno,podate,docno,transtype,supplier,prdate,prdocno,rrdocno,
                            barcode,itemname,poqty,poamt,pototal,rrdate,rrqty,rramt,
                            postat,projcode,projname,subproject,porem ,voidqty
                      from (select head.trno,left(head.dateid,10) as podate,head.docno,
                                   'PURCHASE ORDER' as transtype,head.clientname as supplier,
                                   left(pr.dateid,10) as prdate,head.yourref as prdocno,'' as rrdocno,
                                   item.barcode, item.itemname,stock.rrqty as poqty,stock.rrcost as poamt,
                                   stock.ext as pototal,'' as rrdate, '' as rrqty, '' as rramt, 
                                   'Pending' as postat,proj.code as projcode, proj.name as projname,
                                   subproj.subproject,head.rem as porem,head.client ,
                                   ifnull((select sum(a.rrqty-a.qa) from (
                            select s.rrqty,h.trno,s.itemid,s.qa from pohead as h left join postock as s on s.trno=h.trno
                            where s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                            from postock as stock
                            left join pohead as head on head.trno=stock.trno
                            left join client as supp on supp.client=head.client
                            left join hprhead as pr on pr.docno=head.yourref
                            left join item on item.itemid =stock.itemid
                            left join transnum as num on num.trno=head.trno
                            left join trxstatus as stat on stat.line=num.statid
                            left join projectmasterfile as proj on proj.line=head.projectid
                            left join subproject as subproj on subproj.line = head.subproject and subproj.projectid=proj.line
                            where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
                            union all
                            select trno,podate,docno,transtype,supplier,prdate,prdocno,rrdocno,barcode,
                                   itemname,poqty,poamt,pototal,rrdate,rrqty,rramt,
                            postat, projcode,projname,subproject,porem ,client,voidqty from (
                            select head.trno,left(head.dateid,10) as podate,head.docno,
                                  'PURCHASE ORDER' as transtype,head.clientname as supplier,
                                  left(pr.dateid,10) as prdate,head.yourref as prdocno,
                                  (select group_concat(distinct docno SEPARATOR '\r') from (
                                  select rr.docno,rrs.refx,rrs.itemid,rrs.linex from lahead as rr
                                  left join lastock as rrs on rrs.trno=rr.trno
                                  where rr.doc='RR'
                                  union all
                                  select rr.docno,rrs.refx,rrs.itemid,rrs.linex from glhead as rr
                                  left join glstock as rrs on rrs.trno=rr.trno
                                  where rr.doc='RR'
                                  )as a where refx =stock.trno and linex = stock.line) as rrdocno,
                                  item.barcode, item.itemname,stock.rrqty as poqty,stock.rrcost as poamt,stock.ext as pototal,       
                                  (select group_concat(distinct dateid separator '\r') 
                                  from (select left(rr.dateid,10) as dateid,rrs.refx,rrs.itemid,rrs.linex
                                       from lahead as rr left join lastock as rrs on rrs.trno=rr.trno where rr.doc='RR'
                                       union all
                                       select left(rr.dateid,10) as dateid,rrs.refx,rrs.itemid ,rrs.linex
                                       from glhead as rr left join glstock as rrs on rrs.trno=rr.trno where rr.doc='RR')as a
                                  where refx =stock.trno and linex=stock.line) as rrdate,
                                  (select format(sum(rrqty),2) as rrqty 
                                  from (select rrs.rrqty,rrs.refx,rrs.itemid,rrs.linex
                                        from lahead as rr left join lastock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR'
                                        union all
                                        select rrs.rrqty,rrs.refx,rrs.itemid,rrs.linex from glhead as rr
                                        left join glstock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR') as a
                                        where refx = stock.trno and linex=stock.line) as rrqty,
                                  (select group_concat(distinct format(rrcost,2) separator ',') as rrcost 
                                  from (select rrs.rrcost,rrs.refx,rrs.itemid,rrs.linex from lahead as rr
                                        left join lastock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR'
                                        union all
                                        select rrs.rrcost,rrs.refx,rrs.itemid,rrs.linex from glhead as rr
                                        left join glstock as rrs on rrs.trno=rr.trno
                                        where rr.doc='RR') as a
                                        where refx = stock.trno and linex=stock.line) as rramt,
                                  stat.status as postat,proj.code as projcode, 
                                  proj.name as projname,subproj.subproject,head.rem as porem,head.client,
                                  ifnull((select sum(a.rrqty-a.qa) from (
                            select s.rrqty,h.trno,s.itemid,s.qa from hpohead as h left join hpostock as s on s.trno=h.trno
                            where s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                            from hpostock as stock
                            left join hpohead as head on head.trno=stock.trno
                            left join client as supp on supp.client=head.client
                            left join hprhead as pr on pr.docno=head.yourref
                            left join item on item.itemid =stock.itemid
                            left join transnum as num on num.trno=head.trno
                            left join trxstatus as stat on stat.line=num.statid
                            left join projectmasterfile as proj on proj.line=head.projectid
                            left join subproject as subproj on subproj.line = head.subproject 
                                  and subproj.projectid=proj.line
                             where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1 ) as k ) as a
                             order by podate,supplier,projname,subproject";
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
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='PO'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting
          ";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='PO'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
                    sum(stock.ext) as ext
                    from postock as stock
                    left join pohead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join transnum on transnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.client = head.wh
                    where head.doc='PO'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    UNION ALL
                    select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
                    sum(stock.ext) as ext
                    from hpostock as stock
                    left join hpohead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join transnum on transnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.client = head.wh
                    where head.doc='PO'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    order by clientname,itemname $sorting";
        break;
    } // end switch posttype

    return $query;
  }

  public function POPOSTEDISASSET_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $ptype    = $config['params']['dataparams']['potype'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($ptype != "") {
      $filter    .= " and head.ourref = '$ptype'";
    } else {
      $filter .= "";
    }

    $query = "select a.docno,a.supplier,a.dateid,a.barcode,a.rrqty,a.rrcost,
                    group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                    ifnull(cat.category,'') as categoryname,info.itemdesc,info.specs,
                    ifnull(info.requestorname,'') as requestorname,group_concat(distinct prh.docno separator ', ') as prdocno,
                    reqdept.clientname as deptname,
                    cast(SUBSTRING_INDEX(info.ctrlno, '-', 1) as unsigned ) as ctrlnodoc,
                    cast(SUBSTRING_INDEX(info.ctrlno, '-', -1) as unsigned ) as ctrlnoline,
                    ifnull((select group_concat(distinct stat) as stat
                             from (select 'Draft' as stat,s.reqtrno,s.reqline from lastock as s
                                   left join lahead as h on h.trno=s.trno where h.doc='RR' and s.void=0
                                   union all
                                   select 'Draft - Void' as stat,s.reqtrno,s.reqline from lastock as s
                                   left join lahead as h on h.trno=s.trno where h.doc='RR' and s.void=1
                                   union all
                                   select 'Posted' as stat,s.reqtrno,s.reqline from glstock as s
                                   left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno
                                   where h.doc='RR' and s.void=0
                                   union all
                                   select 'Posted - Void' as stat,s.reqtrno,s.reqline from glstock as s
                                   left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno
                                   where h.doc='RR' and s.void=1) as stat
                            where stat.reqtrno=a.reqtrno and stat.reqline=a.reqline),'') as rrstat,

                    ifnull((select group_concat(distinct postdate) as postdate
                            from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                                  from glstock as s left join glhead as h on h.trno=s.trno
                                  left join cntnum as num on num.trno=h.trno where h.doc='RR') as pd
                            where pd.reqtrno=a.reqtrno and pd.reqline=a.reqline),'') as rrpostdate,

                    ifnull((select group_concat(distinct wh) as wh
                            from (select wh.clientname as wh,s.reqtrno,s.reqline
                                  from lastock as s left join lahead as h on h.trno=s.trno
                                  left join client as wh on wh.clientid=s.whid where h.doc='RR'
                                  union all
                                  select wh.clientname as wh,s.reqtrno,s.reqline
                                  from glstock as s left join glhead as h on h.trno=s.trno
                                  left join client as wh on wh.clientid=s.whid where h.doc='RR') as wh
                            where wh.reqtrno=a.reqtrno and wh.reqline=a.reqline),'') as rrwh,

                    ifnull((select group_concat(distinct stat) as stat
                            from (select 'Draft' as stat,rr.reqtrno,rr.reqline from ladetail as s 
                                  left join lahead as h on h.trno=s.trno left join glstock as rr on rr.trno=s.refx
                                  union all
                                  select 'Posted' as stat,rr.reqtrno,rr.reqline from gldetail as s
                                  left join glhead as h on h.trno=s.trno left join cntnum as num on num.trno=h.trno
                                  left join glstock as rr on rr.trno=s.refx
                                  union all
                                  select 'Draft' as stat,po.reqtrno,po.reqline from cvitems as cv
                                  left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                                  left join lahead as h on h.trno=cv.trno
                                  left join ladetail as d on d.trno=h.trno and d.line=cv.line
                                  left join cntnum as c on c.trno=h.trno where h.trno is not null
                                  union all
                                  select 'Posted' as stat,po.reqtrno,po.reqline from hcvitems as cv
                                  left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                                  left join glhead as h on h.trno=cv.trno
                                  left join gldetail as d on d.trno=h.trno and d.line=cv.line
                                  left join cntnum as c on c.trno=h.trno
                                        where h.trno is not null) as stat
                                  where stat.reqtrno=a.reqtrno and stat.reqline=a.reqline),'') as cvstat
              from (select stock.reqtrno,stock.reqline,head.docno,head.clientname as supplier,
                          date(head.dateid) as dateid,item.barcode,stock.rrqty,stock.rrcost
                    from hpostock as stock
                    left join hpohead as head on head.trno=stock.trno
                    left join transnum on transnum.trno=head.trno
                    left join client as supp on supp.client = head.client
                    left join item on item.itemid=stock.itemid
                    where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter) as a
              left join hprstock as prs on prs.trno=a.reqtrno and prs.line=a.reqline
              left join hprhead as prh on prh.trno=prs.trno
              left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line
              left join reqcategory as cat on cat.line=prh.ourref
              left join hstockinfotrans as xinfo on xinfo.trno=a.reqtrno and xinfo.line=a.reqline
              left join client as creq on creq.clientname=xinfo.requestorname
              left join client as reqdept on reqdept.clientid=creq.deptid
              where  info.isasset='YES'
              group by docno,supplier,dateid,barcode,cat.category,info.itemdesc,rrqty,rrcost,info.specs,
                       info.requestorname,reqdept.clientname,a.reqtrno,a.reqline,info.ctrlno
              order by docno,ctrlnodoc,ctrlnoline $sorting";

    return $query;
  }

  public function DETAILEDLISTING_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $ptype    = $config['params']['dataparams']['potype'];
    $isassettag   = $config['params']['dataparams']['isassettag'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($ptype != "") {
      $filter    .= " and head.ourref = '$ptype'";
    } else {
      $filter .= "";
    }
    switch ($isassettag) {
      case 1: //YES
        $filter .= " and info.isasset='YES'";
        break;
      case 2: //NO
        $filter .= " and info.isasset='NO'";
        break;
    }

    $addgrp = '';
    if ($reporttype == 1) {
      $addgrp .= ",info.ctrlno, cat.category,info.requestorname,stock.cdrefx,stock.cdlinex";
    } else {
      $addgrp .= ",info.ctrlno, cat.category";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "select stock.reqtrno,stock.reqline,head.docno,head.clientname as supplier ,item.barcode,item.itemname,info.itemdesc,
                        stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                        client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,
                        stock.ref,dept.client as deptcode,head.yourref, dept.clientname as deptname, 
                        group_concat(distinct prh.docno separator ', ') as prdocno,info.ctrlno,
                        ifnull(cat.category,'') as categoryname,ifnull(info.requestorname,'') as requestorname,
                        ifnull((select group_concat(distinct dept.clientname)
                                from client as dept 
                                left join hcdstock as cd on cd.deptid=dept.clientid
                                where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department,
                        ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Draft - Void' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=1
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Posted - Void' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=1) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as rrstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as rrpostdate,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='SS'
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS'
                       ) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as ssstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as sspostdate,

                      ifnull((select group_concat(distinct wh) as wh
                      from (select wh.clientname as wh,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR'
                            union all
                            select wh.clientname as wh,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR') as wh
                      where wh.reqtrno=stock.reqtrno and wh.reqline=stock.reqline),'') as rrwh,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,rr.reqtrno,rr.reqline
                            from ladetail as s
                            left join lahead as h on h.trno=s.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Posted' as stat,rr.reqtrno,rr.reqline
                            from gldetail as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Draft' as stat,po.reqtrno,po.reqline
                            from cvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join lahead as h on h.trno=cv.trno
                            left join ladetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null
                            union all
                            select 'Posted' as stat,po.reqtrno,po.reqline
                            from hcvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join glhead as h on h.trno=cv.trno
                            left join gldetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as cvstat,

            (case when info.isasset= '' then 'NO' else info.isasset end) as isasset,info.specs,
            reqdept.clientname as deptname,stock.void
                  from hpostock as stock
                  left join hpohead as head on head.trno=stock.trno
                  left join item on item.itemid=stock.itemid
                  left join transnum on transnum.trno=head.trno
                  left join client on client.clientid=stock.whid
                  left join client as supp on supp.client = head.client
                  left join client as dept on dept.clientid = head.deptid
                  left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline 
                  left join hprhead as prh on prh.trno=prs.trno 
                  left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line 
                  left join reqcategory as cat on cat.line=prh.ourref

                  left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                  left join client as creq on creq.clientname=xinfo.requestorname
                  left join client as reqdept on reqdept.clientid=creq.deptid

                  where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter
                  group by head.docno,head.clientname ,item.barcode,item.itemname ,stock.uom,stock.rrqty,
                          stock.rrcost,stock.disc,stock.ext,head.ourref,
                          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
                          dept.client,head.yourref, dept.clientname,info.itemdesc,info.requestorname,
                          stock.cdrefx,stock.cdlinex,stock.reqtrno,stock.reqline,info.isasset,info.specs,
                          reqdept.clientname,stock.void,stock.reqtrno,stock.reqline $addgrp
                  order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select stock.reqtrno,stock.reqline,head.docno,head.clientname as supplier ,item.barcode,item.itemname,info.itemdesc,
                        stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                        client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                        dept.client as deptcode,head.yourref, dept.clientname as deptname, 
                        group_concat(distinct prh.docno separator ', ') as prdocno, 
                        group_concat(distinct info.ctrlno separator ', ') as ctrlno, 
                        ifnull(cat.category,'') as categoryname,ifnull(info.requestorname,'') as requestorname,
                        ifnull((select group_concat(distinct dept.clientname)
                                from client as dept 
                                left join hcdstock as cd on cd.deptid=dept.clientid
                                where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department,
                        ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Draft - Void' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=1
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Posted - Void' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=1) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as rrstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as rrpostdate,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='SS'
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS'
                       ) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as ssstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as sspostdate,

                      ifnull((select group_concat(distinct wh) as wh
                      from (select wh.clientname as wh,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR'
                            union all
                            select wh.clientname as wh,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR') as wh
                      where wh.reqtrno=stock.reqtrno and wh.reqline=stock.reqline),'') as rrwh,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,rr.reqtrno,rr.reqline
                            from ladetail as s
                            left join lahead as h on h.trno=s.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Posted' as stat,rr.reqtrno,rr.reqline
                            from gldetail as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Draft' as stat,po.reqtrno,po.reqline
                            from cvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join lahead as h on h.trno=cv.trno
                            left join ladetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null
                            union all
                            select 'Posted' as stat,po.reqtrno,po.reqline
                            from hcvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join glhead as h on h.trno=cv.trno
                            left join gldetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as cvstat,

            (case when info.isasset= '' then 'NO' else info.isasset end) as isasset,info.specs,
            reqdept.clientname as deptname,stock.void
                  from postock as stock
                  left join pohead as head on head.trno=stock.trno
                  left join item on item.itemid=stock.itemid
                  left join transnum on transnum.trno=head.trno
                  left join client on client.clientid=stock.whid
                  left join client as supp on supp.client = head.client
                  left join client as dept on dept.clientid = head.deptid
                  left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline 
                  left join hprhead as prh on prh.trno=prs.trno 
                  left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line 
                  left join reqcategory as cat on cat.line=prh.ourref

                  left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                  left join client as creq on creq.clientname=xinfo.requestorname
                  left join client as reqdept on reqdept.clientid=creq.deptid

                  where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter
                  group by head.docno,head.clientname ,item.barcode,item.itemname,stock.uom,stock.rrqty,
                          stock.rrcost,stock.disc,stock.ext,head.ourref,client.clientname,head.createby,
                          stock.loc,stock.rem,head.dateid,stock.ref,
                          dept.client,head.yourref, dept.clientname,info.itemdesc,info.requestorname,
                          stock.cdrefx,stock.cdlinex,stock.reqtrno,stock.reqline,info.isasset,info.specs,
                          reqdept.clientname,stock.void,stock.reqtrno,stock.reqline $addgrp
                  order by docno $sorting";
        break;

      default: // all
        $query = "select stock.reqtrno,stock.reqline,head.docno,head.clientname as supplier ,item.barcode,item.itemname,info.itemdesc,
                        stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                        client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                        dept.client as deptcode,head.yourref, dept.clientname as deptname, 
                        group_concat(distinct prh.docno separator ', ') as prdocno, 
                        group_concat(distinct info.ctrlno separator ', ') as ctrlno, 
                        ifnull(cat.category,'') as categoryname,ifnull(info.requestorname,'') as requestorname,
                        ifnull((select group_concat(distinct dept.clientname)
                                from client as dept 
                                left join hcdstock as cd on cd.deptid=dept.clientid
                                where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department,
                        ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Draft - Void' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=1
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Posted - Void' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=1) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as rrstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as rrpostdate,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='SS'
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS'
                       ) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as ssstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as sspostdate,

                      ifnull((select group_concat(distinct wh) as wh
                      from (select wh.clientname as wh,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR'
                            union all
                            select wh.clientname as wh,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR') as wh
                      where wh.reqtrno=stock.reqtrno and wh.reqline=stock.reqline),'') as rrwh,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,rr.reqtrno,rr.reqline
                            from ladetail as s
                            left join lahead as h on h.trno=s.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Posted' as stat,rr.reqtrno,rr.reqline
                            from gldetail as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Draft' as stat,po.reqtrno,po.reqline
                            from cvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join lahead as h on h.trno=cv.trno
                            left join ladetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null
                            union all
                            select 'Posted' as stat,po.reqtrno,po.reqline
                            from hcvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join glhead as h on h.trno=cv.trno
                            left join gldetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as cvstat,

            (case when info.isasset= '' then 'NO' else info.isasset end) as isasset,info.specs,
            reqdept.clientname as deptname,stock.void
                  from postock as stock
                  left join pohead as head on head.trno=stock.trno
                  left join item on item.itemid=stock.itemid
                  left join transnum on transnum.trno=head.trno
                  left join client on client.clientid=stock.whid
                  left join client as supp on supp.client = head.client
                  left join client as dept on dept.clientid = head.deptid
                  left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline 
                  left join hprhead as prh on prh.trno=prs.trno 
                  left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line 
                  left join reqcategory as cat on cat.line=prh.ourref

                  left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                  left join client as creq on creq.clientname=xinfo.requestorname
                  left join client as reqdept on reqdept.clientid=creq.deptid

                  where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter 
                  group by head.docno,head.clientname ,item.barcode,item.itemname ,
                          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
                          dept.client,head.yourref, dept.clientname,info.itemdesc,info.requestorname,
                          stock.cdrefx,stock.cdlinex,stock.reqtrno,stock.reqline,info.isasset,info.specs,
                          reqdept.clientname,stock.void,stock.reqtrno,stock.reqline $addgrp
                  union all
                  select stock.reqtrno,stock.reqline,head.docno,head.clientname as supplier,item.barcode,item.itemname,info.itemdesc,
                        stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,head.ourref,
                        client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
                        dept.client as deptcode,head.yourref, dept.clientname as deptname, 
                        group_concat(distinct prh.docno separator ', ') as prdocno, 
                        group_concat(distinct info.ctrlno separator ', ') as ctrlno, 
                        ifnull(cat.category,'') as categoryname,ifnull(info.requestorname,'') as requestorname,
                        ifnull((select group_concat(distinct dept.clientname)
                                from client as dept 
                                left join hcdstock as cd on cd.deptid=dept.clientid
                                where cd.trno=stock.cdrefx and cd.line=stock.cdlinex),'') as department,
                        ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Draft - Void' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='RR' and s.void=1
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=0
                            union all
                            select 'Posted - Void' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR' and s.void=1) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as rrstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='RR') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as rrpostdate,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='SS'
                            union all
                            select 'Posted' as stat,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS'
                       ) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as ssstat,

                      ifnull((select group_concat(distinct postdate) as postdate
                      from (select date(num.postdate) as postdate,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS') as pd
                      where pd.reqtrno=stock.reqtrno and pd.reqline=stock.reqline),'') as sspostdate,

                      ifnull((select group_concat(distinct wh) as wh
                      from (select wh.clientname as wh,s.reqtrno,s.reqline
                            from lastock as s
                            left join lahead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR'
                            union all
                            select wh.clientname as wh,s.reqtrno,s.reqline
                            from glstock as s
                            left join glhead as h on h.trno=s.trno
                            left join client as wh on wh.clientid=s.whid
                            where h.doc='RR') as wh
                      where wh.reqtrno=stock.reqtrno and wh.reqline=stock.reqline),'') as rrwh,

                      ifnull((select group_concat(distinct stat) as stat
                      from (select 'Draft' as stat,rr.reqtrno,rr.reqline
                            from ladetail as s
                            left join lahead as h on h.trno=s.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Posted' as stat,rr.reqtrno,rr.reqline
                            from gldetail as s
                            left join glhead as h on h.trno=s.trno
                            left join cntnum as num on num.trno=h.trno
                            left join glstock as rr on rr.trno=s.refx
                            union all
                            select 'Draft' as stat,po.reqtrno,po.reqline
                            from cvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join lahead as h on h.trno=cv.trno
                            left join ladetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null
                            union all
                            select 'Posted' as stat,po.reqtrno,po.reqline
                            from hcvitems as cv
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join glhead as h on h.trno=cv.trno
                            left join gldetail as d on d.trno=h.trno and d.line=cv.line
                            left join cntnum as c on c.trno=h.trno
                            where h.trno is not null) as stat
                      where stat.reqtrno=stock.reqtrno and stat.reqline=stock.reqline),'') as cvstat,

            (case when info.isasset= '' then 'NO' else info.isasset end) as isasset,info.specs,
            reqdept.clientname as deptname,stock.void
                  from hpostock as stock
                  left join hpohead as head on head.trno=stock.trno
                  left join item on item.itemid=stock.itemid
                  left join transnum on transnum.trno=head.trno
                  left join client on client.clientid=stock.whid
                  left join client as supp on supp.client = head.client
                  left join client as dept on dept.clientid = head.deptid
                  left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline 
                  left join hprhead as prh on prh.trno=prs.trno 
                  left join hstockinfotrans as info on info.trno=prs.trno and info.line=prs.line 
                  left join reqcategory as cat on cat.line=prh.ourref

                  left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                  left join client as creq on creq.clientname=xinfo.requestorname
                  left join client as reqdept on reqdept.clientid=creq.deptid

                  where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter
                  group by head.docno,head.clientname ,item.barcode,item.itemname ,stock.uom,stock.rrqty,
                          stock.rrcost,stock.disc,stock.ext,head.ourref,
                          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,
                          dept.client,head.yourref, dept.clientname,info.itemdesc,info.requestorname,
                          stock.cdrefx,stock.cdlinex,stock.reqtrno,stock.reqline,info.isasset,info.specs,
                          reqdept.clientname,stock.void,stock.reqtrno,stock.reqline $addgrp
                  order by docno $sorting";
        break;
    } // end switch posttype

    return $query;
  }

  public function housegem_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $addjoin = "";

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.client = head.wh
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO'  and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTEDs' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
          union all
          select 'POSTEDs' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname
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
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          (select ifnull(group_concat(distinct docno separator ' /'),'') from ( select docno, xs.refx, xs.linex from lahead as xl
          left join lastock as xs on xl.trno = xs.trno where doc = 'rr'
          union all 
          select docno, xs.refx, xs.linex from glhead as xl 
          left join glstock as xs on xl.trno = xs.trno where doc = 'rr') as m
          where refx = stock.trno and linex = stock.line
          ) as ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          (select ifnull(group_concat(distinct docno separator ' /'),'') from ( select docno, xs.refx, xs.linex from lahead as xl
          left join lastock as xs on xl.trno = xs.trno where doc = 'rr'
          union all 
          select docno, xs.refx, xs.linex from glhead as xl 
          left join glstock as xs on xl.trno = xs.trno where doc = 'rr') as m
          where refx = stock.trno and linex = stock.line
          ) as ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          union all
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }


  public function gfc_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $addjoin = "";


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.client = head.wh
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO'  and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref
          order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTEDs' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref
          union all
          select 'POSTEDs' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname,head.yourref
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
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          union all
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname,head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }


  public function gfc_summarized_layout($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_gfc($config);
    $str .= $this->tableheader_gfc($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_gfc($config);
          $str .= $this->tableheader_gfc($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '90', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function gfc_detailed_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_gfc($config);
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
          $str .= $this->reporter->page_break();
          $str .= $this->header_detailed_DEFAULT($config);
        } //end if

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
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Yourref: ' . $data->yourref, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '230', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();


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

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

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
          $str .= $this->reporter->page_break();
          $str .= $this->header_detailed_DEFAULT($config);
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '230', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();


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

  public function rozlabnte_DETAILED_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

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
          $str .= $this->reporter->page_break();
          $str .= $this->header_detailed_DEFAULT($config);
        } //end if

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
          $str .= $this->reporter->col('Terms: ' . $data->terms, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '230', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();


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

  public function reportDefaultLayout_ATI_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $layoutsize = '1800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

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
          $str .= $this->reporter->printline();
        } //end if

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
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Ctrl No.', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Barcode', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Price', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Discount', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total Price', '115', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PO No.', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PO Type', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PR #', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Requestor', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Department', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->ctrlno, '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->barcode, '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '190', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->disc, '60', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '115', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ourref, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdocno, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '190', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->requestorname, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->department, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();


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

  public function reportPostedPO_ISASSET($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $layoutsize = '2400';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_DEFAULT($config);
    $docno = "";
    $i = 0;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PO Document No.', '135', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Supplier', '190', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Ctrl No.', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Category', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Specifications', '500', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PR Document No.', '135', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Requestor', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department (Requestor)', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RR Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RR Posted Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RR Warehouse', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CV Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '135', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->supplier, '190', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ctrlno, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->categoryname, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemdesc, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->specs, '500', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdocno, '135', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->deptname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rrstat, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rrpostdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rrwh, '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->cvstat, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', ''); //'Total: ' . number_format($total, 2)
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDetailedListing($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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
    $layoutsize = '3200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_DEFAULT($config);
    $docno = "";
    $i = 0;
    $void = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Supplier', '160', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Item Description', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Specifications', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('PO Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('PR Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('CD Document #', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');

    $str .= $this->reporter->col('Requestor', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Department (Requestor)', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');

    $str .= $this->reporter->col('RR Status', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('RR Posted Date', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('SS Status', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('SS Posted Date', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('RR Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('CV Status', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Is Asset', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Category', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Void', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '8px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $query = "select h.docno,h.trno,rr.reqtrno,rr.reqline,ifnull((select group_concat(distinct checkno SEPARATOR '\r') from ladetail as detail where detail.trno=h.trno),'') as checkno
                    from ladetail as s
                    left join lahead as h on h.trno=s.trno
                    left join glstock as rr on rr.trno=s.refx
                    where rr.reqtrno= $data->reqtrno and rr.reqline= $data->reqline
                    union all
                    select h.docno,h.trno,rr.reqtrno,rr.reqline,ifnull((select group_concat(distinct checkno SEPARATOR '\r') from gldetail as detail where detail.trno=h.trno),'') as checkno
                    from gldetail as s
                    left join glhead as h on h.trno=s.trno
                    left join glstock as rr on rr.trno=s.refx
                    where rr.reqtrno= $data->reqtrno and rr.reqline= $data->reqline
                    union all
                    select h.docno,h.trno,po.reqtrno,po.reqline,ifnull((select group_concat(distinct checkno SEPARATOR '\r') from ladetail as detail where detail.trno=h.trno),'') as checkno
                    from cvitems as cv
                    left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                    left join lahead as h on h.trno=cv.trno
                    left join ladetail as d on d.trno=h.trno and d.line=cv.line
                    where h.trno is not null and po.reqtrno= $data->reqtrno and po.reqline= $data->reqline
                    union all
                    select h.docno,h.trno,po.reqtrno,po.reqline,ifnull((select group_concat(distinct checkno SEPARATOR '\r') from gldetail as detail where detail.trno=h.trno),'') as checkno
                    from hcvitems as cv
                    left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                    left join glhead as h on h.trno=cv.trno
                    left join gldetail as d on d.trno=h.trno and d.line=cv.line
                    where h.trno is not null and po.reqtrno= $data->reqtrno and po.reqline= $data->reqline";

        $chkno = json_decode(json_encode($this->coreFunctions->opentable($query)), true);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->supplier, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->itemdesc, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->specs, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->ref, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');

        $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->deptname, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '4px');

        $str .= $this->reporter->col($data->rrstat, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->rrpostdate, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->ssstat, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->sspostdate, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->rrwh, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->cvstat, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->col($data->isasset, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');

        $str .= $this->reporter->col($data->categoryname, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        if ($data->void == 1) {
          $void = 'YES';
        } else {
          $void = 'NO';
        }
        $str .= $this->reporter->col($void, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '4px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportmaxiproLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];

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
    $layoutsize = '2700';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $totalpoqty = 0;
    $totalpoamt = 0;
    $totalrrqty = 0;
    $totalrramt = 0;

    $totalpobal = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_maxipro($config);
    $docno = "";
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totalpobal = ($data->poqty - $data->voidqty) - $data->rrqty;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->podate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '230', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rrdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '220', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->poqty, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->poamt, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->pototal, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->rrdate, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rrqty, '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->voidqty == 0 ? '-' : number_format($data->voidqty, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($totalpobal == 0 ? '-' : number_format($totalpobal, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->postat, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->projname, '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->subproject, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->porem, '460', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->endtable();

        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) {
            $str .= $this->header_detailed_maxipro($config);
          } else {
            $str .= $this->header_detailed_maxipro($config, false);
          }
          $page = $page + $count;
        } //end if

      }
    }

    $str .= $this->reporter->printline();

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

    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

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
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

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
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
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

  public function reportDefaultLayout_ATI_SUMMARIZED($config)
  {

    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    // $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader_ati($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->ctrlno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '210', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->prdocno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->categoryname, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader_ati($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '930', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '90', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportmaxiproLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1900;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_maxipro($config);
    $str .= $this->tableheadermaxipro($layoutsize, $config);

    $totalext = 0;
    $pobal = 0;
    $billingstat = '';

    $totalvoid = 0;
    $totalrramt = 0;
    $totalbillamt = 0;
    $totalpobal = 0;
    $powvoid = 0;
    $postat = '';

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        if ($data->rrctr > 0) {
          $percentpo = ($data->rrctr / $data->postockctr) * 100;
        } else {
          $percentpo = 0;
        }

        $pobal = round(($data->ext - $data->voidamt) - $data->billingamt, 2);



        if ($data->rramt == 0) {
          if ($data->billingamt != 0) {
            $billingstat = 'PAID IN ADVANCE';
          }
        } else {
          if ($data->billingamt != 0) {
            if ($pobal <= 0) {
              $billingstat = 'PAID IN FULL';
            } else {

              $billingstat = 'PARTIAL PAYMENT';
            }
          }
        }





        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->voidamt != 0 ? number_format($data->voidamt, 2) : '-', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rramt != 0 ? number_format($data->rramt, 2) : '-', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percentpo, 2) . ' %', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->billingamt != 0 ? number_format($data->billingamt, 2) : '-', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($pobal, 2) . '&nbsp', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->cvdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($billingstat, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

        $postat = $data->postatus;
        if ($data->voidamt != 0) {
          $powvoid = round(($data->voidamt + $data->rramt), 2);
          if ($powvoid == $data->ext) {
            $postat = 'Complete';
          }
        }


        $str .= $this->reporter->col($postat, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->rem, '400', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;

        $totalvoid = $totalvoid + $data->voidamt;
        $totalrramt = $totalrramt + $data->rramt;
        $totalbillamt = $totalbillamt + $data->billingamt;
        $totalpobal = $totalpobal + $pobal;

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_maxipro($config);
          $str .= $this->tableheadermaxipro($layoutsize, $config);
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
    $str .= $this->reporter->col(number_format($totalvoid, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalrramt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'CT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalbillamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalpobal, 2) . '&nbsp', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARYPERITEM($config)
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
    $posttype    = $config['params']['dataparams']['posttype'];

    $count = 16;
    $page = 15;
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
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
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
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];



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

    $str = '';
    if ($companyid == 16) { //ati
      $layoutsize = '1200';
    } else {
      $layoutsize = $this->reportParams['layoutSize'];
    }
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
    if ($companyid != 8) { //not maxipro
      $sorting    = $config['params']['dataparams']['sorting'];
      $prefix     = $config['params']['dataparams']['approved'];
      $filterusername  = $config['params']['dataparams']['username'];
      if ($filterusername != "") {
        $user = $filterusername;
      } else {
        $user = "ALL USERS";
      }

      if ($sorting == 'ASC') {
        $sorting = 'Ascending';
      } else {
        $sorting = 'Descending';
      }
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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
      if ($companyid != 8) { //not maxipro
        $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      if ($companyid != 8) { //not maxipro
        $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function header_maxipro($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $supplier  = $config['params']['dataparams']['dclientname'];
    $projectname = $config['params']['dataparams']['dprojectname'];
    $subproject  = $config['params']['dataparams']['subprojectname'];

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

    if (empty($supplier)) {
      $suppliername = 'ALL';
    } else {
      $suppliername = $config['params']['dataparams']['clientname'];
    }

    if (empty($projectname)) {
      $projectname = 'ALL';
    } else {
      $projectname = $config['params']['dataparams']['dprojectname'];
    }

    if (empty($subproject)) {
      $subproject = 'ALL';
    } else {
      $subproject = $config['params']['dataparams']['subprojectname'];
    }


    $str = '';
    $layoutsize = 1900;
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

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier: ' . $suppliername, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project: ' . $projectname, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sub Project: ' . $subproject, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
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
        $layoutsize = 1300;
        break;
      case 1:
        $reporttype = 'Detailed';
        $layoutsize = 1300;
        break;
      case 2:
        $reporttype = 'Is Asset';
        $layoutsize = 2400;
        break;
      case 3:
        $reporttype = 'Detailed - Listing';
        $layoutsize = 2820;
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

    $str = '';

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
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
      $str .= $this->reporter->col('Project : ', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
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
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function header_detailed_gfc($config)
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


    $str = '';
    $layoutsize = 1000;
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
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
    $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function header_detailed_maxipro($config, $showHeader = true)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $supplier  = $config['params']['dataparams']['dclientname'];
    $projectname = $config['params']['dataparams']['dprojectname'];
    $subproject  = $config['params']['dataparams']['subprojectname'];

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

    if (empty($supplier)) {
      $suppliername = 'ALL';
    } else {
      $suppliername = $config['params']['dataparams']['clientname'];
    }

    if (empty($projectname)) {
      $projectname = 'ALL';
    } else {
      $projectname = $config['params']['dataparams']['dprojectname'];
    }

    if (empty($subproject)) {
      $subproject = 'ALL';
    } else {
      $subproject = $config['params']['dataparams']['subprojectname'];
    }

    $str = '';
    $layoutsize = '2700';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($showHeader) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $config);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br/><br/>';
      $str .= $this->reporter->begintable($layoutsize);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Supplier: ' . $suppliername, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project: ' . $projectname, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub Project: ' . $subproject, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br/><br/>';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PO Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('Supplier', '230', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PR Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PR Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('RR Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('Item Description', '220', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Qty', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Unit Price', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Total', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('RR Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('RR Qty', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('Void Qty', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Qty Balance', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('Project', '320', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('Subproject', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('PO Remarks', '460', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function header_gfc($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];



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
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $sorting    = $config['params']['dataparams']['sorting'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filterusername  = $config['params']['dataparams']['username'];
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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

    $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

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

  public function tableheader_ati($layoutsize, $config)
  {

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CTRL NO.', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '210', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO Type', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PR #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CATEGORY', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function tableheader_gfc($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YOURREF', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function tableheadermaxipro($layoutsize, $config)
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
    $str .= $this->reporter->col('PO AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VOID AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RR AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO COMPLETE (%)', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT BILLED', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO BALANCE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CV DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BILLING STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function housegem_detailed_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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

    // $count=3;
    // $page=2;
    // $this->reporter->linecounter=0;

    $str = '';
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

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
          $str .= $this->reporter->page_break();
          $str .= $this->header_detailed_DEFAULT($config);
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '230', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();


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
}//end class