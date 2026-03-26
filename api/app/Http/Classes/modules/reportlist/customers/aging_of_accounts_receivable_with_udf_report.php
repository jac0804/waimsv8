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

class aging_of_accounts_receivable_with_udf_report
{
  public $modulename = 'Aging Of Accounts Receivable with UDF Report';
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

    $fields = ['radioprint', 'start', 'dclientname', 'dcentername'];

    switch ($companyid) {
      case 39: //cbbsi
        array_push($fields, 'agentname');
        $col1 = $this->fieldClass->create($fields);
        break;

      default:
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.label', 'As of');
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        break;
    }

    $fields = ['radioposttype', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioposttype.options', array(
      ['label' => 'Unposted', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Posted', 'value' => '1', 'color' => 'orange'],
      ['label' => 'All', 'value' => '2', 'color' => 'orange'],
    ));

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      date(now()) as start,
      '' as center,
      '' as client,
      '0' as posttype,
      '0' as reporttype,
      '' as dclientname,
      '' as dcentername,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      '' as agentid
    ";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $reporttype   = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0': // SUMMARIZED
        switch ($companyid) {
          case 39: //cbbsi
            $result = $this->CBBSI_LAYOUT_SUMMARIZED($config, $result);
            break;
          default:
            $result = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result); // POSTED
            break;
        }
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result); // POSTED
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 39: //cbbsi
        $query = $this->report_CBBSI_QUERY($config);
        break;

      default:
        $query = $this->reportDefault_QUERY($config);
        break;
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $this->reportplotting($config, $result);
  }

  public function reportDefault_QUERY($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $asof   = $config['params']['dataparams']['start'];
    $companyid   = $config['params']['companyid'];


    $filter = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    switch ($reporttype) {
      case '0': // SUMMARIZED
        switch ($posttype) {
            // UNPOSTED
          case '0':
            $qry = "select clientname, name, elapse, sum(balance) as balance,crlimit,agent,terms
              from (
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
              detail.cr as balance,head.yourref
              from lahead as head left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('SJ','AR') and head.dateid<='$asof' $filter) as x
              group by clientname, name, elapse ,crlimit,agent,terms
              order by clientname, name";
            break;

          case '1': // POSTED
            $qry = "select * FROM(
                select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
                date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref
                from (arledger as detail left join client on client.clientid=detail.clientid)
                left join cntnum on cntnum.trno=detail.trno
                left join glhead as head on head.trno=detail.trno
                where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
                UNION ALL
                SELECT 'z' AS tr, '' AS clientname,'' AS NAME, '' AS dateid, '' AS docno, '' AS elapse,'' AS balance, '' AS yourref,'' as agent,'' as terms,'' as crlimit
                ) AS X ORDER BY  tr, clientname, dateid, docno";
            break;

          default: // ALL
            $qry = "select tr, clientname, name, elapse, balance,agent,terms,crlimit
          from (
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          detail.cr as balance,head.yourref
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('SJ','AR') and head.dateid<='$asof' $filter) as x
          union all
          select tr, clientname, name, elapse, balance,agent,terms,crlimit from(
          select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
          union all
          select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref,'' as agent,'' as terms,'' as crlimit
          ) as x
          order by  tr, clientname, name";
            break;
        }

        break;
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          detail.cr as balance,head.yourref, head.cur
          from lahead as head left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=head.client
          left join coa on coa.acnoid=detail.acnoid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('SJ','AR') and head.dateid<='$asof' $filter
          order by clientname, dateid, docno";
            break;

          case '1': // POSTED
            $qry = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref, head.cur
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
          order by client.clientname, detail.dateid, detail.docno";
            break;

          default: // ALL
            $qry = "select cntnum.center, 'u' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          detail.cr as balance,
          head.yourref, head.cur
          from lahead as head left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=head.client
          left join coa on coa.acnoid=detail.acnoid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('SJ','AR') and head.dateid<='$asof' $filter
          union all
          select cntnum.center, 'p' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
          head.yourref, head.cur
          from arledger as detail left join client on client.clientid=detail.clientid
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
          order by clientname, dateid, docno";
            break;
        }

        break;
    } //end switch

    return $qry;
  }
  public function report_CBBSI_QUERY($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $asof   = $config['params']['dataparams']['start'];
    $companyid   = $config['params']['companyid'];
    $agentname  = $config['params']['dataparams']['agent'];

    $filter = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }
    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($agentname != '') {
      $agentname = "and client.agent = '$agentname'";
    } else {
      $agentname = '';
    }
    switch ($reporttype) {
      case '0': // SUMMARIZED

        switch ($posttype) {

            //UNPOSTED
          case '0':
            $qry = "select clientname, name, sum(balance) as balance, elapse, bstyle, code, agent, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias, db, cr from(
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, date(head.dateid) as dateid, head.docno,
              datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr),0) as totalbal, client.groupid as clgrp, left(coa.alias,2) as alias,
              detail.db, detail.cr, ifnull((detail.db - detail.cr),0) as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit
              from lahead as head left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias,2) in ('cr', 'ar') and detail.refx=0 and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias
              union all
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, date(head.dateid) as dateid, head.docno,
              datediff(now(), head.dateid) as elapse, stock.ext as totalbal, client.groupid as clrgrp, left(coa.alias,2) as alias,
              0 as db, 0 as cr, stock.ext as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit
              from lahead as head left join lastock as stock on stock.trno=head.trno
              left join ladetail as detail on detail.trno=head.trno
              left join coa on coa.acnoid=detail.acnoid
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('CM', 'SK') and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, client.bstyle, client.client, client.clientname,
              client.agent, client.terms, client.crlimit, client.groupid, stock.ext, coa.alias
            ) as t group by clientname, elapse, name, bstyle, code, agent, terms, crlimit, clgrp, alias, db, cr";
            break;
            //POSTED
          case '1':
            $qry = "select tr, clientname, name, sum(balance) as balance,elapse,crlimit,sum(totalbal) as totalbal,bstyle,clgrp,code,terms,alias,db,cr
              from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,client.crlimit,
              ifnull((detail.db - detail.cr),0) as totalbal,client.bstyle,client.groupid as clgrp,client.client as code,client.terms,left(coa.alias,2) as alias,detail.db as db,detail.cr as cr,
              (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
              from (arledger as detail left join client on client.clientid=detail.clientid)
              left join cntnum on cntnum.trno=detail.trno
              left join glhead as head on head.trno=detail.trno
              left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
              left join coa on coa.acnoid=gdetail.acnoid
              where detail.bal<>0 and left(coa.alias,2) in ('cr','ar') and detail.dateid<='$asof' $filter $agentname
              union all
              select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref,
               null as deldate,'' as crlimit, 0 as totalbal,'' as bstyle,'' as clgrp,'' as code,'' as terms,'' as alias,'' as db, '' as cr
              ) as x
              group by tr, clientname, name,elapse,crlimit,bstyle,code,clgrp,terms,tr,alias,db,cr
              order by tr, clientname";
            break;
            //ALL
          default:
            $qry = "select clientname, name, sum(balance) as balance, elapse, bstyle, code, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias, db, cr
              from(
                select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, date(head.dateid) as dateid, head.docno,
                datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr),0) as totalbal, client.groupid as clgrp, left(coa.alias,2) as alias,
                detail.db, detail.cr, ifnull((detail.db - detail.cr),0) as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit
                from lahead as head left join ladetail as detail on detail.trno=head.trno
                left join client on client.client=head.client
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias,2) in ('cr', 'ar') and detail.refx = 0 and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
                group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
                client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr
                union all
                select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, date(head.dateid) as dateid, head.docno,
                datediff(now(), head.dateid) as elapse, stock.ext as totalbal, client.groupid as clgrp, left(coa.alias,2) as alias,
                0 as db, 0 as cr, stock.ext as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit
                from lahead as head left join lastock as stock on stock.trno=head.trno
                left join ladetail as detail on detail.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid
                left join client on client.client=head.client
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('CM', 'SK') and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
                group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
                client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, stock.ext
                union all
                select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, date(head.dateid) as dateid, head.docno,
                datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr),0) as totalbal, client.groupid as clgrp, left(coa.alias,2) as alias,
                detail.db, detail.cr, (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit
                from arledger as detail left join client on client.clientid=detail.clientid
                left join cntnum on cntnum.trno=detail.trno
                left join glhead as head on head.trno=detail.trno
                left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                left join coa on coa.acnoid=gdetail.acnoid
                where detail.bal<>0 and left(coa.alias,2) in ('cr', 'ar') and detail.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
              ) as t group by tr, clientname, name, elapse, crlimit, bstyle, code, clgrp, terms, alias, db, cr
              order by clientname";


            break;
        }

        break;
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select center, tr, clientname, name, dateid, docno, elapse, sum(balance) as balance, yourref, cur from(
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref, head.cur
              from lahead as head left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('SK', 'CM') and head.dateid <= '" . $asof . "' " . $filter . "
              union all
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
              (detail.db - detail.cr) as balance,head.yourref, head.cur
              from lahead as head left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('SJ','AR') and head.dateid<='$asof' $filter
            ) as t group by center, clientname, tr, name, dateid, docno, elapse, yourref, cur
            order by clientname, dateid, docno";
            break;

          case '1': // POSTED
            $qry = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
              (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref, head.cur
              from (arledger as detail left join client on client.clientid=detail.clientid)
              left join cntnum on cntnum.trno=detail.trno
              left join glhead as head on head.trno=detail.trno
              where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
              order by client.clientname, detail.dateid, detail.docno";
            break;

          default: // ALL
            $qry = "select center, tr, clientname, name, dateid, docno, elapse, sum(balance) as balance, yourref, cur from(
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref, head.cur
                from lahead as head left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('SK', 'CM') and head.dateid <= '" . $asof . "' " . $filter . "
              union all
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, (detail.db - detail.cr) as balance, head.yourref, head.cur
                from lahead as head left join ladetail as detail on detail.trno=head.trno
                left join client on client.client=head.client
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('SJ','AR') and head.dateid<='$asof' $filter
              union all
              select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
                date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse, (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance, head.yourref, head.cur
                from arledger as detail left join client on client.clientid=detail.clientid
                left join cntnum on cntnum.trno=detail.trno
                left join glhead as head on head.trno=detail.trno
                where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
              ) as t group by center, tr, clientname, name, dateid, docno, elapse, yourref, cur
              order by clientname, dateid, docno";
            break;
        }

        break;
    } //end switch

    return $qry;
  }
  private function reportDefaultLayout_LAYOUT_SUMMARIZED($params, $data)
  {

    $str = "";
    $layoutsize = '1000';
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = $params['params']['dataparams']['center'];
    $client       = $params['params']['dataparams']['client'];
    $posttype     = $params['params']['dataparams']['posttype'];
    $reporttype   = $params['params']['dataparams']['reporttype'];
    $companyid    = $params['params']['companyid'];
    $postStatus = '';
    $start   = $params['params']['dataparams']['start'];
    $client       = $params['params']['dataparams']['client'];

    switch ($posttype) {
      case '0': // UNPOSTED
        $postStatus = 'Unposted';
        break;

      case '1': // POSTED
        $postStatus = 'Posted';
        break;

      default: // ALL
        $postStatus = 'ALL';
        break;
    }



    $count = 38;
    $page = 40;

    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE  - SUMMARY', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('F j, Y', strtotime($start)), null, null, false, '1px solid ', '', '', 'Century Gothic', '12', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($postStatus), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLIENT NAME', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Agent', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Terms', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Credit Limit', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('0-30 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('120+ days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $gt = 0;

    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subgt = 0;
    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;

    $clientname = '';
    $agent = '';
    $terms = '';
    $crlimit = 0;
    for ($i = 0; $i < count($data); $i++) {
      if ($clientname == '') {
        $clientname = $data[$i]['clientname'];
        $agent = $data[$i]['agent'];
        $terms = $data[$i]['terms'];
        $crlimit = $data[$i]['crlimit'];


        if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $a = $data[$i]['balance'];
          $subtota = $subtota + $a;
          $subgt = $subgt + $data[$i]['balance'];
        }

        if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $b = $data[$i]['balance'];
          $subtotb = $subtotb + $b;
          $subgt = $subgt + $data[$i]['balance'];
        }

        if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $c = $data[$i]['balance'];
          $subtotc = $subtotc + $c;
          $subgt = $subgt + $data[$i]['balance'];
        }

        if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $d = $data[$i]['balance'];
          $subtotd = $subtotd + $d;
          $subgt = $subgt + $data[$i]['balance'];
        }

        if ($data[$i]['elapse'] >= 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $e = $data[$i]['balance'];
          $subtote = $subtote + $e;
          $subgt = $subgt + $data[$i]['balance'];
        }
      } else {
        if ($clientname != $data[$i]['clientname']) {

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($clientname, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($agent, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtota, 2), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->endrow();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;

          $clientname = $data[$i]['clientname'];
          $agent = $data[$i]['agent'];
          $terms = $data[$i]['terms'];
          $crlimit = $data[$i]['crlimit'];
          if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data[$i]['balance'];
            $subtota = $subtota + $a;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data[$i]['balance'];
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data[$i]['balance'];
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data[$i]['balance'];
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] > 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data[$i]['balance'];
            $subtote = $subtote + $e;
            $subgt = $subgt + $data[$i]['balance'];
          }
        } else {
          if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data[$i]['balance'];
            $subtota = $subtota + $a;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data[$i]['balance'];
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data[$i]['balance'];
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data[$i]['balance'];
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data[$i]['balance'];
          }

          if ($data[$i]['elapse'] > 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data[$i]['balance'];
            $subtote = $subtote + $e;
            $subgt = $subgt + $data[$i]['balance'];
          }
        } //end if
      } //end if clientname == ''
      $tota = $tota + $a;
      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $gt = $gt + $data[$i]['balance'];
    } // end for loop
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '110px', null, false, '1px dotted', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '80px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '110px', null, false, '1px dotted ', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '110px', null, false, '1px dotted ', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '110px', null, false, '1px dotted ', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '110px', null, false, '1px dotted ', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '110px', null, false, '1px dotted ', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($gt, 2), '110px', null, false, '1px dotted ', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end function
  private function CBBSI_LAYOUT_SUMMARIZED($params, $data)
  {
    $str = "";
    $layoutsize = '1400';
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = $params['params']['dataparams']['center'];
    $client       = $params['params']['dataparams']['client'];
    $posttype     = $params['params']['dataparams']['posttype'];
    $reporttype   = $params['params']['dataparams']['reporttype'];
    $companyid    = $params['params']['companyid'];
    $agentname  = $params['params']['dataparams']['agentname'];
    $postStatus = '';
    $start   = $params['params']['dataparams']['start'];
    $client       = $params['params']['dataparams']['client'];

    switch ($posttype) {
      case '0': // UNPOSTED
        $postStatus = 'Unposted';
        break;

      case '1': // POSTED
        $postStatus = 'Posted';
        break;

      default: // ALL
        $postStatus = 'ALL';
        break;
    }

    if ($agentname != '') {
      $agentname  = $params['params']['dataparams']['agentname'];
    } else {
      $agentname = 'ALL';
    }

    $count = 38;
    $page = 40;

    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE WITH UDF- SUMMARY', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('F j, Y', strtotime($start)), null, null, false, '1px solid ', '', '', 'Century Gothic', '12', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($postStatus), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('m-d-Y', strtotime($start)), null, null, false, '1px solid ', '', '', 'Century Gothic', '12', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, null, null, false, '1px solid ', '', '', 'Century Gothic', '12', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLIENT CODE', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('BUSINESS STYLE', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('GROUP', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TERMS', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CREDIT LIMIT', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('BOOK BAL', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('UDF', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('TOTAL BALANCE', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('0-30 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('121-150 days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('150+ days', '110px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $a = $b = $c = $d = $e = $f = 0;
    $tota = $totb = $totc = $totd = $tote = $totf = 0;
    $gt = 0;
    $subtota = $subtotb = $subtotc = $subtotd = $subtote = $subtotf = 0;
    $subgt = 0;

    $clientname = '';
    $group = '';
    $terms = '';
    $code = '';
    $agent = '';
    $bstyle = '';
    $totalbal = $crlimit = $tb = $bookbal = $totaludf = $udf = $totalbookbal = $tbookbal = $totalbalnce = 0;
    $lastclientprinted = '';
    for ($i = 0; $i < count($data); $i++) {
      if ($clientname == '') {
        $clientname = $data[$i]['name'];
        $terms = $data[$i]['terms'];
        $crlimit = $data[$i]['crlimit'];
        $code = $data[$i]['code'];
        $bstyle = $data[$i]['bstyle'];
        $group = $data[$i]['clgrp'];

        if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
          $a = $b = $c = $d = $e = 0;
          $a = $data[$i]['balance'];
          $subtota += $a;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
          $a = $b = $c = $d = $e = 0;
          $b = $data[$i]['balance'];
          $subtotb += $b;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
          $a = $b = $c = $d = $e = 0;
          $c = $data[$i]['balance'];
          $subtotc += $c;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
          $a = $b = $c = $d = $e = 0;
          $d = $data[$i]['balance'];
          $subtotd += $d;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
          $a = $b = $c = $d = $e = 0;
          $e = $data[$i]['balance'];
          $subtote += $e;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 150) {
          $a = $b = $c = $d = $e = $f = 0;
          $f = $data[$i]['balance'];
          $subtotf += $f;
          $subgt += $data[$i]['balance'];
        }
      } else {
        if ($clientname != $data[$i]['name']) {
          $lastclientprinted = $clientname;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($code, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($clientname, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($bstyle, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($group, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '100px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $bookbal = $totalbal + $udf;
          $str .= $this->reporter->col(number_format($bookbal, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($udf, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($totalbal, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtota, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotf, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->endrow();
          $subtota = $subtotb = $subtotc = $subtotd = $subtote = $subtotf = $subgt = 0;

          $clientname = $data[$i]['name'];
          $totalbal =  $data[$i]['balance'];
          $terms = $data[$i]['terms'];
          $crlimit = $data[$i]['crlimit'] == '' ? 0 : $data[$i]['crlimit'];
          $code = $data[$i]['code'];
          $bstyle = $data[$i]['bstyle'];
          $group = $data[$i]['clgrp'];

          $totalbookbal += $bookbal;
          $totaludf += $udf;
          $bookbal = $udf = 0;
          if ($data[$i]['alias'] == 'CR') {
            $udf =  $data[$i]['db'] - $data[$i]['cr'];
          }
          if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
            $a = $b = $c = $d = $e = $f = 0;
            $a = $data[$i]['balance'];
            $subtota += $a;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $a = $b = $c = $d = $e = $f = 0;
            $b = $data[$i]['balance'];
            $subtotb += $b;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $a = $b = $c = $d = $e = $f = 0;
            $c = $data[$i]['balance'];
            $subtotc += $c;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
            $a = $b = $c = $d = $e = $f = 0;
            $d = $data[$i]['balance'];
            $subtotd += $d;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
            $a = $b = $c = $d = $e = $f = 0;
            $e = $data[$i]['balance'];
            $subtote += $e;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] > 150) {
            $a = $b = $c = $d = $e = $f = 0;
            $f = $data[$i]['balance'];
            $subtotf += $f;
            $subgt += $data[$i]['balance'];
          }
        } else {
          if ($clientname == $data[$i]['name']) {
            $totalbal += $data[$i]['balance'];
            if ($data[$i]['alias'] == 'CR') {
              $udf =  $data[$i]['db'] - $data[$i]['cr'];
            }
          }
          if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
            $a = $b = $c = $d = $e = $f = 0;
            $a = $data[$i]['balance'];
            $subtota += $a;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $a = $b = $c = $d = $e = $f = 0;
            $b = $data[$i]['balance'];
            $subtotb += $b;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $a = $b = $c = $d = $e = $f = 0;
            $c = $data[$i]['balance'];
            $subtotc += $c;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
            $a = $b = $c = $d = $e = $f = 0;
            $d = $data[$i]['balance'];
            $subtotd += $d;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
            $a = $b = $c = $d = $e = $f = 0;
            $e = $data[$i]['balance'];
            $subtote += $e;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] > 150) {
            $a = $b = $c = $d = $e = $f = 0;
            $f = $data[$i]['balance'];
            $subtotf += $f;
            $subgt += $data[$i]['balance'];
          }
        } //end if
      } //end if clientname == ''
      $tota = $tota + $a;
      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $totf = $totf + $f;

      $totalbalnce += $data[$i]['balance'];
      $gt = $gt + $data[$i]['balance'];

      if (($i + 1) == count($data)) {
        if ($lastclientprinted != $clientname) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($code, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($clientname, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($bstyle, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($group, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($bookbal, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($udf, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($totalbal, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->coL(number_format($subtota, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotf, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->endrow();
          $totalbookbal += $bookbal;
          $totaludf += $udf;
        }
      }
    } // end for loop

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '110px', null, false, '1px dotted', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbookbal, 2), '100px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totaludf, 2), '100px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbalnce, 2), '100px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totf, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end function

  private function reportDefaultLayout_LAYOUT_DETAILED($params, $data)
  {
    $str = "";
    $layoutsize = '800';
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = $params['params']['dataparams']['center'];
    $client       = $params['params']['dataparams']['client'];
    $posttype     = $params['params']['dataparams']['posttype'];
    $reporttype   = $params['params']['dataparams']['reporttype'];
    $companyid    = $params['params']['companyid'];
    $postStatus = '';

    $client       = $params['params']['dataparams']['client'];

    switch ($posttype) {
      case '0': // UNPOSTED
        $postStatus = 'Unposted';
        break;

      case '1': // POSTED
        $postStatus = 'Posted';
        break;

      default: // ALL
        $postStatus = 'ALL';
        break;
    }

    $count = 65;
    $page = 67;

    $str .= $this->reporter->beginreport('1000');

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLES', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('FOREX', '10', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('0-30 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('120+ days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $gt = 0;

    //WTODO: [KIM][2019.12.04][add subtotal]
    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subgt = 0;
    $clientname = "";

    for ($i = 0; $i < count($data); $i++) {
      //
      $str .= $this->reporter->addline();
      if ($clientname != $data[$i]['clientname']) {
        if ($clientname != '') {
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp', '200', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col('&nbsp', '30', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtota, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->endrow();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;
        } //end if
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['clientname'], '200', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '30', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
      } //end if
      //

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['docno'], '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['cur'], '30', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');

      if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
        $a = $data[$i]['balance'];
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtota = $subtota + $a;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
        $b = $data[$i]['balance'];
        $a = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotb = $subtotb + $b;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
        $c = $data[$i]['balance'];
        $a = 0;
        $b = 0;
        $d = 0;
        $e = 0;

        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotc = $subtotc + $c;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
        $d = $data[$i]['balance'];
        $a = 0;
        $c = 0;
        $b = 0;
        $e = 0;

        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotd = $subtotd + $d;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] > 120) {
        $e = $data[$i]['balance'];
        $a = 0;
        $c = 0;
        $d = 0;
        $b = 0;

        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtote = $subtote + $e;
        $subgt = $subgt + $data[$i]['balance'];
      }
      $str .= $this->reporter->endrow();
      $clientname = $data[$i]['clientname'];



      $tota = $tota + $a;
      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $gt = $gt + $data[$i]['balance'];


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->letterhead($center, $username, $params);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLES', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
        if ($client == '') {
          $str .= $this->reporter->col('Customer : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        } else {
          $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        }

        $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('FOREX', '30', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('DATE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('0-30 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('120+ days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '200', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '30', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtota, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotb, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotc, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotd, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtote, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subgt, 2), '100', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '30', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($gt, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function displayHeader_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $contra = "";
    $dept = "";
    if ($companyid == 10) { //afti
      $contra   = $config['params']['dataparams']['contra'];
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
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

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CURRENT CUSTOMER RECEIVABLES - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $reporttype = 'Posted';
    } else {
      $reporttype = 'Unposted';
    }


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER NAME', '110px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '110px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }



  private function displayHeader_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra = "";
    $dept = "";

    if ($companyid == 10) { //afti
      $contra   = $config['params']['dataparams']['contra'];
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED CURRENT CUSTOMER RECEIVABLES', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER NAME', '270', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('No. of days', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }
}//end class