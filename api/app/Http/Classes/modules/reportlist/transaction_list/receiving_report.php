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

class receiving_report
{
  public $modulename = 'Receiving Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1100'];

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
      case 21: //kinggeorge
        array_push($fields, 'dwhname');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 8: //maxipro
        array_push($fields, 'dprojectname', 'subprojectname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'subprojectname.required', false);
        data_set($col1, 'subprojectname.readonly', false);
        data_set($col1, 'dprojectname.lookupclass', 'projectcode');
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
      case 11: //SUMMIT
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
      case 16: //ATI
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
            ['label' => 'Is Asset', 'value' => '2', 'color' => 'orange']
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
    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as `end`,'' as client,'' as clientname,'' as userid,
                        '' as username,'' as approved,'0' as posttype,'0' as reporttype, 'ASC' as sorting,'' as dclientname,'' as reportusers,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,
                       
                        '' as dprojectname, '' as projectname, '' as projectcode,'' as subprojectname,
                        '0' as clientid, '0' as deptid";
    switch ($companyid) {
      case 21: //kinggeorge
        $paramstr .= ", '' as wh,'' as whname,'' as dwhname,'' as whid";
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
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
      case '2': // SUMMARIZED PER ITEM
        switch ($config['params']['companyid']) {
          case 11: //summit
            $result = $this->reportDefaultLayout_SUMMARYPERITEM($config);
            break;
          case 16: //ati
            $result = $this->reportDefaultLayout_ISASSET($config);
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
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $query = $this->default_QUERY_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $query = $this->default_QUERY_DETAILED($config);
        break;
      case '2':
        switch ($config['params']['companyid']) {
          case 11: // SUMMIT
            $query = $this->SUMMIT_QUERY($config);
            break;
          case 16: //ATI
            $query = $this->ISASSET_QUERY($config);
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $projectcode = $config['params']['dataparams']['project'];
    $dept = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];


    $filter = "";
    $filter1 = "";
    $filter2 = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";
      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }


    if ($companyid == 21) { //kinggeorge
      $whid = $config['params']['dataparams']['whid'];
      $whname = $config['params']['dataparams']['whname'];
      if ($whname != "") {
        $filter2 .= " and client.clientid= $whid";
      }
    } else {
      $filter2 .= "";
    }

    if ($companyid == 8) { //maxipro
      $subprojectname = $config['params']['dataparams']['subprojectname'];
      $projectid = $config['params']['dataparams']['projectid'];
      if ($subprojectname != "") {
        $subproject = $config['params']['dataparams']['subproject'];
      }
      if ($projectcode != "") {
        $filter .= " and head.projectid = '" . $projectid . "' ";
      }
      if ($subprojectname != "") {
        $filter .= " and head.subproject = '" . $subproject . "' ";
      }
    }

    $trnxx = '';
    $leftjoin1 = '';
    $leftjoin = '';
    if ($companyid == 16) { //ati
      $adminid = $config['params']['adminid'];

      if ($adminid != 0) {
        $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);

        if (!empty($trnx)) {
          $trnxx .= " and info1.trnxtype='" . $trnx . "' ";
          $leftjoin = "left join cntnuminfo as info1 on info1.trno=head.trno";
          $leftjoin1 = "left join hcntnuminfo as info1 on info1.trno=head.trno";
        }
      }
    }

    switch ($posttype) {
      case 0: // posted
        if ($companyid == 40) { //cdo
          $query = "select head.docno, head.dateid, head.clientname as supplier,item.itemname as model_name, stock.rrqty, 
          si.color, si.serial, si.chassis, si.pnp, si.csr, stock.rrcost, stock.disc, stock.ext, cntnum.center,
          head.yourref,head.ourref
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join cntnum on cntnum.trno = head.trno
          left join client as supp on supp.clientid = head.clientid
          left join rrstatus as rr on rr.trno = stock.trno and rr.line = stock.line
          left join serialin as si on si.trno = rr.trno and si.line = rr.line
          where head.doc='RR' and date(head.dateid) between '$start' and '$end' $filter and cat.name = 'MC UNIT'
          order by docno, center $sorting";
        } else {
          $query = "select * from ( select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center, dept.client as deptcode, dept.clientname as deptname, head.yourref,prinfo.ctrlno,pr.docno as prdocno
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid=head.clientid
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin1
          " . $addjoin . "
          where head.doc='RR'  and date(head.dateid) between '$start' and '$end' $filter $filter1 $filter2    $trnxx
          ) as a order by docno,center $sorting";
        }
        break;

      case 1: // unposted
        if ($companyid == 40) { //cdo
          $query = "select head.docno, head.dateid, head.clientname as supplier, item.itemname as model_name, stock.rrqty, 
          si.color, si.serial, si.chassis, si.pnp, si.csr, stock.rrcost, stock.disc, stock.ext, cntnum.center,
          head.yourref,head.ourref
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join cntnum on cntnum.trno = head.trno
          left join client as supp on supp.client = head.client
          left join serialin as si on si.trno = stock.trno and si.line = stock.line
          where head.doc='RR' and date(head.dateid) between '$start' and '$end' $filter and cat.name = 'MC UNIT'  
          order by docno, center $sorting";
        } else {
          $query = "select * from (
          select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center,dept.client as deptcode, dept.clientname as deptname,head.yourref,
          prinfo.ctrlno,pr.docno as prdocno
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno     $leftjoin
          " . $addjoin . "
          where head.doc='RR' and date(head.dateid) between '$start' and '$end' $filter $filter1 $filter2   $trnxx
          ) as a order by docno,center $sorting";
        }
        break;

      default: // sana all
        if ($companyid == 40) { //cdo
          $query = "select head.docno, head.dateid, head.clientname as supplier, item.itemname as model_name, stock.rrqty, 
          si.color, si.serial, si.chassis, si.pnp, si.csr, stock.rrcost, stock.disc, stock.ext, cntnum.center,
          head.yourref,head.ourref
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join cntnum on cntnum.trno = head.trno
          left join client as supp on supp.clientid = head.clientid
          left join rrstatus as rr on rr.trno = stock.trno and rr.line = stock.line
          left join serialin as si on si.trno = rr.trno and si.line = rr.line
          where head.doc='RR' and head.dateid between '$start' and '$end' $filter and cat.name = 'MC UNIT'    
          union all
          select head.docno, head.dateid, head.clientname as supplier, item.itemname as  model_name, stock.rrqty, 
          si.color, si.serial, si.chassis, si.pnp, si.csr, stock.rrcost, stock.disc, stock.ext, cntnum.center,
          head.yourref,head.ourref
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join model_masterfile as modelgrp on modelgrp.model_id = item.model
          left join cntnum on cntnum.trno = head.trno
          left join client as supp on supp.client = head.client
          left join serialin as si on si.trno = stock.trno and si.line = stock.line
          where head.doc='RR' and head.dateid between '$start' and '$end' $filter and cat.name = 'MC UNIT'  
          order by docno, center $sorting";
        } else {
          $query = "select * from ( select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center,dept.client as deptcode, dept.clientname as deptname,head.yourref,prinfo.ctrlno,pr.docno as prdocno
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid=head.clientid
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin1
          " . $addjoin . "
          where head.doc='RR' and head.dateid between '$start' and '$end' $filter $filter1 $filter2  $trnxx
          union all
          select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center, dept.client as deptcode, dept.clientname as deptname,head.yourref,prinfo.ctrlno,pr.docno as prdocno
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin
          " . $addjoin . "
          where head.doc='RR' and head.dateid between '$start' and '$end' $filter $filter1 $filter2  $trnxx
          ) as a order by docno,center $sorting";
        }
        break;
    }
    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $whid   = isset($config['params']['dataparams']['whid']) ? $config['params']['dataparams']['whid'] : '';
    $projectcode = $config['params']['dataparams']['project'];
    $dept = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    if ($companyid == 21) { //kinggeorge
      $whid = $config['params']['dataparams']['whid'];
      $whname = $config['params']['dataparams']['whname'];
      if ($whname != "") {
        $filter2 .= " and client.clientid= $whid";
      }
    } else {
      $filter2 .= "";
    }

    $trnxx = '';
    $leftjoin1 = '';
    $leftjoin = '';
    if ($companyid == 16) { //ati
      $adminid = $config['params']['adminid'];
      if ($adminid != 0) {
        $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
        if (!empty($trnx)) {
          $trnxx .= " and info1.trnxtype='" . $trnx . "' ";
          $leftjoin = "left join cntnuminfo as info1 on info1.trno=head.trno";
          $leftjoin1 = "left join hcntnuminfo as info1 on info1.trno=head.trno";
        }
      }
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,deptcode,deptname,ourref,yourref,ctrlno,prdocno,
        ifnull(group_concat(distinct podocno separator '/ '),'') as podocno, orderno from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,client.client as wh,head.rem as hrem,cntnum.center,
      dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,head.yourref as yourref,prinfo.ctrlno,pr.docno as prdocno,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno, head.orderno
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as supp on supp.clientid=head.clientid
      left join client as dept on dept.clientid = head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin1
      where head.doc='RR'  and date(head.dateid) between '$start' and '$end' $filter $filter1 $filter2  $trnxx
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem,center, deptcode, deptname,ourref,yourref,ctrlno,prdocno,orderno
      order by docno $sorting";

        break;

      case 1: // unposted
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center,deptcode,deptname,ourref,yourref,ctrlno,prdocno,
        ifnull(group_concat(distinct podocno separator '/ '),'') as podocno,orderno from ( 
      select head.docno,head.clientname as supplier,
      item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
      ,client.client as wh,head.rem as hrem,cntnum.center, dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,head.yourref as yourref,prinfo.ctrlno,pr.docno as prdocno,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno, head.orderno
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      left join client as supp on supp.client = head.client
      left join client as dept on dept.clientid = head.deptid
      left join client as agent on agent.client=head.agent
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin
      where head.doc='RR' and date(head.dateid) between '$start' and '$end' $filter $filter1 $filter2  $trnxx
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center, deptcode,deptname,ourref,yourref,ctrlno,prdocno,orderno
      order by docno $sorting";

        break;

      default: // all
        $query = "select docno,dateid,supplier,wh,clientname as whname,sum(ext) as amount,hrem,center, deptcode,deptname,ourref,yourref,ctrlno,prdocno,
        ifnull(group_concat(distinct podocno separator '/ '),'') as podocno,orderno from ( 
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,stock.msako,stock.tsako,client.client as wh,head.rem as hrem,cntnum.center,
      dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,head.yourref as yourref,prinfo.ctrlno,pr.docno as prdocno,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno, head.orderno
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as supp on supp.clientid=head.clientid
      left join client as dept on dept.clientid = head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin1
      where head.doc='RR' and head.dateid between '$start' and '$end' $filter $filter1 $filter2  $trnxx
      union all
      select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,stock.msako,stock.tsako
      ,client.client as wh,head.rem as hrem,cntnum.center,dept.client as deptcode, dept.clientname as deptname,head.ourref as ourref,head.yourref as yourref,prinfo.ctrlno,pr.docno as prdocno,
      (select group_concat(po.docno separator '\r\n') from hpohead as po
      where po.trno=stock.refx) as podocno, head.orderno
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      left join client as supp on supp.client = head.client
      left join client as dept on dept.clientid = head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                left join hprhead as pr on pr.trno=prinfo.trno  $leftjoin
      where head.doc='RR' and date(head.dateid) between '$start' and '$end' $filter $filter1 $filter2  $trnxx
      ) as a 
      group by docno,dateid,supplier,wh,clientname,hrem ,center, deptcode, deptname,ourref,yourref,ctrlno,prdocno,orderno
      order by docno $sorting";

        break;
    }

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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as wh on wh.clientid = head.whid
          where head.doc='RR'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting
          ";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='RR'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
                    sum(stock.ext) as ext
                    from lastock as stock
                    left join lahead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.client = head.wh
                    where head.doc='RR'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    UNION ALL
                    select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
                    sum(stock.ext) as ext
                    from glstock as stock
                    left join glhead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    left join client as wh on wh.clientid = head.whid
                    where head.doc='RR'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    order by clientname,itemname $sorting";
        break;
    } // end switch posttype



    return $query;
  }

  public function ISASSET_QUERY($config)
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
    $filter2 = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $addjoin = "";

    $filter2 .= "";

    $trnxx = '';
    $leftjoin1 = '';
    $leftjoin = '';

    $adminid = $config['params']['adminid'];
    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);

      if (!empty($trnx)) {
        $trnxx .= " and info1.trnxtype='" . $trnx . "' ";
        $leftjoin = "left join cntnuminfo as info1 on info1.trno=head.trno";
        $leftjoin1 = "left join hcntnuminfo as info1 on info1.trno=head.trno";
      }
    }
    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center, dept.client as deptcode, dept.clientname as deptname, 
          head.yourref,prinfo.ctrlno,pr.docno as prdocno,ifnull(prinfo.requestorname,'') as requestorname,
          ifnull(prinfo.specs,'') as specs,prj.name as stock_projectname,ifnull(cat.category,'') as category,
          ifnull(prinfo.itemdesc,'') as itemdesc
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid=head.clientid
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno
          left join projectmasterfile as prj on prj.line = stock.projectid
          left join reqcategory as cat on cat.line=pr.ourref  $leftjoin1
          " . $addjoin . "
          where head.doc='RR' and prinfo.isasset = 'YES' and head.dateid between '$start' and '$end' $filter $filter1 $filter2 $trnxx
          ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
          select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center,dept.client as deptcode, dept.clientname as deptname,head.yourref,
          prinfo.ctrlno,pr.docno as prdocno,ifnull(prinfo.requestorname,'') as requestorname,
          ifnull(prinfo.specs,'') as specs,prj.name as stock_projectname,ifnull(cat.category,'') as category,
          ifnull(prinfo.itemdesc,'') as itemdesc
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno 
          left join projectmasterfile as prj on prj.line = stock.projectid
          left join reqcategory as cat on cat.line=pr.ourref $leftjoin
          " . $addjoin . "
          where head.doc='RR' and prinfo.isasset = 'YES' and head.dateid between '$start' and '$end' $filter $filter1 $filter2   $trnxx
          ) as a order by docno,center $sorting";
        break;

      default: // sana all
        $query = "select * from ( select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center,dept.client as deptcode, dept.clientname as deptname,
          head.yourref,prinfo.ctrlno,pr.docno as prdocno,ifnull(prinfo.requestorname,'') as requestorname,
          ifnull(prinfo.specs,'') as specs,prj.name as stock_projectname,ifnull(cat.category,'') as category,
          ifnull(prinfo.itemdesc,'') as itemdesc
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid=head.clientid
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno 
          left join projectmasterfile as prj on prj.line = stock.projectid
          left join reqcategory as cat on cat.line=pr.ourref $leftjoin1
          " . $addjoin . "
          where head.doc='RR' and prinfo.isasset = 'YES' and head.dateid between '$start' and '$end' $filter $filter1 $filter2  $trnxx
          union all
          select head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
          stock.ref,stock.msako,stock.tsako,cntnum.center, dept.client as deptcode, dept.clientname as deptname,
          head.yourref,prinfo.ctrlno,pr.docno as prdocno,ifnull(prinfo.requestorname,'') as requestorname,
          ifnull(prinfo.specs,'') as specs,prj.name as stock_projectname,ifnull(cat.category,'') as category,
          ifnull(prinfo.itemdesc,'') as itemdesc
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
          left join hprhead as pr on pr.trno=prinfo.trno 
          left join projectmasterfile as prj on prj.line = stock.projectid
          left join reqcategory as cat on cat.line=pr.ourref $leftjoin
          " . $addjoin . "
          where head.doc='RR' and prinfo.isasset = 'YES' and head.dateid between '$start' and '$end' $filter $filter1 $filter2  $trnxx
          ) as a order by docno,center $sorting";
        break;
    }

    $this->coreFunctions->LogConsole($query);
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
    $layoutsize = '1100';

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

    switch ($reporttype) {
      case 0:
        $reporttype = 'Summarized';
        break;
      case 1:
        $reporttype = 'Detailed';
        if ($companyid == 40) { //cdo
          $layoutsize = '1500';
        }
        break;
      case 2:
        if ($companyid == 16) { //ati
          $reporttype = 'Is Asset';
          $layoutsize = '1700';
        }
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

    if ($companyid == 21) { //kinggeorge
      $whname = $config['params']['dataparams']['whname'];
      if ($whname == "") {
        $whname = 'ALL';
      }
    }

    if ($companyid == 8) { //maxipro
      $project = $config['params']['dataparams']['dprojectname'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];

      if ($project == "") {
        $project = "ALL";
      }
      if ($subprojectname == "") {
        $subprojectname = "ALL";
      }
    }

    $count = 38;
    $page = 40;

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

    if ($companyid == 36) { //rozlab
      $layoutsize = '1000';
    }
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Receiving Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
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
        break;

      default:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();

        switch ($companyid) {
          case 21: //kinggeorge
            $str .= $this->reporter->col('Warehouse: ' . $whname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            break;
          case 8: //maxipro
            $str .= $this->reporter->col('Project: ' . $project, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            break;
          default:
            $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            break;
        }

        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        if ($companyid == 8) { //maxipro
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Sub Project: ' . $subprojectname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
        break;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1500';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    if ($companyid == 40) { //cdo
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DOCUMENT NO.', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('SUPPLIER NAME', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('YOURREF', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('OURREF', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('MODEL', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('QUANTITY', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('COLOR', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('ENGINE NO.', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('CHASSIS', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('PNP NO.', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('CSR NO.', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('PRICE', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('DISCOUNT', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('TOTAL', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $docno = '';
    $total = 0;
    $totalqty = 0;
    $totalamt = 0;
    $grandtotal = 0;
    $lastDocno = '';
    $lastDate = '';
    $lastSupplier = '';

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($companyid == 40) { //cdo
            $str .= $this->reporter->col('TOTAL:', '570', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col((int) $totalqty, '60', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '870', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col('TOTAL: ' . number_format($total, 2), $layoutsize, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= '<br/>';
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totalqty = 0;
          $lastDate = '';
          $lastSupplier = '';

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
          } else if ($companyid != 40) { //not cdo
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
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
              $str .= $this->reporter->col('SKU/Part No.', '83', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            case 40: //cdo
              break;
            default:
              $str .= $this->reporter->col('Barcode', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
              break;
          }

          if ($companyid != 40) { //not cdo
            $str .= $this->reporter->col('Item Description', '170', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Quantity', '78', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('UOM', '53', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Price', '78', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Discount', '78', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total Price', '78', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Warehouse', '83', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Location', '83', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Expiry', '83', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Reference', '135', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');

            if ($companyid == 16) { //ati
              $str .= $this->reporter->col('PO No.', '81', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
            } else {
              $str .= $this->reporter->col('Notes', '181', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
            }

            $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('PR Docno', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }
        }

        if ($companyid == 40) { //cdo
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $displayDocno = ($lastDocno == $data->docno) ? '' : $data->docno;
          $displayDate = ($lastDate == $data->dateid) ? '' : $data->dateid;
          $displaySupplier = ($lastSupplier == $data->supplier) ? '' : $data->supplier;

          $qty = ($data->rrqty >= 1) ? 1 : number_format($data->rrqty, 2);
          $totalamt = $data->ext / $data->rrqty;

          $str .= $this->reporter->col($displayDocno, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($displayDate, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($displaySupplier, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->yourref, '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ourref, '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->model_name, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($qty, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->color, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->serial, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->chassis, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->pnp, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->csr, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->rrcost, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->disc, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($totalamt, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $lastDocno = $data->docno;
          $lastDate = $data->dateid;
          $lastSupplier = $data->supplier;
        } else {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->barcode, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '170', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->rrqty, 2), '78', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '53', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->rrcost, 2), '78', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->disc, '78', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '78', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->clientname, '83', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->expiry, '83', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ref, '135', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');

          if ($companyid == 16) { //ati
            $str .= $this->reporter->col('TOTAL: ', '205', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col((int) $totalqty, '60', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '735', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col($data->rem, '181', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          }

          $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->addline();
        }

        if ($docno == $data->docno) {
          if ($companyid == 40) { //cdo
            $totalqty += $qty;
            $total += $totalamt;
            $grandtotal += $totalamt;
          } else {
            $total += $data->ext;
          }
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      if ($companyid == 40) { //cdo
        $str .= $this->reporter->col('TOTAL:', '570', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col((int) $totalqty, '60', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($total, 2), '870', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      } else {
        $str .= $this->reporter->col('TOTAL: ' . number_format($total, 2), $layoutsize, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= '<br/>';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //cdo
    $str .= $this->reporter->col($companyid == 40 ? 'GRAND TOTAL: ' . number_format($grandtotal, 2) : 'Total: ' . number_format($total, 2), $layoutsize, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_ISASSET($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

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

    $count = 38;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1700';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);


    $docno = '';
    $total = 0;
    $totalqty = 0;
    $totalamt = 0;
    $grandtotal = 0;
    $lastDocno = '';
    $lastDate = '';
    $lastSupplier = '';

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('TOTAL: ' . number_format($total, 2), $layoutsize, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= '<br/>';
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $totalqty = 0;
          $lastDate = '';
          $lastSupplier = '';

          $str .= $this->reporter->begintable($layoutsize);

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Item Description', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Quantity', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('UOM', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Price', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Discount', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Warehouse', '110', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Requestor', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Specifications', '330', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('PR Docno', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Category', '110', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->itemdesc, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->uom, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->disc, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->specs, '330', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->col($data->category, '110', null, false, $border, '', 'LT', $font, $fontsize, '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }

        // $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TOTAL: ' . number_format($total, 2), $layoutsize, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= '<br/>';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total: ' . number_format($total, 2), $layoutsize, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    //okks
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
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    switch ($companyid) {
      case 36: //rozlab
        $layoutsize = '1000';
        break;
      case 21: //kinggeorge
        $layoutsize = '1500';
        break;
        break;
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
        switch ($companyid) {
          case 17: //unihome
          case 39: //CBBSI
            $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->whname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '140', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->hrem, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            break;
          case 28: //xcomp
            $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->yourref, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->whname, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->podocno, '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->hrem, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            break;
          case 36: //rozlab
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->yourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->whname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->podocno, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->hrem, '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            break;
          case 21: //kinggeorge
            $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->whname, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->podocno, '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col($data->hrem, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            break;
          default:
            $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->whname, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->podocno, '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

            if ($companyid == 16) { //ati
              $str .= $this->reporter->col($data->orderno, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data->hrem, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col($data->hrem, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            }
            $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            break;
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $total = $total + $data->amount;
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($config, $layoutsize);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          switch ($companyid) {
            case 17: //unihome
              $str .= $this->reporter->col('', '600', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('Grand Total : ', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($total, 2), '140', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '210', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
              break;
            case 16: //ati
              break;
            default:
              $str .= $this->reporter->col('', '460', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('Grand Total : ', '140', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($total, 2), '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '280', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
              break;
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



    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_SUMMARYPERITEM($config);
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
          $str .= $this->reporter->page_break();
          $str .= $this->header_SUMMARYPERITEM($config);
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
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_SUMMARYPERITEM($config)
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
    $str .= $this->reporter->col('Receiving Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

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

    return $str;
  }

  public function tableheader($config, $layoutsize)
  {
    $companyid = $config['params']['companyid'];
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    switch ($companyid) {
      case 17: //unihome
      case 39: //CBBSI
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document No.', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OURREF', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Name', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '140', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
      case 28: //xcomp
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT NO.', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('YOURREF', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('WAREHOUSE', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('AMOUNT', '120', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('PO DOCUMENT NO.', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('REMARKS', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
      case 36: //rozlab
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('YOURREF', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('WAREHOUSE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('PO DOCUMENT NO.', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('REMARKS', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        break;
      case 21: //kinggeorge
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT NO.', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('WAREHOUSE', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('AMOUNT', '120', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('PO DOCUMENT NO.', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('SI#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('DR#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('REMARKS', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('CTRL NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('PR DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
      default:
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT NO.', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('WAREHOUSE', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('AMOUNT', '120', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('PO DOCUMENT NO.', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col('PO NO.', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
          $str .= $this->reporter->col('REMARKS', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        } else {
          $str .= $this->reporter->col('REMARKS', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        }
        $str .= $this->reporter->col('CTRL NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');
        $str .= $this->reporter->col('PR DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '2px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        break;
    }

    return $str;
  }
}//end class