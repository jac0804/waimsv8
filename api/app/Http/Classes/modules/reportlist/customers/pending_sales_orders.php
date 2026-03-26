<?php

namespace App\Http\Classes\modules\reportlist\customers;

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
use DateTime;

class pending_sales_orders
{
  public $modulename = 'Pending Sales Orders';
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
      $fields = ['radioprint', 'start', 'end', 'dcentername', 'dclientname', 'ditemname', 'divsion', 'brand', 'class', 'dagentname', 'categoryname', 'subcatname'];
    } else {
      $fields = ['radioprint', 'dclientname', 'dcentername', 'ditemname', 'divsion', 'brand', 'class', 'dagentname', 'categoryname', 'subcatname'];
    }
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname', 'industry');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'industry.readonly', true);
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        data_set($col1, 'start.type', 'coldel');
        data_set($col1, 'end.type', 'coldel');
        break;
      case 21: //kinggeorge
        $col1 = $this->fieldClass->create($fields);

        break;
      case 19: //housegem
        array_push($fields, 'radioposttype');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 59: //roosevelt
        array_push($fields, 'area', 'start', 'end', 'radioreporttype');
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
    $fields = ['radiotypeofreportpendingsalesorder'];
    $col2 = $this->fieldClass->create($fields);
    switch ($companyid) {
      case 21: //kinggeorge
        data_set(
          $col2,
          'radiotypeofreportpendingsalesorder.options',
          [
            ['label' => 'Customer', 'value' => 'client', 'color' => 'orange'],
            ['label' => 'Item', 'value' => 'item', 'color' => 'orange'],
            ['label' => 'Amount (Summarized)', 'value' => 'amount', 'color' => 'orange']
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
    $paramstr = "select
          'default' as print,
          adddate(left(now(),10),-360) as start,
          left(now(),10) as end,
          '' as client,
          '0' as clientid,
          '' as clientname,
          '' as ditemname,
          '' as barcode,
          '' as groupid,
          '' as brandid,
          '' as classid,
          '' as classic,
          'client' as typeofreport,
          '' as divsion,
          '' as brand,
          '' as class,
          '' as dclientname,
          '' as category,
          '' as categoryname,
          '0' as reporttype,
          '' as subcat,'' as dagentname,'' as agent,'' as agentid,'' as agentname,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername, '' as area";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= " ,'' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'' as industry ";
        break;
      case 19: //housegem
        $paramstr .= " ,'0' as posttype ";
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
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $username = $config['params']['user'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $reportt = $config['params']['dataparams']['reporttype']; //roosevelt


    switch ($companyid) {
      case 19: // housegem
        $result = $this->housegem_Layout($config);
        break;
      case 59: //roosevelt
        switch ($reportt) {
          case 0: //summarrized
            switch ($typeofreport) {
              case 'client':
                $result = $this->summ_Customer_roosevelt($config);
                break;

              case 'item':
                $result = $this->summ_ITEM_roosevelt($config);
                break;
            }
            break;
          case 1: //detailed
            switch ($typeofreport) {
              case 'client':
                $result = $this->reportDefaultLayout_Customer_roosevelt($config);
                break;

              case 'item':
                $result = $this->reportDefaultLayout_item_roosevelt($config);
                break;
            }

            break;
        }

        break;

      default:
        switch ($typeofreport) {
          case 'client':
            $result = $this->reportDefaultLayout_Customer($config);
            break;

          case 'item':
            $result = $this->reportDefaultLayout_Item($config);
            break;

          case 'amount':
            $result = $this->reportDefaultLayout_Amount($config);
            break;
        }
        break;
    } // end switch

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid     = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $agent       = $config['params']['dataparams']['agent'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $posttype = isset($config['params']['dataparams']['posttype']) ? $config['params']['dataparams']['posttype'] : '';

    $filter = "";
    $filter1 = "";
    $order = "";
    $datefilter = "";

    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid) {
      $filter = $filter . " and item.groupid='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandid != "") {
      $filter = $filter . " and item.brand='$brandid'";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($center != "") {
      $filter .= " and transnum.center='$center'";
    }

    if ($typeofreport == 'client') {
      $order = "cgrp,docno, itemname ";
    } else {
      if ($companyid == 21 && $config['params']['dataparams']['typeofreport'] == 'amount') { //kinggeorge
        $order = " itemname ";
      } else {
        $order = "igrp,docno, itemname ";
      }
    }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      $indus = $config['params']['dataparams']['industry'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and qsstock.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and qshead.deptid = $dept";
      }
      if ($indus != "") {
        $filter1 .= " and client.industry = '$indus'";
      }
    } else {
      $filter1 .= "";
    }

    if ($companyid == 21) { //kinggeorge
      $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
      $datefilter = " and date(head.dateid) between '" . $start . "' and '" . $end . "'";
    }

    switch ($companyid) {
      case 19: //housegem
        switch ($posttype) {
          case '0': //posted
            $query = "select client.clientname as cgrp,
            concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
            client.clientname, item.itemname, item.groupid, item.brand, item.class,
            date(head.dateid) as dateid, stock.qa,
            stock.iss as qty, (stock.iss-stock.qa) as unserved, 
            (stock.iss-stock.qa)*stock.amt as unservedamt, stock.amt,
            item.uom,head.ourref,
            head.yourref, item.itemid,'' as barcode,'' as subcode,'' as partno,
            cat.name as category, subcat.name as subcatname
            from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
            left join item on item.itemid=stock.itemid)
            left join client on client.client=head.client 
            left join client as agent on agent.client=head.agent
            left join transnum on transnum.trno=head.trno
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
            where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . " " . $datefilter . " and item.isofficesupplies= 0 ";
            break;

          default:
            $query = "select client.clientname as cgrp,
            concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
            client.clientname, item.itemname, item.groupid, item.brand, item.class,
            date(head.dateid) as dateid, stock.qa,
            stock.iss as qty, (stock.iss-stock.qa) as unserved, 
            (stock.iss-stock.qa)*stock.amt as unservedamt, stock.amt,
            item.uom,head.ourref,
            head.yourref, item.itemid,'' as barcode,'' as subcode,'' as partno,
            cat.name as category, subcat.name as subcatname
            from ((sohead as head left join sostock as stock on stock.trno=head.trno)
            left join item on item.itemid=stock.itemid)
            left join client on client.client=head.client 
            left join client as agent on agent.client=head.agent
            left join transnum on transnum.trno=head.trno          
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
            where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . " " . $datefilter;
            break;
        }


        break;
      case 10: //afti
      case 12: //afti usd
        $query = "select client.clientname as cgrp,
                      concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
                      client.clientname, item.itemname, item.groupid, item.brand, item.class,
                      date(head.dateid) as dateid, qsstock.qa,
                      qsstock.iss as qty, (qsstock.iss-qsstock.qa) as unserved, item.uom,qshead.ourref,
                      qshead.yourref, item.itemid,'' as barcode,'' as subcode,'' as partno
                from ((sqhead as head
                left join hqshead as qshead on qshead.sotrno=head.trno
                left join hqsstock as qsstock on qsstock.trno=qshead.trno)
                left join item on item.itemid=qsstock.itemid)
                left join client on client.client=qshead.client
                left join client as agent on agent.client=qshead.agent
                left join transnum on transnum.trno=head.trno
                where qsstock.void=0 and ( qsstock.iss-qsstock.qa)>0 $filter $filter1
                union all
                select client.clientname as cgrp,
                      concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
                      client.clientname, item.itemname, item.groupid, item.brand, item.class,
                      date(head.dateid) as dateid, qsstock.qa,
                      qsstock.iss as qty, (qsstock.iss-qsstock.qa) as unserved, item.uom,qshead.ourref,
                      qshead.yourref, item.itemid,'' as barcode,'' as subcode,'' as partno
                from ((hsqhead as head
                left join hqshead as qshead on qshead.sotrno=head.trno
                left join hqsstock as qsstock on qsstock.trno=qshead.trno)
                left join item on item.itemid=qsstock.itemid)
                left join client on client.client=qshead.client
                left join client as agent on agent.client=qshead.agent
                left join transnum on transnum.trno=head.trno
                where qsstock.void=0 and (qsstock.iss-qsstock.qa)>0 $filter $filter1 ";
        break;
      case 6: //mitsukoshi
        $query = "
          
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class, date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, item.uom,head.ourref,head.yourref,item.itemid
          , item.barcode as barcode, item.subcode as subcode, item.partno as partno
          from ((sahead as head left join sastock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)left join client on client.client=head.client left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          where stock.void=0 and (stock.iss-stock.qa)>0 $filter
          union all
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class, date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, item.uom,head.ourref,head.yourref, item.itemid
          , item.barcode as barcode, item.subcode as subcode, item.partno as partno
          from ((hsahead as head left join hsastock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)left join client on client.client=head.client left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          where stock.void=0 and (stock.iss-stock.qa)>0 $filter
          union all
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class, date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, item.uom,head.ourref,head.yourref, item.itemid
          , item.barcode as barcode, item.subcode as subcode, item.partno as partno
          from ((sbhead as head left join sbstock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)left join client on client.client=head.client left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          where stock.void=0 and (stock.iss-stock.qa)>0 $filter
          union all
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class, date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, item.uom,head.ourref,head.yourref, item.itemid
          , item.barcode as barcode, item.subcode as subcode, item.partno as partno
          from ((hsbhead as head left join hsbstock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)left join client on client.client=head.client left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          where stock.void=0 and (stock.iss-stock.qa)>0 $filter
          union all
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class, date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, item.uom,head.ourref,head.yourref, item.itemid
          , item.barcode as barcode, item.subcode as subcode, item.partno as partno
          from ((schead as head left join scstock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)left join client on client.client=head.client left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          where stock.void=0 and (stock.iss-stock.qa)>0 $filter
          union all
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class, date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, item.uom,head.ourref,head.yourref, item.itemid
          , item.barcode as barcode, item.subcode as subcode, item.partno as partno
          from ((hschead as head left join hscstock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)left join client on client.client=head.client left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          where stock.void=0 and (stock.iss-stock.qa)>0 $filter
        ";
        break;
      case 59: //roosevelt
        switch ($config['params']['dataparams']['reporttype']) {
          case '0':
            $query = "select cgrp, igrp, clientname, itemname, groupid, brand, class, sum(qa) as qa,
              sum(qty) as qty, sum(unserved) as unserved, sum(unservedamt) as unservedamt, uom, itemid,
              barcode, subcode, partno, category, subcatname from (
                select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                  client.clientname, item.itemname, item.groupid, item.brand, item.class, stock.qa,stock.iss as qty,
                  (stock.iss-stock.qa) as unserved, (stock.iss-stock.qa)*stock.amt as unservedamt, item.uom, item.itemid,
                  item.barcode,'' as subcode,'' as partno, cat.name as category, subcat.name as subcatname
                from ((sohead as head left join sostock as stock on stock.trno=head.trno)
                  left join item on item.itemid=stock.itemid)
                  left join client on client.client=head.client 
                  left join client as agent on agent.client=head.agent
                  left join transnum on transnum.trno=head.trno          
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . '' . $datefilter . "
                union all
                select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                  client.clientname, item.itemname, item.groupid, item.brand, item.class, stock.qa,stock.iss as qty,
                  (stock.iss-stock.qa) as unserved, (stock.iss-stock.qa)*stock.amt as unservedamt, item.uom, item.itemid,
                  item.barcode,'' as subcode,'' as partno, cat.name as category, subcat.name as subcatname
                from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
                  left join item on item.itemid=stock.itemid)
                  left join client on client.client=head.client 
                  left join client as agent on agent.client=head.agent
                  left join transnum on transnum.trno=head.trno
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . '' . $datefilter . " and item.isofficesupplies= 0 ) as t
                group by cgrp, igrp, clientname, itemname, groupid, brand, class, uom, itemid, barcode, subcode, partno, category, subcatname";
            if ($typeofreport == 'client') {
              $order = "cgrp, itemname";
            } else {
              $order = "itemname, cgrp";
            }
            break;
          default:
            $query = "select client.clientname as cgrp,
              concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
              client.clientname, item.itemname, item.groupid, item.brand, item.class,
              date(head.dateid) as dateid, stock.qa,
              stock.iss as qty, (stock.iss-stock.qa) as unserved, 
              (stock.iss-stock.qa)*stock.amt as unservedamt,
              item.uom,head.ourref,
              head.yourref, item.itemid,item.barcode,'' as subcode,'' as partno,
              cat.name as category, subcat.name as subcatname
              from ((sohead as head left join sostock as stock on stock.trno=head.trno)
              left join item on item.itemid=stock.itemid)
              left join client on client.client=head.client 
              left join client as agent on agent.client=head.agent
              left join transnum on transnum.trno=head.trno          
              left join itemcategory as cat on cat.line = item.category
              left join itemsubcategory as subcat on subcat.line = item.subcat
              where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . " " . $datefilter . "
              union all
              select client.clientname as cgrp,
              concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
              client.clientname, item.itemname, item.groupid, item.brand, item.class,
              date(head.dateid) as dateid, stock.qa,
              stock.iss as qty, (stock.iss-stock.qa) as unserved, 
              (stock.iss-stock.qa)*stock.amt as unservedamt,
              item.uom,head.ourref,
              head.yourref, item.itemid,item.barcode,'' as subcode,'' as partno,
              cat.name as category, subcat.name as subcatname
              from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
              left join item on item.itemid=stock.itemid)
              left join client on client.client=head.client 
              left join client as agent on agent.client=head.agent
              left join transnum on transnum.trno=head.trno
              left join itemcategory as cat on cat.line = item.category
              left join itemsubcategory as subcat on subcat.line = item.subcat
              where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . " " . $datefilter . " and item.isofficesupplies= 0 ";
            break;
        }
        break;
      default:
        $query = '';
        if ($companyid == 21 && $config['params']['dataparams']['typeofreport'] == 'amount') { //kinggeorge
          $query = 'select itemname, sum(unservedamt) as unservedamt from (';
        }
        $addfield = '';
        if ($companyid == 32) { //3m
          $addfield = ",client.brgy, client.area";
        }

        $query .= "select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class,
          date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, 
          (stock.iss-stock.qa)*stock.amt as unservedamt,
          item.uom,head.ourref,
          head.yourref, item.itemid,item.barcode,'' as subcode,'' as partno,
          cat.name as category, subcat.name as subcatname " . $addfield . "
          from ((sohead as head left join sostock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)
          left join client on client.client=head.client 
          left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno          
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . " " . $datefilter . "
          union all
          select client.clientname as cgrp,
          concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
          client.clientname, item.itemname, item.groupid, item.brand, item.class,
          date(head.dateid) as dateid, stock.qa,
          stock.iss as qty, (stock.iss-stock.qa) as unserved, 
          (stock.iss-stock.qa)*stock.amt as unservedamt,
          item.uom,head.ourref,
          head.yourref, item.itemid,item.barcode,'' as subcode,'' as partno,
          cat.name as category, subcat.name as subcatname " . $addfield . "
          from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
          left join item on item.itemid=stock.itemid)
          left join client on client.client=head.client 
          left join client as agent on agent.client=head.agent
          left join transnum on transnum.trno=head.trno
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where stock.void=0 and (stock.iss-stock.qa)>0 " . $filter . " " . $datefilter . " and item.isofficesupplies= 0 ";

        if ($companyid == 21 && $config['params']['dataparams']['typeofreport'] == 'amount') { //kinggeorge
          $query .= ") as so group by itemname";
        }

        break;
    }

    $query .= " order by $order ";
    return $this->coreFunctions->opentable($query);
  }


  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $classic    = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $agent       = $config['params']['dataparams']['agent'];
    $agentname       = $config['params']['dataparams']['agentname'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $filtercenter       = $config['params']['dataparams']['dcentername'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    if ($companyid == 19) { //housegem
      $posttype = $config['params']['dataparams']['posttype'];
      $post = $posttype == '0' ? 'POSTED' : 'UNPOSTED';
    }

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PENDING SALES ORDERS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $cus = $client == '' ? 'ALL' : $client;
    $item = $barcode == '' ? 'ALL' : $barcode;
    $group = $groupid == '' ? 'ALL' : $groupid;
    $brand = $brandid == '' ? 'ALL' : $brandid;
    $class = $classic == '' ? 'ALL' : $classic;

    $sorty = strtoupper($typeofreport);
    $age = $agent == '' ? 'ALL' : $agent . ' ~ ' . $agentname;
    $filtercenter = $filtercenter == '' ? 'ALL' : $filtercenter;

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 21) { //kinggeorge
      $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Customer : ' . strtoupper($cus), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Item : ' . strtoupper($item), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Group : ' . strtoupper($group), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 19) { //housegem
      $str .= $this->reporter->col('Transaction : ' . strtoupper($post), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brand), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . strtoupper($class), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Sort By : ' . strtoupper($sorty), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Agent : ' . strtoupper($age), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Industry : ' . $indus, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('Department : ' . $deptname, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('Project : ' . $projname, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 21) { //kinggeorge
      switch ($sorty) {
        case 'ITEM':
          $str .= $this->reporter->col($sorty, '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DOCUMENT #', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('BARCODE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('SUBCODE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PARTNO', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ORDERED', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('SERVED', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('BALANCE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          break;
        case 'AMOUNT':
          $str .= $this->reporter->col('ITEM DESCRIPTION', '500', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UNSERVED AMOUNT', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          break;

        default:
          $str .= $this->reporter->col($sorty, '290', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DOCUMENT #', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('DATE', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('ORDERED', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('SERVED', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('BALANCE', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          break;
      }
    } else if ($companyid == 59) { //roosevelt
      if ($config['params']['dataparams']['reporttype'] == '0') {
        $str .= $this->reporter->col($sorty, '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUBCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PARTNO', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ORDERED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SERVED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UOM', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      } else {
        goto defLayout;
      }
    } else {
      defLayout:
      $str .= $this->reporter->col($sorty, '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BARCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SUBCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PARTNO', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('ORDERED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SERVED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('UOM', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout_Amount($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $barcode    = $config['params']['dataparams']['barcode'];

    $count = 55;
    $page = 55;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $total = 0;
    $this->reporter->linecounter = 0;
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $item = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();
      $itemname = $data->itemname;
      $unservedamt = number_format($data->unservedamt, 2);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($itemname, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($unservedamt, '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $total += $data->unservedamt;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total', '700', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_Customer($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $item = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();
      $display = $data->itemname;
      $docno = isset($data->docno) ? $data->docno : '';
      $barcode = $data->barcode;
      $subcode = $data->subcode;
      $partno = $data->partno;
      $date = isset($data->dateid) ? $data->dateid : '';
      $order = $data->qty;
      $served = $data->qa;
      $bal = $data->unserved;
      $uom = $data->uom;
      $str .= $this->reporter->startrow();
      if ($item != $data->clientname) {
        if ($companyid == 21) { //kinggeorge
          $str .= $this->reporter->col($data->clientname, '290', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
        } else if ($companyid == 59) { //roosevelt
          $str .= $this->reporter->col($data->clientname . 'testtttt', '110', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
        } else {
          if ($companyid == 32) { //3m
            $str .= $this->reporter->col($data->clientname . ' - ' . $data->brgy . ', ' . $data->area, '110', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col($data->clientname, '110', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
          }
          $str .= $this->reporter->col('', '500', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
        }
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      if ($companyid == 21) { //kinggeorge
        $str .= $this->reporter->col($display, '290', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($docno, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($date, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($order, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($served, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($bal, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($uom, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } else if ($companyid == 59) { //roosevelt
        $str .= $this->reporter->col("&nbsp;&nbsp;" . $display, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($barcode, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subcode, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($partno, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($order, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($served, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($bal, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($uom, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($display, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($barcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($partno, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($date, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($order, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($served, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($bal, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->endrow();
      $item = $data->clientname;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_Item($config)
  {
    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $barcode    = $config['params']['dataparams']['barcode'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $item = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();
      $display = $data->clientname;
      $docno = isset($data->docno) ? $data->docno : '';
      $barcode = $data->barcode;
      $subcode = $data->subcode;
      $partno = $data->partno;
      $date = isset($data->dateid) ? $data->dateid : '';
      $order = $data->qty;
      $served = $data->qa;
      $bal = $data->unserved;
      $uom = $data->uom;
      $str .= $this->reporter->startrow();
      if ($item == $data->itemid) {
        $dis = "";
      } else {
        if ($companyid == 59) { //roosevelt
          if ($config['params']['dataparams']['reporttype'] == '0') {
            $str .= $this->reporter->col($data->itemname, '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted', 'T', 'L', 'B', '10', '', '', '');
          } else {
            goto defLayout1;
          }
        } else {
          defLayout1:
          if ($companyid == 32) { //3m
            $str .= $this->reporter->col($data->itemname . ' - ' . $data->brgy . ', ' . $data->area, '110', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col($data->itemname, '500', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
          }
          $str .= $this->reporter->col('', '500', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '');
        }
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      if ($companyid == 59) { //roosevelt
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $str .= $this->reporter->col("&nbsp;&nbsp;&nbsp;" . $display, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($barcode, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($subcode, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($partno, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($order, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($served, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($bal, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($uom, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        } else {
          goto defLayout2;
        }
      } else {
        defLayout2:
        $str .= $this->reporter->col($display, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($barcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($partno, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($date, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($order, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($served, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($bal, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->endrow();
      $item = $data->itemid;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function housegem_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $classic    = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $agent       = $config['params']['dataparams']['agent'];
    $agentname       = $config['params']['dataparams']['agentname'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $filtercenter       = $config['params']['dataparams']['dcentername'];
    $posttype = $config['params']['dataparams']['posttype'];
    $post = $posttype == '0' ? 'POSTED' : 'UNPOSTED';


    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PENDING SALES ORDERS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $cus = $client == '' ? 'ALL' : $client;
    $item = $barcode == '' ? 'ALL' : $barcode;
    $group = $groupid == '' ? 'ALL' : $groupid;
    $brand = $brandid == '' ? 'ALL' : $brandid;
    $class = $classic == '' ? 'ALL' : $classic;

    $sorty = strtoupper($typeofreport);
    $age = $agent == '' ? 'ALL' : $agent . ' ~ ' . $agentname;
    $filtercenter = $filtercenter == '' ? 'ALL' : $filtercenter;

    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Customer : ' . strtoupper($cus), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Item : ' . strtoupper($item), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Group : ' . strtoupper($group), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($post), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brand), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . strtoupper($class), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Sort By : ' . strtoupper($sorty), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Agent : ' . strtoupper($age), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($sorty, '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUBCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PARTNO', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ORDERED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SERVED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PRICE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function housegem_Layout($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->housegem_header($config);

    $item = null;
    $subtotalorder =
      $subtotalserved =
      $subtotalbalance =
      $subtotalprice =
      $subtotalunserved =

      $totalorder =
      $totalserved =
      $totalbalance =
      $totalprice =
      $totalunserved = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();
      if ($typeofreport == 'client') {
        $display = $data->itemname;
        $group = $data->clientname;
      } else {
        $display = $data->clientname;
        $group = $data->itemname;
      }
      $docno = $data->docno;
      $barcode = $data->barcode;
      $subcode = $data->subcode;
      $partno = $data->partno;
      $date = $data->dateid;
      $order = $data->qty;
      $served = $data->qa;
      $bal = $data->unserved;
      $unservedamt = $data->unservedamt;
      $amt = $data->amt;
      $uom = $data->uom;


      if ($item != $group) {
        if ($item != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '4px', '4px');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '4px', '4px');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '4px', '4px');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '4px', '4px');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '4px', '4px');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '4px', '4px');
          $str .= $this->reporter->col(number_format($subtotalorder, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '4px', '4px');
          $str .= $this->reporter->col(number_format($subtotalserved, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '4px', '4px');
          $str .= $this->reporter->col(number_format($subtotalbalance, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '4px', '4px');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '4px', '4px');
          $str .= $this->reporter->col(number_format($subtotalprice, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '4px', '4px');
          $str .= $this->reporter->col(number_format($subtotalunserved, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '4px', '4px');
          $str .= $this->reporter->endrow();
          $subtotalorder =
            $subtotalserved =
            $subtotalbalance =
            $subtotalunserved = 0;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($group, '110', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '4px');
        $str .= $this->reporter->col('', '500', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'B', '10', '', '', '4px');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($display, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($barcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($subcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($partno, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($date, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($order, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($served, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($bal, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($unservedamt, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      if ($typeofreport == 'client') {
        $item = $data->clientname;
      } else {
        $item = $data->itemname;
      }
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->housegem_header($config);
        $page = $page + $count;
      }

      $totalorder += $order;
      $totalserved += $served;
      $totalbalance += $bal;
      $totalunserved += $unservedamt;
      $totalprice += $amt;

      $subtotalorder += $order;
      $subtotalserved += $served;
      $subtotalbalance += $bal;
      $subtotalprice += $amt;
      $subtotalunserved += $unservedamt;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUB TOTAL', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($subtotalorder, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($subtotalserved, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($subtotalbalance, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($subtotalprice, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($subtotalunserved, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->endrow();
    $subtotalorder =
      $subtotalserved =
      $subtotalbalance =
      $subtotalunserved = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '500', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalorder, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totalserved, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totalbalance, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalprice, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totalunserved, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '4px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function roosevelt_qry($config)
  {
    // QUERY
    $companyid     = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $category  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $agent       = $config['params']['dataparams']['agent'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $posttype = isset($config['params']['dataparams']['posttype']) ? $config['params']['dataparams']['posttype'] : '';
    $area = $config['params']['dataparams']['area'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $filter = "";
    $filter1 = "";
    $order = "";
    $datefilter = "";

    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }

    if ($groupid) {
      $filter = $filter . " and item.groupid='$groupid'";
    }

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($brandid != "") {
      $filter = $filter . " and item.brand='$brandid'";
    }

    if ($classid != "") {
      $filter = $filter . " and item.class='$classid'";
    }

    if ($center != "") {
      $filter .= " and transnum.center='$center'";
    }

    if ($area != "") {
      $filter .= " and client.area='" . $area . "'";
    }

    $order = "";
    if ($typeofreport == 'client') {
      if ($config['params']['dataparams']['reporttype'] == 0) { //summarized
        $order = "cgrp, clientname";
      } else { //detailed
        $order = "area, cgrp";
      }
    } else { //item
      if ($config['params']['dataparams']['reporttype'] == 0) { //summarized
        $order = "itemname";
      } else { //detailed
        $order = "area,itemname";
      }
    }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    switch ($config['params']['dataparams']['reporttype']) {
      case '0': //Summarized
        // $query = "select cgrp, igrp, clientname, itemname, groupid, brand, class, sum(qa) as qa,
        //       sum(qty) as qty, sum(unserved) as unserved, sum(unservedamt) as unservedamt, uom, itemid,
        //       barcode, subcode, partno, category, subcatname from (
        //         select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
        //           client.clientname, item.itemname, item.groupid, item.brand, item.class, stock.qa,stock.iss as qty,
        //           (stock.iss-stock.qa) as unserved, (stock.iss-stock.qa)*stock.amt as unservedamt, item.uom, item.itemid,
        //           item.barcode,'' as subcode,'' as partno, cat.name as category, subcat.name as subcatname
        //         from ((sohead as head left join sostock as stock on stock.trno=head.trno)
        //           left join item on item.itemid=stock.itemid)
        //           left join client on client.client=head.client 
        //           left join client as agent on agent.client=head.agent
        //           left join transnum on transnum.trno=head.trno          
        //           left join itemcategory as cat on cat.line = item.category
        //           left join itemsubcategory as subcat on subcat.line = item.subcat
        //         where stock.void=0 and (stock.iss-stock.qa)>0   and date(head.dateid) between '$start' and '$end' " . $filter . '' . $datefilter . "
        //         union all
        //         select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
        //           client.clientname, item.itemname, item.groupid, item.brand, item.class, stock.qa,stock.iss as qty,
        //           (stock.iss-stock.qa) as unserved, (stock.iss-stock.qa)*stock.amt as unservedamt, item.uom, item.itemid,
        //           item.barcode,'' as subcode,'' as partno, cat.name as category, subcat.name as subcatname
        //         from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
        //           left join item on item.itemid=stock.itemid)
        //           left join client on client.client=head.client 
        //           left join client as agent on agent.client=head.agent
        //           left join transnum on transnum.trno=head.trno
        //           left join itemcategory as cat on cat.line = item.category
        //           left join itemsubcategory as subcat on subcat.line = item.subcat
        //         where stock.void=0 and (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . '' . $datefilter . " and item.isofficesupplies= 0 ) as t
        //         group by cgrp, igrp, clientname, itemname, groupid, brand, class, uom, itemid, barcode, subcode, partno, category, subcatname";

        // break;


        if ($typeofreport == 'client') {
          $query = "  select cgrp,clientname, sum(unservedamt) as unservedamt,
                              sum(servedamt) as servedamt,
                              sum(totalamt) as totalamt,
                              sum(cancelamt) as cancelamt from (
                     select client.clientname as cgrp,
                              client.clientname,
                              sum((stock.iss-stock.qa)*stock.amt) as unservedamt,
                              sum(stock.qa*stock.amt) as servedamt,
                              sum(stock.iss*stock.amt) as totalamt,
                              sum(if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0)) as cancelamt
                              from ((sohead as head left join sostock as stock on stock.trno=head.trno)
                              left join item on item.itemid=stock.itemid)
                              left join client on client.client=head.client
                              left join client as agent on agent.client=head.agent
                              left join transnum on transnum.trno=head.trno
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat
                              where (stock.iss-stock.qa)>0  and date(head.dateid) between '$start' and '$end' " . $filter . '' . $datefilter . "
                              group by client.clientname, client.clientname

                              union all
                              select client.clientname as cgrp, client.clientname,
                              sum((stock.iss-stock.qa)*stock.amt) as unservedamt,
                              sum(stock.qa*stock.amt) as servedamt,
                              sum(stock.iss*stock.amt) as totalamt,
                              sum(if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0)) as cancelamt
                              from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
                              left join item on item.itemid=stock.itemid)
                              left join client on client.client=head.client
                              left join client as agent on agent.client=head.agent
                              left join transnum on transnum.trno=head.trno
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat
                              where (stock.iss-stock.qa)>0  and date(head.dateid) between '$start' and '$end' " . $filter . '' . $datefilter . " and item.isofficesupplies= 0
                              group by client.clientname, client.clientname  ) as xd
                          group by cgrp,clientname ";
        } else { //item
          $query = "select igrp,itemname,sum(unservedamt) as unservedamt,
                sum(servedamt) as servedamt,
                sum(totalamt) as totalamt,
                sum(cancelamt) as cancelamt
                from (
                select concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                              item.itemname,
                              sum((stock.iss-stock.qa)*stock.amt) as unservedamt,
                              sum(stock.qa*stock.amt) as servedamt,
                              sum(stock.iss*stock.amt) as totalamt,
                              sum(if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0)) as cancelamt
                              from ((sohead as head left join sostock as stock on stock.trno=head.trno)
                              left join item on item.itemid=stock.itemid)
                              left join client on client.client=head.client
                              left join client as agent on agent.client=head.agent
                              left join transnum on transnum.trno=head.trno
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat
                              where (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . '' . $datefilter . "
                              group by igrp,  item.itemname
                              union all
                              select   concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,  item.itemname,
                                sum((stock.iss-stock.qa)*stock.amt) as unservedamt,
                              sum(stock.qa*stock.amt) as servedamt,
                              sum(stock.iss*stock.amt) as totalamt,
                              sum(if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0)) as cancelamt
                              from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
                              left join item on item.itemid=stock.itemid)
                              left join client on client.client=head.client 
                              left join client as agent on agent.client=head.agent
                              left join transnum on transnum.trno=head.trno
                              left join itemcategory as cat on cat.line = item.category
                              left join itemsubcategory as subcat on subcat.line = item.subcat
                              where (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . '' . $datefilter . "
                              and item.isofficesupplies= 0
                              group by igrp,  item.itemname) as xy
                            group by igrp,itemname";
        }
        break;
      default: //Detailed

        if ($typeofreport == 'client') {
          $query = "select client.clientname as cgrp, head.docno,
              client.clientname,
              date(head.dateid) as dateid,
              sum((stock.iss-stock.qa)*stock.amt) as unservedamt,
              sum(stock.qa*stock.amt) as servedamt,
              sum(stock.iss*stock.amt) as totalamt,
              if(client.area ='', 'No Area', client.area) as area,
              sum(if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0)) as cancelamt
              from ((sohead as head left join sostock as stock on stock.trno=head.trno)
              left join item on item.itemid=stock.itemid)
              left join client on client.client=head.client 
              left join client as agent on agent.client=head.agent
              left join transnum on transnum.trno=head.trno
              left join itemcategory as cat on cat.line = item.category
              left join itemsubcategory as subcat on subcat.line = item.subcat
              where (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . " " . $datefilter . "
              group by client.clientname, head.docno,client.clientname,date(head.dateid),client.area

              union all
              select client.clientname as cgrp, head.docno, client.clientname, 
              date(head.dateid) as dateid,
              sum((stock.iss-stock.qa)*stock.amt) as unservedamt,
              sum(stock.qa*stock.amt) as servedamt,
              sum(stock.iss*stock.amt) as totalamt,
              if(client.area ='', 'No Area', client.area) as area,
              sum(if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0)) as cancelamt
              from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
              left join item on item.itemid=stock.itemid)
              left join client on client.client=head.client
              left join client as agent on agent.client=head.agent
              left join transnum on transnum.trno=head.trno
              left join itemcategory as cat on cat.line = item.category
              left join itemsubcategory as subcat on subcat.line = item.subcat
              where (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . " " . $datefilter . "
              and transnum.center='001'  and item.isofficesupplies= 0
              group by client.clientname, head.docno,client.clientname,date(head.dateid),client.area ";
        } else {
          $query = "select
              concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
              client.clientname, item.itemname,
              date(head.dateid) as dateid, stock.qa,
              (stock.iss-stock.qa)*stock.amt as unservedamt, stock.qa*stock.amt as servedamt,stock.iss*stock.amt as totalamt,
              if(client.area ='', 'No Area', client.area) as area,
              if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0) as cancelamt
              from ((sohead as head left join sostock as stock on stock.trno=head.trno)
              left join item on item.itemid=stock.itemid)
              left join client on client.client=head.client 
              left join client as agent on agent.client=head.agent
              left join transnum on transnum.trno=head.trno          
              left join itemcategory as cat on cat.line = item.category
              left join itemsubcategory as subcat on subcat.line = item.subcat
              where (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . " " . $datefilter . "
              union all
              select
              concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp, head.docno,
              client.clientname, item.itemname,
              date(head.dateid) as dateid, stock.qa,
              (stock.iss-stock.qa)*stock.amt as unservedamt, stock.qa*stock.amt as servedamt,stock.iss*stock.amt as totalamt,
              if(client.area ='', 'No Area', client.area) as area,
              if( stock.void=1, (stock.iss-stock.qa)*stock.amt, 0) as cancelamt
              from ((hsohead as head left join hsostock as stock on stock.trno=head.trno)
              left join item on item.itemid=stock.itemid)
              left join client on client.client=head.client 
              left join client as agent on agent.client=head.agent
              left join transnum on transnum.trno=head.trno
              left join itemcategory as cat on cat.line = item.category
              left join itemsubcategory as subcat on subcat.line = item.subcat
              where (stock.iss-stock.qa)>0 and date(head.dateid) between '$start' and '$end' " . $filter . " " . $datefilter . " and item.isofficesupplies= 0 ";
        }
    }

    $query .= " order by $order ";
    return $this->coreFunctions->opentable($query);
  }


  // private function default_displayHeader_roosevelt($config)
  private function default_displayHeader_roosevelt($config, $isContinuation = false, $continuedArea = null)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $classic    = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $agent       = $config['params']['dataparams']['agent'];
    $agentname       = $config['params']['dataparams']['agentname'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $filtercenter       = $config['params']['dataparams']['dcentername'];
    $str = '';
    // if ($config['params']['dataparams']['reporttype'] == '0') {
    //   $layoutsize = '800';
    // } else {
    $layoutsize = '1000';
    // }
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "12";
    $border = "1px solid";
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PENDING SALES ORDERS', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $startdate = $start;
    $startt = new DateTime($startdate);
    $start = $startt->format('m/d/Y');

    $enddate = $end;
    $endd = new DateTime($enddate);
    $end = $endd->format('m/d/Y');

    $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $cus = $client == '' ? 'ALL' : $client;
    $item = $barcode == '' ? 'ALL' : $barcode;
    $group = $groupid == '' ? 'ALL' : $groupid;
    $brand = $brandid == '' ? 'ALL' : $brandid;
    $class = $classic == '' ? 'ALL' : $classic;

    $sorty = strtoupper($typeofreport);

    $sortyname = '';
    if ($sorty == 'CLIENT') {
      $sortyname = 'CUSTOMER';
    } else {
      $sortyname = 'ITEM';
    }

    $age = $agent == '' ? 'ALL' : $agent . ' ~ ' . $agentname;
    $filtercenter = $filtercenter == '' ? 'ALL' : $filtercenter;

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Customer : ' . strtoupper($cus), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Item : ' . strtoupper($item), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Group : ' . strtoupper($group), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brand), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . strtoupper($class), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Sort By : ' . strtoupper($sorty), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Agent : ' . strtoupper($age), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($config['params']['dataparams']['reporttype'] == '0') { //summary
      // $str .= $this->reporter->col('DATE', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('OF #', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TBL', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($sortyname, '550', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col('UNSERVED AMOUNT', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SERVED AMOUNT', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CANCEL AMOUNT', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('TOTAL ORDER AMOUNT', '110', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else { //detailed
      // $str .= $this->reporter->col($sorty, '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('BARCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('SUBCODE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('PARTNO', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('DATE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('ORDERED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('SERVED', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      // $str .= $this->reporter->col('UOM', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col('DATE', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('OF #', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, 'TBL', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($sortyname, '290', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col('UNSERVED AMOUNT', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('SERVED AMOUNT', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CANCEL AMOUNT', '110', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('TOTAL ORDER AMOUNT', '110', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      // $str .= $this->reporter->endtable();

      if (!$isContinuation) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '150', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '290', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
        $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');

        $str .= $this->reporter->endrow();
      }
    }

    return $str;
  }
  public function reportDefaultLayout_Customer_roosevelt($config)
  {
    $result = $this->roosevelt_qry($config);

    $count = 35;
    $page = 35;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_roosevelt($config);

    $item = null;
    $unservedamt = 0;
    $servedamt = 0;
    $cancelamt = 0;
    $totalamt = 0;
    $grand_unserved = 0;
    $grand_served = 0;
    $grand_cancel = 0;
    $grand_total = 0;

    $isContinuation = false;
    $areas = '';
    foreach ($result as $key => $data) {

      // $served = $data->qa;
      $unserved = $data->unservedamt;
      $served = $data->servedamt;
      $cancel = $data->cancelamt;
      $total = $data->totalamt;

      if ($areas != $data->area) {

        // I-print muna ang area total ng previous area (kung meron)
        if ($areas != '') {

          $unservedamts = $unservedamt;
          $servedamts = $servedamt;
          $cancelamts = $cancelamt;
          $totalamts = $totalamt;

          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col('AREA TOTAL', '290', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($unservedamts != 0 ? number_format($unservedamts, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($servedamts != 0 ? number_format($servedamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($cancelamts != 0 ? number_format($cancelamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($totalamts != 0 ? number_format($totalamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->addline();

          // add area totals to grand total

          $grand_unserved += $unservedamt;
          $grand_served += $servedamt;
          $grand_cancel += $cancelamt;
          $grand_total += $totalamt;


          // reset area totals
          $unservedamt = 0;
          $servedamt = 0;
          $cancelamt = 0;
          $totalamt = 0;



          //space bago magheader
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '150', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '290', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');

          $str .= $this->reporter->endrow();
        }


        // I-print ang bagong area header
        $areas = $data->area;
        // $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'TLB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col(strtoupper($areas), '290', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TBR', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
      }


      $unserveds = $unserved;
      $serveds = $served;
      $cancels = $cancel;
      $totals = $total;
      // I-print ang bawat client row sa loob ng area
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '110', null, false,  $border, 'L', 'CT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($data->docno, '150', null, false,  $border, 'L', 'CT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col('', '10', null, false,  $border, 'L', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($data->clientname, '290', null, false,  $border, '', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($unserveds != 0 ? number_format($unserveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($serveds != 0 ? number_format($serveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($cancels != 0 ? number_format($cancels, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($totals != 0 ? number_format($totals, 2) : '', '110', null, false,  $border, 'LR', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->endrow();

      // accumulate area totals

      $unservedamt += $unserved;
      $servedamt += $served;
      $cancelamt += $cancel;
      $totalamt += $total;

      // pagination  (optional)
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '150', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '10', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '290', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->endrow();

        $isContinuation = true;
        $continuedArea = $areas;
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->page_break();
        // $str .= $this->default_displayHeader_roosevelt($config);
        $str .= $this->default_displayHeader_roosevelt($config, true, $continuedArea);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    // Pag natapos ang loop, i-print ang last area total
    if ($areas != '') {

      $unservedamts = $unservedamt;
      $servedamts = $servedamt;
      $cancelamts = $cancelamt;
      $totalamts = $totalamt;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '110', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '150', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '10', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col('AREA TOTAL', '290', null, true, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($unservedamts != 0 ? number_format($unservedamts, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($servedamts != 0 ? number_format($servedamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($cancelamts != 0 ? number_format($cancelamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($totalamts != 0 ? number_format($totalamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->endrow();

      // add last area to grand total
      $grand_unserved += $unservedamt;
      $grand_served += $servedamt;
      $grand_cancel += $cancelamt;
      $grand_total += $totalamt;
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '150', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '290', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');

    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('', '150', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('', '10', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('GRAND TOTAL', '290', null, true, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_unserved != 0 ? number_format($grand_unserved, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_served != 0 ? number_format($grand_served, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_cancel != 0 ? number_format($grand_cancel, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_total != 0 ? number_format($grand_total, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_item_roosevelt($config)
  {
    $result = $this->roosevelt_qry($config);


    $count = 39;
    $page = 40;
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_roosevelt($config);

    $item = null;
    $unservedamt = 0;
    $servedamt = 0;
    $cancelamt = 0;
    $totalamt = 0;
    $grand_unserved = 0;
    $grand_served = 0;
    $grand_cancel = 0;
    $grand_total = 0;

    $areas = '';
    $isContinuation = false;
    foreach ($result as $key => $data) {

      // $served = $data->qa;
      $unserved = $data->unservedamt;
      $served = $data->servedamt;
      $cancel = $data->cancelamt;
      $total = $data->totalamt;

      if ($areas != $data->area) {

        // I-print muna ang area total ng previous area (kung meron)
        if ($areas != '') {

          $unservedamts = $unservedamt;
          $servedamts = $servedamt;
          $cancelamts = $cancelamt;
          $totalamts = $totalamt;

          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col('AREA TOTAL', '290', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($unservedamts != 0 ? number_format($unservedamts, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($servedamts != 0 ? number_format($servedamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($cancelamts != 0 ? number_format($cancelamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->col($totalamts != 0 ? number_format($totalamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->addline();

          // add area totals to grand total

          $grand_unserved += $unservedamt;
          $grand_served += $servedamt;
          $grand_cancel += $cancelamt;
          $grand_total += $totalamt;


          // reset area totals
          $unservedamt = 0;
          $servedamt = 0;
          $cancelamt = 0;
          $totalamt = 0;


          //space bago magheader
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '150', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '290', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');
          $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '5', '', '', '', '2px');

          $str .= $this->reporter->endrow();
        }


        // I-print ang bagong area header
        $areas = $data->area;
        // $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'TLB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col(strtoupper($areas), '290', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TBR', 'L', $font, $fontsize + 1, 'B', '', '', '5px');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
      }


      $unserveds = $unserved;
      $serveds = $served;
      $cancels = $cancel;
      $totals = $total;
      // I-print ang bawat client row sa loob ng area
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '110', null, false,  $border, 'L', 'CT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($data->docno, '150', null, false,  $border, 'L', 'CT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col('', '10', null, false,  $border, 'L', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($data->itemname, '290', null, false,  $border, '', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($unserveds != 0 ? number_format($unserveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($serveds != 0 ? number_format($serveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($cancels != 0 ? number_format($cancels, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($totals != 0 ? number_format($totals, 2) : '', '110', null, false,  $border, 'LR', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->endrow();

      // accumulate area totals

      $unservedamt += $unserved;
      $servedamt += $served;
      $cancelamt += $cancel;
      $totalamt += $total;
      // $area_back += $data->backtotal;

      // pagination  (optional)
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '150', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '10', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '290', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '2px');
        $str .= $this->reporter->endrow();

        $isContinuation = true;
        $continuedArea = $areas;
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->page_break();
        // $str .= $this->default_displayHeader_roosevelt($config);
        $str .= $this->default_displayHeader_roosevelt($config, true, $continuedArea);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    // Pag natapos ang loop, i-print ang last area total
    if ($areas != '') {

      $unservedamts = $unservedamt;
      $servedamts = $servedamt;
      $cancelamts = $cancelamt;
      $totalamts = $totalamt;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '110', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '150', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '10', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col('AREA TOTAL', '290', null, true, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($unservedamts != 0 ? number_format($unservedamts, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($servedamts != 0 ? number_format($servedamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($cancelamts != 0 ? number_format($cancelamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->col($totalamts != 0 ? number_format($totalamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
      $str .= $this->reporter->endrow();

      // add last area to grand total
      $grand_unserved += $unservedamt;
      $grand_served += $servedamt;
      $grand_cancel += $cancelamt;
      $grand_total += $totalamt;
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '150', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '10', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '290', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false,  '', '',  'L', $font, '4', '', '', '', '');

    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('', '150', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('', '10', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('GRAND TOTAL', '290', null, true, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_unserved != 0 ? number_format($grand_unserved, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_served != 0 ? number_format($grand_served, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_cancel != 0 ? number_format($grand_cancel, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($grand_total != 0 ? number_format($grand_total, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summ_Customer_roosevelt($config)
  {
    $result = $this->roosevelt_qry($config);
    $count = 49;
    $page = 49;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_roosevelt($config);

    $unservedamt = 0;
    $servedamt = 0;
    $cancelamt = 0;
    $totalamt = 0;

    foreach ($result as $key => $data) {
      $unserved = $data->unservedamt;
      $served = $data->servedamt;
      $cancel = $data->cancelamt;
      $total = $data->totalamt;
      $unserveds = $unserved;
      $serveds = $served;
      $cancels = $cancel;
      $totals = $total;


      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '10', null, false,  $border, 'L', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($data->clientname, '550', null, false,  $border, '', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($unserveds != 0 ? number_format($unserveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($serveds != 0 ? number_format($serveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($cancels != 0 ? number_format($cancels, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($totals != 0 ? number_format($totals, 2) : '', '110', null, false,  $border, 'LR', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->endrow();



      $unservedamt += $unserved;
      $servedamt += $served;
      $cancelamt += $cancel;
      $totalamt += $total;

      // pagination  (optional)
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '550', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader_roosevelt($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $unservedamts = $unservedamt;
    $servedamts = $servedamt;
    $cancelamts = $cancelamt;
    $totalamts = $totalamt;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('GRAND TOTAL', '550', null, true, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($unservedamts != 0 ? number_format($unservedamts, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($servedamts != 0 ? number_format($servedamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($cancelamts != 0 ? number_format($cancelamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($totalamts != 0 ? number_format($totalamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summ_ITEM_roosevelt($config)
  {
    $result = $this->roosevelt_qry($config);
    $count = 49;
    $page = 49;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_roosevelt($config);

    $unservedamt = 0;
    $servedamt = 0;
    $cancelamt = 0;
    $totalamt = 0;

    foreach ($result as $key => $data) {
      $unserved = $data->unservedamt;
      $served = $data->servedamt;
      $cancel = $data->cancelamt;
      $total = $data->totalamt;
      $unserveds = $unserved;
      $serveds = $served;
      $cancels = $cancel;
      $totals = $total;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '10', null, false,  $border, 'L', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($data->itemname, '550', null, false,  $border, '', 'LT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($unserveds != 0 ? number_format($unserveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($serveds != 0 ? number_format($serveds, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($cancels != 0 ? number_format($cancels, 2) : '', '110', null, false,  $border, 'L', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->col($totals != 0 ? number_format($totals, 2) : '', '110', null, false,  $border, 'LR', 'RT', $font, $fontsize, '', '', '', '2px');
      $str .= $this->reporter->endrow();


      $unservedamt += $unserved;
      $servedamt += $served;
      $cancelamt += $cancel;
      $totalamt += $total;

      // pagination  (optional)
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '550', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '110', null, false,  $border, 'T', '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader_roosevelt($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $unservedamts = $unservedamt;
    $servedamts = $servedamt;
    $cancelamts = $cancelamt;
    $totalamts = $totalamt;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, true, $border, 'T', 'L', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col('GRAND TOTAL', '550', null, true, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($unservedamts != 0 ? number_format($unservedamts, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($servedamts != 0 ? number_format($servedamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($cancelamts != 0 ? number_format($cancelamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->col($totalamts != 0 ? number_format($totalamt, 2) : '', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class
