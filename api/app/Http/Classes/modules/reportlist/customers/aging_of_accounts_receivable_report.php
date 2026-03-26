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

class aging_of_accounts_receivable_report
{
  public $modulename = 'Aging Of Accounts Receivable Report';
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

    if ($companyid == 59) { //roosevelt
      $fields = ['radioprint', 'start',  'dagentname', 'area'];
    } else {
      $fields = ['radioprint', 'start', 'dclientname', 'dcentername'];
    }

    switch ($companyid) {
      case 36: //rozlab
        array_push($fields, 'category');
        break;
      case 17: //unihome
        array_push($fields, 'contra');
        break;
      case 55: // afli 
        $fields = ['radioprint', 'start', 'end'];
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'As of');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'contra.lookupclass', 'AR');
    if ($companyid == 55) { // afli
      data_set($col1, 'start.label', 'Start Date');
    }

    if ($companyid == 59) { // roosevelt
      data_set($col1, 'dagentname.label', 'Salesman');
    }
    $fields = ['radioposttype', 'radioreporttype'];

    if ($companyid == 55) { // afli
      unset($fields[1]);
    }

    if ($companyid == 59) { // roosevelt
      unset($fields[0]);
      unset($fields[1]);
    }

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
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      date(now()) as start,
      left(now(),10) as end,
      '' as center,
      '' as client,
      '0' as posttype,
      '0' as reporttype,
      '' as dclientname,
      '' as dcentername,
      '' as category,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      '' as agentid,
      '' as contra,
      '' as acnoname,
      '0' as acnoid,'' as area
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

    switch ($companyid) {
      case 36: //rozlab
      case 27: //nte
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->NEW_ROZLAB_NTE_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $result = $this->ROZLAB_NTE_LAYOUT_DETAILED($config, $result);
            break;
        }
        break;
      case 17: //unihome
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->repor_Layout_UNIHOME_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $result = $this->report_Layout_UNIHOME_LAYOUT_DETAILED($config, $result);
            break;
        }
        break;
      case 55: //afli
        $result = $this->repor_Layout_AFLI_LAYOUT($config, $result);
        break;
      case 59: //roosevelt
        $result = $this->roosevelt_LAYOUT($config, $result);
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 27: //NTE
      case 36: //ROZLAB
      case 17: //unihome
        $query = $this->report_NEW_NTE_ROZLAB_QUERY($config);
        break;
      case 55: // afli
        //$query = $this->afli_query($config);
        $query = $this->principal($config);
        break;
      case 59: //roosevelt
        $query = $this->roosevelt_QUERY($config);
        break;
      default:
        $query = $this->reportDefault_QUERY($config);
        break;
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $this->reportplotting($config, $result);
  }


  public function report_NEW_NTE_ROZLAB_QUERY($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $asof   = $config['params']['dataparams']['start'];
    $agentname  = $config['params']['dataparams']['agent'];
    $category = $config['params']['dataparams']['category'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    $filteracc = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }
    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }
    if ($agentname != '') {
      $agentname = "and client.agent = '$agentname'";
    }

    $cat_name = '';
    $categoryName = '';

    switch ($companyid) {
      case 36: //rozlab
        if ($category != '') {
          $filter = "and cat.cat_name = '$category'";
        }

        $categoryName = " ifnull(cat.cat_name, '') as category , ";
        $cat_name = ', category ';
        break;
      case 17: //unihome
        $filteracc = " and coa.acnoid = '$acnoid'";
        break;
    }

    switch ($reporttype) {
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select clientname, name, sum(balance) as balance, bstyle, code, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias,
            
            sum(e0to30) as e0to30,
            sum(e31to60) as e31to60,
            sum(e61to90) as e61to90,
            sum(e91to120) as e91to120,
            sum(e121to150) as e121to150,
            sum(e151to180) as e151to180,
            sum(e181to360) as e181to360,
            sum(e361) as e361 $cat_name
            from (
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno,datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
              left(coa.alias, 2) as alias, detail.db, detail.cr, ifnull((detail.db - detail.cr), 0) as balance, head.yourref, client.bstyle, client.client as code, 
              client.agent, client.terms, client.crlimit, $categoryName
              sum(case when (datediff(now(), head.dateid)>=0 and datediff(now(), head.dateid)<=30) then ifnull((detail.db - detail.cr), 0) else 0 end) as e0to30,
              sum(case when (datediff(now(), head.dateid)>=31 and datediff(now(), head.dateid)<=60) then ifnull((detail.db - detail.cr), 0) else 0 end) as e31to60,
              sum(case when (datediff(now(), head.dateid)>=61 and datediff(now(), head.dateid)<=90) then ifnull((detail.db - detail.cr), 0) else 0 end) as e61to90,
              sum(case when (datediff(now(), head.dateid)>=91 and datediff(now(), head.dateid)<=120) then ifnull((detail.db - detail.cr), 0) else 0 end) as e91to120,
              sum(case when (datediff(now(), head.dateid)>=121 and datediff(now(), head.dateid)<=150) then ifnull((detail.db - detail.cr), 0) else 0 end) as e121to150,
              sum(case when (datediff(now(), head.dateid)>=151 and datediff(now(), head.dateid)<=180) then ifnull((detail.db - detail.cr), 0) else 0 end) as e151to180,
              sum(case when (datediff(now(), head.dateid)>=181 and datediff(now(), head.dateid)<=360) then ifnull((detail.db - detail.cr), 0) else 0 end) as e181to360,
              sum(case when (datediff(now(), head.dateid)>360) then ifnull((detail.db - detail.cr), 0) else 0 end) as e361
              from lahead as head 
              left join ladetail as detail on detail.trno = head.trno
              left join client on client.client = head.client
              left join coa on coa.acnoid = detail.acnoid
              left join cntnum on cntnum.trno = head.trno
              left join category_masterfile as cat on client.category = cat.cat_id
              where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('CR', 'AR') and detail.refx = 0 and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . " " . $filteracc . "    
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, cat.cat_name

              union all

              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as totalbal, client.groupid as clgrp, left(coa.alias, 2) as alias,
              0 as db, 0 as cr, stock.ext as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit, 
              $categoryName
              sum(case when (datediff(now(), head.dateid)>=0 and datediff(now(), head.dateid)<=30) then stock.ext else 0 end) as e0to30,
              sum(case when (datediff(now(), head.dateid)>=31 and datediff(now(), head.dateid)<=60) then stock.ext else 0 end) as e31to60,
              sum(case when (datediff(now(), head.dateid)>=61 and datediff(now(), head.dateid)<=90) then stock.ext else 0 end) as e61to90,
              sum(case when (datediff(now(), head.dateid)>=91 and datediff(now(), head.dateid)<=120) then stock.ext else 0 end) as e91to120,
              sum(case when (datediff(now(), head.dateid)>=121 and datediff(now(), head.dateid)<=150) then stock.ext else 0 end) as e121to150,
              sum(case when (datediff(now(), head.dateid)>=151 and datediff(now(), head.dateid)<=180) then stock.ext else 0 end) as e151to180,
              sum(case when (datediff(now(), head.dateid)>=181 and datediff(now(), head.dateid)<=360) then stock.ext else 0 end) as e181to360,
              sum(case when (datediff(now(), head.dateid)>360) then stock.ext else 0 end) as e361
              from lahead as head 
              left join lastock as stock on stock.trno = head.trno
              left join ladetail as detail on detail.trno = head.trno
              left join coa on coa.acnoid = detail.acnoid
              left join client on client.client = head.client
              left join cntnum on cntnum.trno = head.trno
              left join category_masterfile as cat on client.category = cat.cat_id
              where head.doc in ('CM', 'SK') and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "  " . $filteracc . "   
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, stock.ext, cat.cat_name
            ) as t 
            
            group by clientname, name, crlimit, bstyle, code, clgrp, terms, alias $cat_name
            order by clientname";
            break;
          case '1': // POSTED
            $qry = "select clientname, name, sum(balance) as balance, bstyle, code, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias, 
            
            sum(e0to30) as e0to30,
            sum(e31to60) as e31to60,
            sum(e61to90) as e61to90,
            sum(e91to120) as e91to120,
            sum(e121to150) as e121to150,
            sum(e151to180) as e151to180,
            sum(e181to360) as e181to360,
            sum(e361) as e361 $cat_name
            from (
            
              select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
              left(coa.alias, 2) as alias, detail.db, detail.cr, (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, head.yourref, client.bstyle, 
              client.client as code, client.agent, client.terms, client.crlimit, $categoryName
              sum(case when (datediff(now(), head.dateid)>=0 and datediff(now(), head.dateid)<=30) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e0to30,
              sum(case when (datediff(now(), head.dateid)>=31 and datediff(now(), head.dateid)<=60) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e31to60,
              sum(case when (datediff(now(), head.dateid)>=61 and datediff(now(), head.dateid)<=90) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e61to90,
              sum(case when (datediff(now(), head.dateid)>=91 and datediff(now(), head.dateid)<=120) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e91to120,
              sum(case when (datediff(now(), head.dateid)>=121 and datediff(now(), head.dateid)<=150) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e121to150,
              sum(case when (datediff(now(), head.dateid)>=151 and datediff(now(), head.dateid)<=180) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e151to180,
              sum(case when (datediff(now(), head.dateid)>=181 and datediff(now(), head.dateid)<=360) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e181to360,
              sum(case when (datediff(now(), head.dateid)>360) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e361

              from arledger as detail left join client on client.clientid = detail.clientid
              left join cntnum on cntnum.trno = detail.trno
              left join glhead as head on head.trno = detail.trno
              left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
              left join coa on coa.acnoid = gdetail.acnoid
              left join category_masterfile as cat on client.category = cat.cat_id
              where detail.bal <> 0 and left(coa.alias, 2) in ('CR', 'AR') and detail.dateid <= '" . $asof . "' " . $filter . " " . $agentname . " " . $filteracc . " 
                group by cntnum.center, tr, client.clientname, 
              head.dateid, head.docno, client.groupid, 
              coa.alias, detail.db, detail.cr,detail.bal, head.yourref, client.bstyle, 
              client.client, client.agent, client.terms, client.crlimit, cat.cat_name
              
            ) as x
            group by clientname, name, crlimit, bstyle, code, clgrp, terms, alias $cat_name
            order by clientname";
            break;
          default: // ALL
            $qry = "
            select clientname, name, sum(balance) as balance, bstyle, code, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias,
            
            sum(e0to30) as e0to30,
            sum(e31to60) as e31to60,
            sum(e61to90) as e61to90,
            sum(e91to120) as e91to120,
            sum(e121to150) as e121to150,
            sum(e151to180) as e151to180,
            sum(e181to360) as e181to360,
            sum(e361) as e361 $cat_name
            from (
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno,datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
              left(coa.alias, 2) as alias, detail.db, detail.cr, ifnull((detail.db - detail.cr), 0) as balance, head.yourref, client.bstyle, client.client as code, 
              client.agent, client.terms, client.crlimit, $categoryName
              sum(case when (datediff(now(), head.dateid)>=0 and datediff(now(), head.dateid)<=30) then ifnull((detail.db - detail.cr), 0) else 0 end) as e0to30,
              sum(case when (datediff(now(), head.dateid)>=31 and datediff(now(), head.dateid)<=60) then ifnull((detail.db - detail.cr), 0) else 0 end) as e31to60,
              sum(case when (datediff(now(), head.dateid)>=61 and datediff(now(), head.dateid)<=90) then ifnull((detail.db - detail.cr), 0) else 0 end) as e61to90,
              sum(case when (datediff(now(), head.dateid)>=91 and datediff(now(), head.dateid)<=120) then ifnull((detail.db - detail.cr), 0) else 0 end) as e91to120,
              sum(case when (datediff(now(), head.dateid)>=121 and datediff(now(), head.dateid)<=150) then ifnull((detail.db - detail.cr), 0) else 0 end) as e121to150,
              sum(case when (datediff(now(), head.dateid)>=151 and datediff(now(), head.dateid)<=180) then ifnull((detail.db - detail.cr), 0) else 0 end) as e151to180,
              sum(case when (datediff(now(), head.dateid)>=181 and datediff(now(), head.dateid)<=360) then ifnull((detail.db - detail.cr), 0) else 0 end) as e181to360,
              sum(case when (datediff(now(), head.dateid)>360) then ifnull((detail.db - detail.cr), 0) else 0 end) as e361
              from lahead as head 
              left join ladetail as detail on detail.trno = head.trno
              left join client on client.client = head.client
              left join coa on coa.acnoid = detail.acnoid
              left join cntnum on cntnum.trno = head.trno
              left join category_masterfile as cat on client.category = cat.cat_id
              where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('CR', 'AR') and detail.refx = 0 and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "  " . $filteracc . "  
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, cat.cat_name

              union all

              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as totalbal, client.groupid as clgrp, left(coa.alias, 2) as alias,
              0 as db, 0 as cr, stock.ext as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit, 
              $categoryName
              sum(case when (datediff(now(), head.dateid)>=0 and datediff(now(), head.dateid)<=30) then stock.ext else 0 end) as e0to30,
              sum(case when (datediff(now(), head.dateid)>=31 and datediff(now(), head.dateid)<=60) then stock.ext else 0 end) as e31to60,
              sum(case when (datediff(now(), head.dateid)>=61 and datediff(now(), head.dateid)<=90) then stock.ext else 0 end) as e61to90,
              sum(case when (datediff(now(), head.dateid)>=91 and datediff(now(), head.dateid)<=120) then stock.ext else 0 end) as e91to120,
              sum(case when (datediff(now(), head.dateid)>=121 and datediff(now(), head.dateid)<=150) then stock.ext else 0 end) as e121to150,
              sum(case when (datediff(now(), head.dateid)>=151 and datediff(now(), head.dateid)<=180) then stock.ext else 0 end) as e151to180,
              sum(case when (datediff(now(), head.dateid)>=181 and datediff(now(), head.dateid)<=360) then stock.ext else 0 end) as e181to360,
              sum(case when (datediff(now(), head.dateid)>360) then stock.ext else 0 end) as e361
              from lahead as head 
              left join lastock as stock on stock.trno = head.trno
              left join ladetail as detail on detail.trno = head.trno
              left join coa on coa.acnoid = detail.acnoid
              left join client on client.client = head.client
              left join cntnum on cntnum.trno = head.trno
              left join category_masterfile as cat on client.category = cat.cat_id
              where head.doc in ('CM', 'SK') and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "   " . $filteracc . "
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, stock.ext, cat.cat_name
                
              union all

              select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
              left(coa.alias, 2) as alias, detail.db, detail.cr, (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, head.yourref, client.bstyle, 
              client.client as code, client.agent, client.terms, client.crlimit, $categoryName
              sum(case when (datediff(now(), head.dateid)>=0 and datediff(now(), head.dateid)<=30) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e0to30,
              sum(case when (datediff(now(), head.dateid)>=31 and datediff(now(), head.dateid)<=60) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e31to60,
              sum(case when (datediff(now(), head.dateid)>=61 and datediff(now(), head.dateid)<=90) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e61to90,
              sum(case when (datediff(now(), head.dateid)>=91 and datediff(now(), head.dateid)<=120) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e91to120,
              sum(case when (datediff(now(), head.dateid)>=121 and datediff(now(), head.dateid)<=150) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e121to150,
              sum(case when (datediff(now(), head.dateid)>=151 and datediff(now(), head.dateid)<=180) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e151to180,
              sum(case when (datediff(now(), head.dateid)>=181 and datediff(now(), head.dateid)<=360) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e181to360,
              sum(case when (datediff(now(), head.dateid)>360) then ifnull((case when detail.db > 0 then detail.bal else (detail.bal * -1) end), 0) else 0 end) as e361

              from arledger as detail left join client on client.clientid = detail.clientid
              left join cntnum on cntnum.trno = detail.trno
              left join glhead as head on head.trno = detail.trno
              left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
              left join coa on coa.acnoid = gdetail.acnoid
              left join category_masterfile as cat on client.category = cat.cat_id
              where detail.bal <> 0 and left(coa.alias, 2) in ('CR', 'AR') and detail.dateid <= '" . $asof . "' " . $filter . " " . $agentname . " " . $filteracc . "
                group by cntnum.center, tr, client.clientname, 
              head.dateid, head.docno, client.groupid, 
              coa.alias, detail.db, detail.cr,detail.bal, head.yourref, client.bstyle, 
              client.client, client.agent, client.terms, client.crlimit, cat.cat_name
            ) as t 
            group by clientname, name, crlimit, bstyle, code, clgrp, terms, alias $cat_name
            order by clientname";
            break;
        }
        break;
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select center, tr, clientname, name, dateid, due, docno, elapse, sum(balance) as balance, yourref, cur, sum(totalbal) as totalbal $cat_name
            from (select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, (detail.db - detail.cr) as balance, 
            head.yourref, head.cur, $categoryName ifnull((detail.db - detail.cr), 0) as totalbal
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join client on client.client = head.client
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('cr', 'ar') and detail.refx = 0 and head.dateid <= '$asof' $filter  $agentname $filteracc
            union all 
            select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, 
            head.yourref, head.cur, $categoryName stock.ext as totalbal
            from lahead as head 
            left join lastock as stock on stock.trno = head.trno
            left join client on client.client = head.client
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('SK', 'CM') and head.dateid <= '$asof' $filter  $agentname
            ) as t group by center, clientname, tr, name, dateid, due, docno, elapse, yourref, cur $cat_name
            order by clientname, dateid, docno";
            break;
          case '1': // POSTED
            $qry = "select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(detail.dateid) as dateid, date(head.due) as due, detail.docno, datediff(now(), detail.dateid) as elapse, 
            (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, 
            head.yourref, head.cur, $categoryName ifnull((detail.db - detail.cr), 0) as totalbal
            from arledger as detail 
            left join client on client.clientid = detail.clientid
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join coa on coa.acnoid = detail.acnoid
            left join category_masterfile as cat on client.category = cat.cat_id
            where detail.bal <> 0 and left(coa.alias, 2) in ('cr', 'ar') and iscustomer = 1 and head.dateid <= '$asof' $filter $filteracc
            order by client.clientname, detail.dateid, detail.docno";
            break;
          default: // ALL
            $qry = "select center, tr, clientname, name, dateid, due, docno, elapse, sum(balance) as balance, yourref, cur, sum(totalbal) as totalbal $cat_name
            from (select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref, 
            head.cur, $categoryName stock.ext as totalbal
            from lahead as head 
            left join lastock as stock on stock.trno = head.trno
            left join client on client.client = head.client
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('SK', 'CM') and head.dateid <= '" . $asof . "' " . $filter . "
            union all
            select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, (detail.db - detail.cr) as balance, 
            head.yourref, head.cur, $categoryName ifnull((detail.db - detail.cr), 0) as totalbal
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join client on client.client = head.client
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('cr', 'ar') and detail.refx = 0 and head.dateid <= '$asof' $filter $filteracc
            union all
            select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(detail.dateid) as dateid, date(head.due) as due, detail.docno, datediff(now(), detail.dateid) as elapse, 
            (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, head.yourref, head.cur, $categoryName 
            ifnull((detail.db - detail.cr), 0) as totalbal
            from arledger as detail 
            left join client on client.clientid = detail.clientid
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join coa on coa.acnoid = detail.acnoid
            left join category_masterfile as cat on client.category = cat.cat_id
            where detail.bal <> 0 and left(coa.alias, 2) in ('cr', 'ar') and iscustomer = 1 and head.dateid <= '$asof' $filter $filteracc ) as t 
            group by center, tr, clientname, name, dateid, due, docno, elapse, yourref, cur $cat_name
            order by clientname, dateid, docno";
            break;
        }
        break;
    } //end switch

    return $qry;
  }


  public function report_NTE_ROZLAB_QUERY($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $asof   = $config['params']['dataparams']['start'];
    $agentname  = $config['params']['dataparams']['agent'];
    $category = $config['params']['dataparams']['category'];

    $filter = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }
    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }
    if ($agentname != '') {
      $agentname = "and client.agent = '$agentname'";
    }

    if ($config['params']['companyid'] == 36) { //rozlab

      if ($category != '') {
        $filter = "and cat.cat_name = '$category'";
      }

      $categoryName = "ifnull(cat.cat_name, '') as category";
      $cat_name = 'category';
    }

    switch ($reporttype) {
      case '0': // SUMMARIZED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select clientname, name, sum(balance) as balance, elapse, bstyle, code, agent, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias, db, cr, $cat_name 
            from (select cntnum.center, 'u' as tr, client.clientname, 
            case client.clientname when '' then 'no name' else client.clientname end as name, 
            date(head.dateid) as dateid, head.docno,
            datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
            left(coa.alias, 2) as alias,
            detail.db, detail.cr, ifnull((detail.db - detail.cr), 0) as balance, head.yourref, 
            client.bstyle, client.client as code, client.agent, client.terms, client.crlimit, $categoryName
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join client on client.client = head.client
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('cr', 'ar') 
            and detail.refx = 0 and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, 
            detail.db, detail.cr, client.bstyle, client.client, client.agent, client.terms, client.crlimit, client.groupid, coa.alias, cat.cat_name
            union all
            select cntnum.center, 'u' as tr, client.clientname, 
            case client.clientname when '' then 'no name' else client.clientname end as name, 
            date(head.dateid) as dateid, head.docno,
            datediff(now(), head.dateid) as elapse, stock.ext as totalbal, client.groupid as clrgrp, 
            left(coa.alias, 2) as alias,
            0 as db, 0 as cr, stock.ext as balance, head.yourref, 
            client.bstyle, client.client as code, client.agent, client.terms, client.crlimit, $categoryName
            from lahead as head 
            left join lastock as stock on stock.trno = head.trno
            left join ladetail as detail on detail.trno = head.trno
            left join coa on coa.acnoid = detail.acnoid
            left join client on client.client = head.client
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('CM', 'SK') and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, stock.ext,
            client.bstyle, client.client, client.agent, client.terms, client.crlimit, client.groupid, coa.alias, cat.cat_name) as t 
            group by clientname, elapse, name, bstyle, code, agent, terms, crlimit, clgrp, alias, db, cr, $cat_name";
            break;
          case '1': // POSTED
            $qry = "select tr, clientname, name, sum(balance) as balance, elapse, crlimit, sum(totalbal) as totalbal, bstyle, clgrp, code, terms, alias, db, cr, $cat_name
            from (select 'p' as tr, client.clientname, ifnull(client.clientname, 'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse, client.crlimit,
            ifnull((detail.db - detail.cr), 0) as totalbal, client.bstyle, client.groupid as clgrp, client.client as code, client.terms, left(coa.alias, 2) as alias, detail.db as db, detail.cr as cr, (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, head.yourref, date(head.deldate) as deldate, $categoryName
            from (arledger as detail 
            left join client on client.clientid = detail.clientid)
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            left join category_masterfile as cat on client.category = cat.cat_id
            where detail.bal <> 0 and left(coa.alias, 2) in ('cr', 'ar') and detail.dateid <= '$asof' $filter $agentname) as x
            group by tr, clientname, name, elapse, crlimit, bstyle, code, clgrp, terms, tr, alias, db, cr, $cat_name
            order by tr, clientname";
            break;
          default: // ALL
            $qry = "select clientname, name, sum(balance) as balance, elapse, bstyle, code, terms, crlimit, sum(totalbal) as totalbal, clgrp, alias, db, cr, $cat_name
            from (
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno,datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
              left(coa.alias, 2) as alias, detail.db, detail.cr, ifnull((detail.db - detail.cr), 0) as balance, head.yourref, client.bstyle, client.client as code, 
              client.agent, client.terms, client.crlimit, $categoryName
              from lahead as head 
              left join ladetail as detail on detail.trno = head.trno
              left join client on client.client = head.client
              left join coa on coa.acnoid = detail.acnoid
              left join cntnum on cntnum.trno = head.trno
              left join category_masterfile as cat on client.category = cat.cat_id
              where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('CR', 'AR') and detail.refx = 0 and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "  
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, cat.cat_name
              union all
              select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as totalbal, client.groupid as clgrp, left(coa.alias, 2) as alias,
              0 as db, 0 as cr, stock.ext as balance, head.yourref, client.bstyle, client.client as code, client.agent, client.terms, client.crlimit, $categoryName
              from lahead as head 
              left join lastock as stock on stock.trno = head.trno
              left join ladetail as detail on detail.trno = head.trno
              left join coa on coa.acnoid = detail.acnoid
              left join client on client.client = head.client
              left join cntnum on cntnum.trno = head.trno
              left join category_masterfile as cat on client.category = cat.cat_id
              where head.doc in ('CM', 'SK') and head.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "  
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, detail.db, detail.cr, client.bstyle, client.client,
              client.agent, client.terms, client.crlimit, client.groupid, coa.alias, db, cr, stock.ext, cat.cat_name
              union all
              select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, ifnull((detail.db - detail.cr), 0) as totalbal, client.groupid as clgrp, 
              left(coa.alias, 2) as alias, detail.db, detail.cr, (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, head.yourref, client.bstyle, 
              client.client as code, client.agent, client.terms, client.crlimit, $categoryName
              from arledger as detail left join client on client.clientid = detail.clientid
              left join cntnum on cntnum.trno = detail.trno
              left join glhead as head on head.trno = detail.trno
              left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
              left join coa on coa.acnoid = gdetail.acnoid
              left join category_masterfile as cat on client.category = cat.cat_id
              where detail.bal <> 0 and left(coa.alias, 2) in ('CR', 'AR') and detail.dateid <= '" . $asof . "' " . $filter . " " . $agentname . "
            ) as t 
            group by tr, clientname, name, elapse, crlimit, bstyle, code, clgrp, terms, alias, db, cr, $cat_name
            order by clientname";
            break;
        }
        break;
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select center, tr, clientname, name, dateid, due, docno, elapse, sum(balance) as balance, yourref, cur, sum(totalbal) as totalbal, $cat_name
            from (select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, (detail.db - detail.cr) as balance, 
            head.yourref, head.cur, ifnull((detail.db - detail.cr), 0) as totalbal, $categoryName
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join client on client.client = head.client
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('cr', 'ar') and detail.refx = 0 and head.dateid <= '$asof' $filter  $agentname
            union all 
            select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, 
            head.yourref, head.cur, stock.ext as totalbal, $categoryName
            from lahead as head 
            left join lastock as stock on stock.trno = head.trno
            left join client on client.client = head.client
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('SK', 'CM') and head.dateid <= '$asof' $filter  $agentname
            ) as t group by center, clientname, tr, name, dateid, due, docno, elapse, yourref, cur, $cat_name
            order by clientname, dateid, docno";
            break;
          case '1': // POSTED
            $qry = "select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(detail.dateid) as dateid, date(head.due) as due, detail.docno, datediff(now(), detail.dateid) as elapse, 
            (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, 
            head.yourref, head.cur, ifnull((detail.db - detail.cr), 0) as totalbal, $categoryName
            from arledger as detail 
            left join client on client.clientid = detail.clientid
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join coa on coa.acnoid = detail.acnoid
            left join category_masterfile as cat on client.category = cat.cat_id
            where detail.bal <> 0 and left(coa.alias, 2) in ('cr', 'ar') and iscustomer = 1 and head.dateid <= '$asof' $filter
            order by client.clientname, detail.dateid, detail.docno";
            break;
          default: // ALL
            $qry = "select center, tr, clientname, name, dateid, due, docno, elapse, sum(balance) as balance, yourref, cur, sum(totalbal) as totalbal, $cat_name
            from (select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name, 
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref, 
            head.cur, stock.ext as totalbal, $categoryName
            from lahead as head 
            left join lastock as stock on stock.trno = head.trno
            left join client on client.client = head.client
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('SK', 'CM') and head.dateid <= '" . $asof . "' " . $filter . "
            union all
            select cntnum.center, 'u' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(head.dateid) as dateid, date(head.due) as due, head.docno, datediff(now(), head.dateid) as elapse, (detail.db - detail.cr) as balance, 
            head.yourref, head.cur, ifnull((detail.db - detail.cr), 0) as totalbal, $categoryName
            from lahead as head 
            left join ladetail as detail on detail.trno = head.trno
            left join client on client.client = head.client
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            left join category_masterfile as cat on client.category = cat.cat_id
            where head.doc in ('GJ', 'AR', 'CR', 'SJ') and left(coa.alias, 2) in ('cr', 'ar') and detail.refx = 0 and head.dateid <= '$asof' $filter
            union all
            select cntnum.center, 'p' as tr, client.clientname, case client.clientname when '' then 'no name' else client.clientname end as name,
            date(detail.dateid) as dateid, date(head.due) as due, detail.docno, datediff(now(), detail.dateid) as elapse, 
            (case when detail.db > 0 then detail.bal else (detail.bal * -1) end) as balance, head.yourref, head.cur, 
            ifnull((detail.db - detail.cr), 0) as totalbal, $categoryName
            from arledger as detail 
            left join client on client.clientid = detail.clientid
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join coa on coa.acnoid = detail.acnoid
            left join category_masterfile as cat on client.category = cat.cat_id
            where detail.bal <> 0 and left(coa.alias, 2) in ('cr', 'ar') and iscustomer = 1 and head.dateid <= '$asof' $filter) as t 
            group by center, tr, clientname, name, dateid, due, docno, elapse, yourref, cur, $cat_name
            order by clientname, dateid, docno";
            break;
        }
        break;
    } //end switch

    return $qry;
  }

  public function roosevelt_QUERY($config)
  {
    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    $asof   = $config['params']['dataparams']['start'];
    $agent    = $config['params']['dataparams']['agent'];
    $area = $config['params']['dataparams']['area'];

    $filter = "";

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($area != "") {
      $filter .= " and client.area='" . $area . "'";
    }

    // if ($filtercenter != "") {
    //   $filter .= " and cntnum.center='$filtercenter'";
    // }


    $qry = "
          select  clientname,elapse,sum(balance) as balance,agentname,area from (
           select  if(client.clientname='','no clientname',client.clientname) as clientname,
               datediff(now(), head.dateid) as elapse, sum(stock.ext) as balance,
              if(agent.clientname = '' or agent.clientname is null, 'No Salesman', agent.clientname) as agentname,
              if(client.area='','No area',client.area) as area
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              left join client as agent on agent.client=head.agent
              where head.doc in ('SK', 'CM') and head.dateid<='$asof' $filter
              group by clientname,elapse,agentname,client.area
              union all
              select  if(client.clientname='','no clientname',client.clientname) as clientname,
               datediff(now(), head.dateid) as elapse,
              sum(detail.db-detail.cr) as balance,
               if(agent.clientname = '' or agent.clientname is null, 'No Salesman', agent.clientname) as agentname,
              if(client.area='','No area',client.area) as area
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join client as agent on agent.client=head.agent
              where left(coa.alias,2)='AR' and head.dateid<='$asof' $filter
              group by clientname,elapse,agentname,client.area
              union all
              select if(client.clientname='','no clientname',client.clientname) as clientname,
              datediff(now(), detail.dateid) as elapse,
              sum(case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
               if(agent.clientname = '' or agent.clientname is null, 'No Salesman', agent.clientname) as agentname,
              if(client.area='','No area',client.area) as area
              from arledger as detail
              left join client on client.clientid=detail.clientid
              left join cntnum on cntnum.trno=detail.trno
              left join glhead as head on head.trno=detail.trno
              left join client as agent on agent.clientid=head.agentid
              where detail.bal<>0 and client.iscustomer = 1 and head.dateid<='$asof' $filter
              group by clientname,elapse,agentname,client.area
              order by clientname ) as x
              group by clientname,elapse,agentname,area
              order by agentname,area";
    // var_dump($qry);
    return $qry;
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
            $qry = "select clientname, name, elapse, sum(balance) as balance, crlimit, agent, terms
              from (
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name, client.agent, client.terms, client.crlimit,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref
              from lahead as head left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('SK', 'CM') and head.dateid<='" . $asof . "' " . $filter . "
              union all
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name, client.agent, client.terms, client.crlimit,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, (detail.db-detail.cr) as balance, head.yourref
              from lahead as head left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where left(coa.alias,2)='AR' and head.dateid<='$asof' $filter
              union all
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
              date(head.dateid) as dateid, si.docno, datediff(now(), head.dateid) as elapse,
              (detail.ext) as balance,head.yourref
              from glhead as head
              left join glstock as detail on detail.trno=head.trno
              left join client on client.clientid=head.clientid
              left join cntnum on cntnum.trno=head.trno
              left join cntnum as si on si.trno = cntnum.svnum
              where head.doc in ('DR') and cntnum.svnum<>0 and head.dateid<='$asof' $filter
              union all
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
              (detail.ext)*-1 as balance,head.yourref
              from lahead as head
              left join lastock as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('CM') and head.dateid<='$asof' $filter) as x
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
                ) AS X ORDER BY  tr, clientname, dateid, docno";
            break;

          default: // ALL
            $qry = "select tr, clientname, name, elapse, balance,agent,terms,crlimit
          from (
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          (detail.db-detail.cr) as balance,head.yourref
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where left(coa.alias,2)='AR' and head.dateid<='$asof' $filter
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
          date(head.dateid) as dateid, si.docno, datediff(now(), head.dateid) as elapse,
          (detail.ext) as balance,head.yourref
          from glhead as head
          left join glstock as detail on detail.trno=head.trno
          left join client on client.clientid=head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join cntnum as si on si.trno = cntnum.svnum
          where head.doc in ('DR') and cntnum.svnum<>0 and head.dateid<='$asof' $filter
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          (detail.ext)*-1 as balance,head.yourref
          from lahead as head
          left join lastock as detail on detail.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('CM') and head.dateid<='$asof' $filter
          union all select cntnum.center,'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,client.agent as agent,client.terms,client.crlimit,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          where detail.bal<>0 and iscustomer = 1 and head.dateid<='$asof' $filter
          union all
          select '' as center,'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref,'' as agent,'' as terms,'' as crlimit
          ) as x
          order by  tr, clientname, name";
            break;
        }

        break;
      case '1': // DETAILED
        switch ($posttype) {
          case '0': // UNPOSTED
            $qry = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref, head.cur
              from lahead as head left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('SK', 'CM') and head.dateid <= '" . $asof . "' " . $filter . "
              union all
              select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
              (detail.db-detail.cr) as balance,head.yourref, head.cur
              from lahead as head left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where left(coa.alias,2)='AR' and head.dateid<='$asof' $filter
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
            $qry = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, stock.ext as balance, head.yourref, head.cur
              from lahead as head left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('SK', 'CM') and head.dateid <= '" . $asof . "' " . $filter . "
              union all
              select cntnum.center, 'u' as tr, client.clientname, 
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
              (detail.db-detail.cr) as balance,
              head.yourref, head.cur
              from lahead as head left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where left(coa.alias,2)='AR' and head.dateid<='$asof' $filter
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
  public function getnoofclient($config, $planid)
  {

    $posttype     = $config['params']['dataparams']['posttype'];
    $asof   = $this->othersClass->sbcdateformat($config['params']['dataparams']['start']);


    switch ($posttype) {
      case '0': // unposted
        $query = " select count(distinct head.docno) as value
      from lahead as head
      left join ladetail as detail ON detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid and r.isloantype =1
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) <= '$asof' and r.line is not null and r.line = $planid group by r.reqtype,head.docno";
        break;

      case '1': // posted
        $query = "select count(distinct head.docno) as value
      from glhead as head
      left join gldetail as detail on detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) <= '$asof'  and r.line is not null  and r.line = $planid group by r.reqtype,head.docno";
        break;

      default: // all
        $query = "
    select count(distinct docno) as value from (
      select head.docno
      from lahead as head
      left join ladetail as detail ON detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid and r.isloantype =1
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) <= '$asof' and r.line is not null and r.line = $planid group by r.reqtype,head.docno

      union all

      select head.docno
      from glhead as head
      left join gldetail as detail on detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) <= '$asof'  and r.line is not null  and r.line = $planid group by r.reqtype,head.docno
    ) as v
";
        break;
    }


    return $this->coreFunctions->datareader($query);
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

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE - SUMMARY', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';

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
    $lastclientprinted = '';
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
          $lastclientprinted = $clientname;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($clientname, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($agent, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '80px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtota, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '110px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
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
          $crlimit = $data[$i]['crlimit'] == '' ? 0 : $data[$i]['crlimit'];
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
        }
      }
      if (($i + 1) == count($data)) {
        if ($lastclientprinted != $clientname) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($clientname, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($agent, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '110px', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '80px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtota, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '110px', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->endrow();
        }
      }
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
    $str .= $this->reporter->col(number_format($tota, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($gt, 2), '110px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
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
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($gt, 2), '100', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn
  private function roosevelt_displayHeader($params, $data)
  {
    $str = "";
    $layoutsize = '1000';
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];
    $agent    = $params['params']['dataparams']['agent'];
    $area = $params['params']['dataparams']['area'];
    $font_size = '12';


    // $str .= $this->reporter->beginreport('1000');

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
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic',  $font_size, '', 'b', '');
    if ($agent == '') {
      $str .= $this->reporter->col('Salesman : ALL', '400', null, false, '1px solid ', '', 'L', 'Century Gothic',  $font_size, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Salesman : ' . strtoupper($agent), '400', null, false, '1px solid ', '', 'L', 'Century Gothic',  $font_size, 'B', '', '');
    }

    if ($area == '') {
      $area = 'ALL';
    } else {
      $area = strtoupper($area);
    }

    $str .= $this->reporter->col('Area : ' . $area, '400', null, false, '1px solid ', '', 'L', 'Century Gothic',  $font_size, 'B', 'b', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', 'Century Gothic',  $font_size, '', '', '');
    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->pagenumber('Page', '100',  null, false, '1px solid ', '', 'R', 'Century Gothic',  $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '5', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('CUSTOMER', '330', null, false, '1px solid ', 'TB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('30 days', '110', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('60 days', '110', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('90 days', '110', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('120 days', '115', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('120+ days', '110', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('TOTAL', '110', null, false, '1px solid ', 'LTBR', 'C', 'Century Gothic',  $font_size, 'B', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '5', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '330', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '115', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }


  private function roosevelt_LAYOUT($params, $data)
  {
    $str = "";
    $count = 30;
    // $page = 40;

    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->roosevelt_displayHeader($params, $data);
    $str .= $this->reporter->begintable($layoutsize);
    $border = "1px solid";
    $font_size = '11';

    $this->reporter->linecounter = 0;
    // $rowCount = 0;

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

    //subtotal
    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subgt = 0;


    $agent = "";
    $subtotas = 0;
    $subtotbs = 0;
    $subtotcs = 0;
    $subtotds = 0;
    $subtotes = 0;
    $subgts = 0;

    for ($i = 0; $i < count($data); $i++) {

      $str .= $this->reporter->addline();
      if ($agent != $data[$i]['agentname']) {
        if ($agent != '') {
          $subtotas = $subtota;
          $subtotbs = $subtotb;
          $subtotcs = $subtotc;
          $subtotds = $subtotd;
          $subtotes = $subtote;
          $subgts = $subgt;
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '5', null, false, $border, 'TBL', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col('SALESMAN TOTAL', '330', null, false, $border, 'TB', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col($subtotas != 0 ? number_format($subtota, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col($subtotbs != 0 ? number_format($subtotb, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col($subtotcs != 0 ? number_format($subtotc, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col($subtotds != 0 ? number_format($subtotd, 2) : '-', '115', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col($subtotes != 0 ? number_format($subtote, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->col($subgts != 0 ? number_format($subgt, 2) : '-', '110', null, false, $border, 'LTBR', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
          $str .= $this->reporter->endrow();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '5', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '330', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '115', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->col('&nbsp;', '110', null, false, '1px solid ', '', 'C', 'Century Gothic',  '4', 'B', '', '');
          $str .= $this->reporter->endrow();

          // $rowCount += 2; // subtotal + spacer
        } //end if

        $str .= $this->reporter->addline(); //space sa pagitan ng header
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false, $border, 'TBL', 'L', 'Century Gothic', $font_size, 'B', '', '5px');

        $str .= $this->reporter->col(strtoupper($data[$i]['agentname']), '330', null, false, $border, 'TB', 'L', 'Century Gothic', $font_size, 'B', '', '5px');

        $str .= $this->reporter->col('&nbsp', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp', '115', null, false, $border, 'TB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp', '110', null, false, $border, 'TBR', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        // $rowCount++;
        // var_dump($rowCount);
      } //end if

      // var_dump($rowCount);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '5', null, false, '1px solid ', 'L', 'L', 'Century Gothic', $font_size, '', '', '5px');
      $str .= $this->reporter->col($data[$i]['clientname'], '330', null, false, '1px solid ', '', 'L', 'Century Gothic', $font_size, '', '', '5px');

      if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
        $a = $data[$i]['balance'];
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '115', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', 'R', 'r', 'Century Gothic', $font_size, '', '', '5px');

        $subtota = $subtota + $a;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
        $b = $data[$i]['balance'];
        $a = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '115', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', 'R', 'r', 'Century Gothic', $font_size, '', '', '5px');

        $subtotb = $subtotb + $b;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
        $c = $data[$i]['balance'];
        $a = 0;
        $b = 0;
        $d = 0;
        $e = 0;

        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '115', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', 'R', 'r', 'Century Gothic', $font_size, '', '', '5px');

        $subtotc = $subtotc + $c;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
        $d = $data[$i]['balance'];
        $a = 0;
        $c = 0;
        $b = 0;
        $e = 0;

        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '115', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', 'R', 'r', 'Century Gothic', $font_size, '', '', '5px');

        $subtotd = $subtotd + $d;
        $subgt = $subgt + $data[$i]['balance'];
      }
      if ($data[$i]['elapse'] > 120) {
        $e = $data[$i]['balance'];
        $a = 0;
        $c = 0;
        $d = 0;
        $b = 0;

        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col('-', '115', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', '', 'r', 'Century Gothic', $font_size, '', '', '5px');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '110', null, false, '1px solid ', 'R', 'r', 'Century Gothic', $font_size, '', '', '5px');

        $subtote = $subtote + $e;
        $subgt = $subgt + $data[$i]['balance'];
      }
      $str .= $this->reporter->endrow();

      // $rowCount++;
      $agent = $data[$i]['agentname'];



      $tota = $tota + $a;
      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $gt = $gt + $data[$i]['balance'];
      // if ($rowCount >= $count) {
      //   $str .= $this->reporter->endtable();
      //   $str .= $this->reporter->page_break();

      //   // new header
      //   $str .= $this->roosevelt_displayHeader($params, $data);
      //   $str .= $this->reporter->begintable('1000');

      //   // reprint current agent header
      //   $str .= $this->reporter->startrow();
      //   $str .= $this->reporter->col(strtoupper($agent), '335', null, false, $border, 'TBL', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->col('&nbsp;', '115', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TBR', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
      //   $str .= $this->reporter->endrow();

      //   // reset
      //   $rowCount = 1; // counted the reprinted agent row
      // }
      if ($this->reporter->linecounter >= $count) {

        // close current page
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        // $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '330', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '115', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', 'Century Gothic', '7', '', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        // reset line counter
        $this->reporter->linecounter = 0;

        // print new header
        $str .= $this->roosevelt_displayHeader($params, $data);
        $str .= $this->reporter->begintable('1000');

        // reprint current agent name
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false, $border, 'TBL', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col(strtoupper($agent), '330', null, false, $border, 'TB', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;', '115', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TBR', 'C', 'Century Gothic', $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
      }
    }
    $subtotas = $subtota;
    $subtotbs = $subtotb;
    $subtotcs = $subtotc;
    $subtotds = $subtotd;
    $subtotes = $subtote;
    $subgts = $subgt;
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '5', null, false,  $border, 'TBL', 'L', 'Century Gothic', $font_size, 'B', '', '5px');;
    $str .= $this->reporter->col(' SALESMAN TOTAL', '330', null, false,  $border, 'TB', 'L', 'Century Gothic', $font_size, 'B', '',  '5px');
    $str .= $this->reporter->col($subtotas != 0 ? number_format($subtota, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($subtotbs != 0 ? number_format($subtotb, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($subtotcs != 0 ? number_format($subtotc, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($subtotds != 0 ? number_format($subtotd, 2) : '-', '115', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($subtotes != 0 ? number_format($subtote, 2) : '-', '110', null, false, $border, 'LTB', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($subgts != 0 ? number_format($subgt, 2) : '-', '110', null, false, $border, 'LTBR', 'R', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '5', null, false,  $border, 'TBL', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col(' GRAND TOTAL', '335', null, false,  $border, 'TB', 'L', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($tota != 0 ? number_format($tota, 2) : '-', '100', null, false,  $border, 'LTB', 'r', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($totb != 0 ? number_format($totb, 2) : '-', '100', null, false,  $border, 'LTB', 'r', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($totc != 0 ? number_format($totc, 2) : '-', '100', null, false,  $border, 'LTB', 'r', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($totd != 0 ? number_format($totd, 2) : '-', '115', null, false,  $border, 'LTB', 'r', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($tote != 0 ? number_format($tote, 2) : '-', '100', null, false,  $border, 'LTB', 'r', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->col($gt != 0 ? number_format($gt, 2) : '-', '100', null, false,  $border, 'LTBR', 'r', 'Century Gothic', $font_size, 'B', '', '5px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn




  private function NEW_ROZLAB_NTE_LAYOUT_SUMMARIZED($params, $data)
  {
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

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
    $category = $params['params']['dataparams']['category'];

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

    if (empty($agentname)) {
      $agentname = 'ALL';
    }

    if (empty($category)) {
      $category = 'ALL';
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

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE- SUMMARY', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
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

    if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('Category : ' . $category, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    if ($agentname == '') {
      $str .= $this->reporter->col('Agent : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Agent : ' . strtoupper($agentname), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLIENT CODE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TERMS', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CREDIT LIMIT', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('BOOK BAL', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('UDF', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TOTAL BALANCE', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //A
    $str .= $this->reporter->col('0-30 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //B
    $str .= $this->reporter->col('31-60 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //C
    $str .= $this->reporter->col('61-90 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //D
    $str .= $this->reporter->col('91-120 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //E
    $str .= $this->reporter->col('121-150 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //F
    $str .= $this->reporter->col('151-180 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //G
    $str .= $this->reporter->col('181-360 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //H
    $str .= $this->reporter->col('360+ days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $a = $b = $c = $d = $e = $f = $g = $h = 0;
    $tota = $totb = $totc = $totd = $tote = $totf = $totg = $toth = 0;
    $gt = 0;
    $subtota = $subtotb = $subtotc = $subtotd = $subtote = $subtotf = $subtotg = $subtoth = 0;
    $subgt = 0;

    $clientname = '';
    $terms = '';
    $code = '';
    $totalbal = $crlimit = $tb = $bookbal = $totaludf = $udf = $totalbookbal = $tbookbal = $totalbalance = 0;
    $lastclientprinted = '';

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['code'], '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['clientname'], '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['terms'], '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['crlimit'], 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      // case when alias='CR' then balance else 0 end as udf,
      $udf = 0;
      if ($data[$i]['alias'] == 'CR') {
        $udf = $data[$i]['balance'];
      }
      $bookbal = $data[$i]['balance'] - $udf;
      $str .= $this->reporter->col(number_format($bookbal, 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($udf == 0 ? '-' : number_format($udf, 2)), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');

      $str .= $this->reporter->col(($data[$i]['e0to30'] == 0 ? '-' : number_format($data[$i]['e0to30'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e31to60'] == 0 ? '-' : number_format($data[$i]['e31to60'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e61to90'] == 0 ? '-' : number_format($data[$i]['e61to90'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e91to120'] == 0 ? '-' : number_format($data[$i]['e91to120'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e121to150'] == 0 ? '-' : number_format($data[$i]['e121to150'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e151to180'] == 0 ? '-' : number_format($data[$i]['e151to180'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e181to360'] == 0 ? '-' : number_format($data[$i]['e181to360'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(($data[$i]['e361'] == 0 ? '-' : number_format($data[$i]['e361'], 2)), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->endrow();
      $tota = $tota + $data[$i]['e0to30'];
      $totb = $totb + $data[$i]['e31to60'];
      $totc = $totc + $data[$i]['e61to90'];
      $totd = $totd + $data[$i]['e91to120'];
      $tote = $tote + $data[$i]['e121to150'];
      $totf = $totf + $data[$i]['e151to180'];
      $totg = $totg + $data[$i]['e181to360'];
      $toth = $toth + $data[$i]['e361'];
      $totalbookbal = $totalbookbal + $bookbal;
      $totaludf = $totaludf + $udf;
      $totalbalance = $totalbalance + $data[$i]['balance'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbookbal, 2), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totaludf, 2), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbalance, 2), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totf, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totg, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($toth, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end function


  private function ROZLAB_NTE_LAYOUT_SUMMARIZED($params, $data)
  {
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

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
    $category = $params['params']['dataparams']['category'];

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

    if (empty($agentname)) {
      $agentname = 'ALL';
    }

    if (empty($category)) {
      $category = 'ALL';
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

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE- SUMMARY', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
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

    if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('Category : ' . $category, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    if ($agentname == '') {
      $str .= $this->reporter->col('Agent : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Agent : ' . strtoupper($agentname), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLIENT CODE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TERMS', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CREDIT LIMIT', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('BOOK BAL', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('UDF', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TOTAL BALANCE', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //A
    $str .= $this->reporter->col('0-30 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //B
    $str .= $this->reporter->col('31-60 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //C
    $str .= $this->reporter->col('61-90 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //D
    $str .= $this->reporter->col('91-120 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //E
    $str .= $this->reporter->col('121-150 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //F
    $str .= $this->reporter->col('151-180 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //G
    $str .= $this->reporter->col('181-360 days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //H
    $str .= $this->reporter->col('360+ days', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $a = $b = $c = $d = $e = $f = $g = $h = 0;
    $tota = $totb = $totc = $totd = $tote = $totf = $totg = $toth = 0;
    $gt = 0;
    $subtota = $subtotb = $subtotc = $subtotd = $subtote = $subtotf = $subtotg = $subtoth = 0;
    $subgt = 0;

    $clientname = '';
    $terms = '';
    $code = '';
    $totalbal = $crlimit = $tb = $bookbal = $totaludf = $udf = $totalbookbal = $tbookbal = $totalbalnce = 0;
    $lastclientprinted = '';
    for ($i = 0; $i < count($data); $i++) {
      if ($clientname == '') {
        $clientname = $data[$i]['name'];
        $terms = $data[$i]['terms'];
        $crlimit = $data[$i]['crlimit'];
        $code = $data[$i]['code'];

        if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $a = $data[$i]['balance'];
          $subtota += $a;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $b = $data[$i]['balance'];
          $subtotb += $b;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $c = $data[$i]['balance'];
          $subtotc += $c;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $d = $data[$i]['balance'];
          $subtotd += $d;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $e = $data[$i]['balance'];
          $subtote += $e;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] >= 151 && $data[$i]['elapse'] <= 180) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $f = $data[$i]['balance'];
          $subtotf += $f;
          $subgt += $data[$i]['balance'];
        }

        if ($data[$i]['elapse'] >= 181 && $data[$i]['elapse'] <= 360) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $g = $data[$i]['balance'];
          $subtotg += $g;
          $subgt += $data[$i]['balance'];
        }
        if ($data[$i]['elapse'] > 360) {
          $a = $b = $c = $d = $e = $f = $g = $h = 0;
          $h = $data[$i]['balance'];
          $subtoth += $h;
          $subgt += $data[$i]['balance'];
        }
      } else {
        if ($clientname != $data[$i]['name']) {
          $lastclientprinted = $clientname;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($code, '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($clientname, '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $bookbal = $totalbal + $udf;
          $str .= $this->reporter->col(number_format($bookbal, 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($udf, 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($totalbal, 2), '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');

          $str .= $this->reporter->col(number_format($subtota, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotf, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotg, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtoth, 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->endrow();
          $subtota = $subtotb = $subtotc = $subtotd = $subtote = $subtotf = $subtotg = $subtoth = $subgt = 0;


          $clientname = $data[$i]['name'];
          $totalbal =  $data[$i]['balance'];
          $terms = $data[$i]['terms'];
          $crlimit = $data[$i]['crlimit'] == '' ? 0 : $data[$i]['crlimit'];
          $code = $data[$i]['code'];

          $totalbookbal += $bookbal;
          $totaludf += $udf;
          $bookbal = $udf = 0;
          if ($data[$i]['alias'] == 'CR') {
            $udf =  $data[$i]['db'] - $data[$i]['cr'];
          }
          if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $a = $data[$i]['balance'];
            $subtota += $a;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $b = $data[$i]['balance'];
            $subtotb += $b;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $c = $data[$i]['balance'];
            $subtotc += $c;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $d = $data[$i]['balance'];
            $subtotd += $d;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $e = $data[$i]['balance'];
            $subtote += $e;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 151 && $data[$i]['elapse'] <= 180) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $f = $data[$i]['balance'];
            $subtotf += $f;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 181 && $data[$i]['elapse'] <= 360) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $g = $data[$i]['balance'];
            $subtotg += $g;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] > 360) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $h = $data[$i]['balance'];
            $subtoth += $h;
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
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $a = $data[$i]['balance'];
            $subtota += $a;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $b = $data[$i]['balance'];
            $subtotb += $b;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $c = $data[$i]['balance'];
            $subtotc += $c;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $d = $data[$i]['balance'];
            $subtotd += $d;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $e = $data[$i]['balance'];
            $subtote += $e;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 151 && $data[$i]['elapse'] <= 180) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $f = $data[$i]['balance'];
            $subtotf += $f;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] >= 181 && $data[$i]['elapse'] <= 360) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $g = $data[$i]['balance'];
            $subtotg += $g;
            $subgt += $data[$i]['balance'];
          }
          if ($data[$i]['elapse'] > 360) {
            $a = $b = $c = $d = $e = $f = $g = $h = 0;
            $h = $data[$i]['balance'];
            $subtoth += $h;
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
      $totg = $totg + $g;
      $toth = $toth + $h;

      $totalbalnce += $data[$i]['balance'];
      $gt = $gt + $data[$i]['balance'];

      if (($i + 1) == count($data)) {
        if ($lastclientprinted != $clientname) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($code, '100', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($clientname, '100', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col($terms, '80', null, false, '1px solid', '', 'L', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($crlimit, 2), '80', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($bookbal, 2), '80', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($udf, 2), '80', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($totalbal, 2), '80', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');

          $str .= $this->reporter->coL(number_format($subtota, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotf, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtotg, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($subtoth, 2), '100', null, false, '1px solid', '', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->endrow();
          $totalbookbal += $bookbal;
          $totaludf += $udf;
        }
      }
    } // end for loop

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'l', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbookbal, 2), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totaludf, 2), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbalnce, 2), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totf, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totg, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($toth, 2), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end function


  private function ROZLAB_NTE_LAYOUT_DETAILED($params, $data)
  {
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = "";
    $layoutsize = '1000';
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = $params['params']['dataparams']['center'];
    $client       = $params['params']['dataparams']['client'];
    $posttype     = $params['params']['dataparams']['posttype'];
    $reporttype   = $params['params']['dataparams']['reporttype'];
    $category     = $params['params']['dataparams']['category'];
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

    if (empty($category)) {
      $category = 'ALL';
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

    if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('Transaction : ' . strtoupper($postStatus), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

      $str .= $this->reporter->col('Category : ' . $category, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER', '90', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '90', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('FOREX', '50', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('DATE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DUE DATE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('TOTAL BALANCE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //A
    $str .= $this->reporter->col('0-30 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //B
    $str .= $this->reporter->col('31-60 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //C
    $str .= $this->reporter->col('61-90 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //D
    $str .= $this->reporter->col('91-120 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //E
    $str .= $this->reporter->col('121-150 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //F
    $str .= $this->reporter->col('151-180 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //G
    $str .= $this->reporter->col('181-360 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //H
    $str .= $this->reporter->col('360+ days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $f = 0;
    $g = 0;
    $h = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $totf = 0;
    $totg = 0;
    $toth = 0;
    $gt = 0;

    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subtotf = 0;
    $subtotg = 0;
    $subtoth = 0;
    $subgt = 0;
    $clientname = "";

    for ($i = 0; $i < count($data); $i++) {
      //
      $str .= $this->reporter->addline();
      if ($clientname != $data[$i]['clientname']) {
        if ($clientname != '') {
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp', '90', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col('&nbsp', '90', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

          $str .= $this->reporter->col('&nbsp', '50', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

          $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

          $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

          $str .= $this->reporter->col(number_format($subtota, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotb, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotc, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotd, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtote, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotf, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotg, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->col(number_format($subtoth, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
          $str .= $this->reporter->endrow();


          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subtotf = 0;
          $subtotg = 0;
          $subtoth = 0;
          $subgt = 0;
        } //end if
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['clientname'], '90', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '90', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '50', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
      } //end if
      //

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['docno'], '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['cur'], '50', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '70', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['due'], '70', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['totalbal'], 2), '70', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');



      //A
      if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
        $a = $data[$i]['balance'];
        $b = 0;
        $c = 0;
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $subtota = $subtota + $a;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //B
      if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
        $a = 0;
        $b = $data[$i]['balance'];
        $c = 0;
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotb = $subtotb + $b;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //C
      if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
        $a = 0;
        $b = 0;
        $c = $data[$i]['balance'];
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotc = $subtotc + $c;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //D
      if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = $data[$i]['balance'];

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotd = $subtotd + $d;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //E
      if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;

        $e = $data[$i]['balance'];
        $f = 0;
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtote = $subtote + $e;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //F
      if ($data[$i]['elapse'] >= 151 && $data[$i]['elapse'] <= 180) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;

        $e = 0;
        $f = $data[$i]['balance'];
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotf = $subtotf + $f;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //G
      if ($data[$i]['elapse'] >= 181 && $data[$i]['elapse'] <= 360) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;

        $e = 0;
        $f = 0;
        $g = $data[$i]['balance'];
        $h = 0;
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotg = $subtotg + $g;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //H
      if ($data[$i]['elapse'] > 360) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = $data[$i]['balance'];
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('-', '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '70', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtoth = $subtoth + $h;
        $subgt = $subgt + $data[$i]['balance'];
      }
      $str .= $this->reporter->endrow();
      $clientname = $data[$i]['clientname'];



      $tota = $tota + $a;
      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $totf = $totf + $f;
      $totg = $totg + $g;
      $toth = $toth + $h;
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
        $str .= $this->reporter->col('CUSTOMER', '90', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '90', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('FOREX', '50', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('DATE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('DUE DATE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('TOTAL BALANCE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //A
        $str .= $this->reporter->col('0-30 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //B
        $str .= $this->reporter->col('31-60 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //C
        $str .= $this->reporter->col('61-90 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //D
        $str .= $this->reporter->col('91-120 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //E
        $str .= $this->reporter->col('121-150 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //F
        $str .= $this->reporter->col('151-180 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //G
        $str .= $this->reporter->col('181-360 days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        //H
        $str .= $this->reporter->col('360+ days', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '90', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '90', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '50', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '70', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtota, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotb, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotc, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotd, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtote, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotf, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotg, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtoth, 2), '70', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '90', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '90', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col('', '70', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totf, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totg, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($toth, 2), '70', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn
  private function repor_Layout_UNIHOME_LAYOUT_SUMMARIZED($params, $data)
  {

    $str = "";
    $layoutsize = '1230';
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
    $acnoid       = $params['params']['dataparams']['acnoid'];

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
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }
    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE - SUMMARY', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';

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
    $acnoname = "ALL";
    if ($acnoid != 0) {
      $acnoname = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acnoid=?", [$acnoid], '', true);
    }
    $str .= $this->reporter->col('Account: ' . $acnoname, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($postStatus), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col(' ', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '150px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '200px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TERMS', '80px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('CREDIT LIMIT', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('TOTAL BALANCE', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('0-30 days', '100px', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '100px', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '100px', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '100px', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('121-150 days', '100px', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('150+ days', '100px', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $el150plus = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['code'] != '' ? $data[$i]['code'] : '-', '100px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['clientname'] != '' ? $data[$i]['clientname'] : '-', '100px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['terms'] != '' ? $data[$i]['terms'] : '-', '80px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['crlimit'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['e0to30'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['e31to60'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['e61to90'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['e91to120'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['e121to150'], 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $el150plus = $data[$i]['e151to180'] + $data[$i]['e181to360'] + $data[$i]['e361'];
      $str .= $this->reporter->col(number_format($el150plus, 2), '100px', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->endrow();
      $el150plus = 0;
    } // end for loop
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end function

  private function report_Layout_UNIHOME_LAYOUT_DETAILED($params, $data)
  {
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = "";
    $layoutsize = '1115';
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = $params['params']['dataparams']['center'];
    $client       = $params['params']['dataparams']['client'];
    $posttype     = $params['params']['dataparams']['posttype'];
    $reporttype   = $params['params']['dataparams']['reporttype'];
    $category     = $params['params']['dataparams']['category'];
    $companyid    = $params['params']['companyid'];
    $postStatus = '';

    $client       = $params['params']['dataparams']['client'];


    $count = 65;
    $page = 67;

    $str .= $this->reporter->beginreport($layoutsize);

    //header
    $str .= $this->unihome_header($params, $client, $posttype, $layoutsize);

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $f = 0;
    $g = 0;
    $h = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $totf = 0;
    $totg = 0;
    $toth = 0;
    $gt = 0;

    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subtotf = 0;
    $subtotg = 0;
    $subtoth = 0;
    $subgt = 0;
    $clientname = "";

    for ($i = 0; $i < count($data); $i++) {
      //
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($clientname != $data[$i]['clientname']) {
        $clientname = $data[$i]['clientname'];
        $str .= $this->reporter->col($clientname, '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '50px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
        $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '90px', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '90px', null, false, '1px', 'B', 'R', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '90px', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '90px', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '90px', null, false, '1px', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

        $str .= $this->reporter->col('&nbsp', '90px', null, false, '1px', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
      } //end if
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['docno'], '100px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['cur'], '50px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '100px', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data[$i]['due'], '100px', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['totalbal'], 2), '100px', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      // A
      if ($data[$i]['elapse'] >= 0 && $data[$i]['elapse'] <= 30) {
        $a = $data[$i]['balance'];
        $b = 0;
        $c = 0;
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $subtota = $subtota + $a;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //B
      if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
        $a = 0;
        $b = $data[$i]['balance'];
        $c = 0;
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotb = $subtotb + $b;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //C
      if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
        $a = 0;
        $b = 0;
        $c = $data[$i]['balance'];
        $d = 0;

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotc = $subtotc + $c;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //D
      if ($data[$i]['elapse'] >= 91 && $data[$i]['elapse'] <= 120) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = $data[$i]['balance'];

        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');


        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtotd = $subtotd + $d;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //E
      if ($data[$i]['elapse'] >= 121 && $data[$i]['elapse'] <= 150) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;

        $e = $data[$i]['balance'];
        $f = 0;
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $subtote = $subtote + $e;
        $subgt = $subgt + $data[$i]['balance'];
      }
      //F
      if ($data[$i]['elapse'] > 151) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;

        $e = 0;
        $f = $data[$i]['balance'];
        $g = 0;
        $h = 0;
        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('-', '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '90px', null, false, '1px solid ', '', 'r', 'Century Gothic', '10', '', '', '');
        $subtotf = $subtotf + $f;
        $subgt = $subgt + $data[$i]['balance'];
      }
      $str .= $this->reporter->endrow();
      $tota = $tota + $a;
      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $totf = $totf + $f;
      $gt = $gt + $data[$i]['balance'];


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .=  $this->unihome_header($params, $client, $posttype, $layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '50px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('&nbsp', '100px', null, false, '1px', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtota, 2), '90px', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotb, 2), '90px', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotc, 2), '90px', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotd, 2), '90px', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtote, 2), '90px', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotf, 2), '90px', null, false, '1px dotted', 'T', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100px', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'L', 'Century Gothic', '10', '', '', '5px');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted', 'T', 'L', 'Century Gothic', '10', 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota, 2), '90px', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '90px', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '90px', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '90px', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '90px', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col(number_format($totf, 2), '90px', null, false, '1px dotted ', 'T', 'r', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn
  public function unihome_header($params, $client, $posttype, $layoutsize)
  {

    $str = "";
    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = $params['params']['dataparams']['center'];
    $client       = $params['params']['dataparams']['client'];
    $posttype     = $params['params']['dataparams']['posttype'];
    $reporttype   = $params['params']['dataparams']['reporttype'];
    $category     = $params['params']['dataparams']['category'];
    $companyid    = $params['params']['companyid'];
    $acnoid     = $params['params']['dataparams']['acnoid'];
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

    if (empty($category)) {
      $category = 'ALL';
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLES', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
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
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $acnoname = "ALL";
    if ($acnoid != 0) {
      $acnoname = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acnoid=?", [$acnoid], '', true);
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($postStatus), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Account : ' . $acnoname, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');


    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('FOREX', '50px', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('DATE', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('DUE DATE', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('TOTAL BALANCE', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //A
    $str .= $this->reporter->col('0-30 days', '90px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //B
    $str .= $this->reporter->col('31-60 days', '90px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //C
    $str .= $this->reporter->col('61-90 days', '90px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //D
    $str .= $this->reporter->col('91-120 days', '90px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //E
    $str .= $this->reporter->col('121-150 days', '90px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    //F
    $str .= $this->reporter->col('150+ days', '90px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
  public function header_AFLI($config)
  {
    $str = "";
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $category     = $config['params']['dataparams']['category'];
    $companyid    = $config['params']['companyid'];
    $acnoid     = $config['params']['dataparams']['acnoid'];
    $asof  = $config['params']['dataparams']['end'];

    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = "1100";

    $font = $this->companysetup->getrptfont($config['params']);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLES', null, null, false, '1px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, '1px solid ', '', '', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(' As of ', '60', null, false, '1px solid ', '', '', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('' . $asof, '240', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '400', null, false, '1px solid ', '', '', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '180', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('No. of', '60', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Accounts', '120', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '140', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Aging of ', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Receivable ', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Past Due', '80', null, false, $border, 'TRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type of Loan', '180', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Clients', '60', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Principal (Only)', '120', null, false, $border, 'LBR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Current Receivable', '140', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Past Due', '120', null, false, $border, 'LB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('1-30 Days', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 Days', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 Days', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Over 90 Days', '100', null, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Rate', '80', null, false, $border, 'LBR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function principal($config)
  {
    $posttype = $config['params']['dataparams']['posttype'];
    $start   = $this->othersClass->sbcdateformat($config['params']['dataparams']['start']);
    $end   = $this->othersClass->sbcdateformat($config['params']['dataparams']['end']);
    $filter = "";

    // if($s){
    //   $filter =" and r.line = '$planid' ";
    // }

    switch ($posttype) {
      case '0': // unposted
        $query = "
      select sum(detail.db - detail.cr) as bal,datediff('$end',detail.postdate) as elapse,r.reqtype as planname,r.line as planid
      from lahead as head
      left join ladetail as detail ON detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid and r.isloantype =1
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) between '$start' and '$end'  and r.line is not null $filter group by  datediff('$end',detail.postdate),r.reqtype,r.line order by planname,elapse
    ";
        break;

      case '1': // posted
        $query = "
      select sum(detail.db-detail.cr)-ifnull((select sum(vpay.cr-vpay.db) from (
      select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line
      from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid
      left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid
      where detail.refx > 0 and date(head.dateid) between '$start' and '$end') as vpay where vpay.refx=detail.trno and vpay.linex=detail.line),0)  as bal,
      datediff('$end',detail.postdate) as elapse,r.reqtype as planname,r.line as planid
      from glhead as head
      left join gldetail as detail on detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) between '$start' and '$end' and r.line is not null $filter group by  datediff('$end',detail.postdate),r.reqtype,r.line,detail.trno,detail.line order by planname,elapse";

        break;

      default: // all
        $query = "select sum(bal) as bal,elapse,planname,planid from (
      select sum(detail.db - detail.cr) as bal,datediff('$end',detail.postdate) as elapse,r.reqtype as planname,r.line as planid
      from lahead as head
      left join ladetail as detail ON detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid and r.isloantype =1
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) between '$start' and '$end' and r.line is not null $filter group by datediff('$end',detail.postdate) ,r.reqtype,r.line

      union all

      select sum(detail.db-detail.cr)-ifnull((select sum(vpay.cr-vpay.db) from (
      select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line
      from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid
      left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid
      where detail.refx > 0 and date(head.dateid) between '$start' and '$end') as vpay where vpay.refx=detail.trno and vpay.linex=detail.line),0)  as bal,datediff('$end',detail.postdate) as elapse,
      r.reqtype as planname,r.line as planid
      from glhead as head
      left join gldetail as detail on detail.trno = head.trno
      left join coa on coa.acnoid = detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.trno = cntnum.dptrno
      left join reqcategory as r on r.line = ea.planid
      WHERE coa.alias in ('AR1','AR5')
      and date(detail.postdate) between '$start' and '$end'  and r.line is not null  $filter group by datediff('$end',detail.postdate),detail.trno,detail.line,r.reqtype,r.line
        ) as v group by elapse,planname,planid
         order by planname,elapse";
        break;
    }


    $this->coreFunctions->LogConsole($query);
    return $query;
    //return $this->coreFunctions->opentable($query);
  }
  public function repor_Layout_AFLI_LAYOUT($config, $data)
  {
    $fontsize = "9";
    $border = "1px solid ";
    $layoutsize = "1100";

    $font = $this->companysetup->getrptfont($config['params']);
    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', 'margin-top:10px;margin-right:-100px;');
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_AFLI($config);

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $pal = 0;
    $noemp = 0;
    $aco = 0;
    $cr = 0;
    $tpastdue = 0;
    $t1days = 0;
    $t2days = 0;
    $t3days = 0;
    $t4days = 0;
    $trate = 0;
    $str .= $this->reporter->begintable($layoutsize);
    $totalprinc = 0;
    $count = 0;
    $planname = "";
    $rate = 0;
    $pasdue = 0;

    //get total principal
    for ($x = 0; $x < count($data); $x++) {
      $totalprinc += $data[$x]['bal'];
    }

    $planid = 0;

    foreach ($data as $i => $value) {
      if ($planname == '') {
        $planname = $data[$i]['planname'];
        $planid = $data[$i]['planid'];

        if ($data[$i]['elapse'] <= 0) {
          $a = $a + $data[$i]['bal'];
        }
        if ($data[$i]['elapse'] > 0 && $data[$i]['elapse'] <= 30) {
          $b = $b + $data[$i]['bal'];
          $pasdue = $pasdue + $data[$i]['bal'];
        }
        if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
          $c = $c + $data[$i]['bal'];
          $pasdue = $pasdue + $data[$i]['bal'];
        }
        if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
          $d = $d + $data[$i]['bal'];
          $pasdue = $pasdue + $data[$i]['bal'];
        }
        if ($data[$i]['elapse'] > 90) {
          $e = $e + $data[$i]['bal'];
          $pasdue = $pasdue + $data[$i]['bal'];
        }

        $pal = $pal + $data[$i]['bal'];
      } else {
        if ($planname != $data[$i]['planname']) {
          $noemp = $this->getnoofclient($config, $planid);
          $count = $count + $noemp;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($planname, '180', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($noemp, '60', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($pal, 2), '120', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($a != 0 ? number_format($a, 2) : '-', '140', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($pasdue != 0 ? number_format($pasdue, 2) : '-', '120', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($b != 0 ? number_format($b, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($c != 0 ? number_format($c, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($d != 0 ? number_format($d, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($e != 0 ? number_format($e, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

          $rate = $pasdue != 0 && $totalprinc != 0 ? ($pasdue / $totalprinc) * 100 : 0;

          $str .= $this->reporter->col(number_format($rate, 2) . '%', '80', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();


          $aco += $pal;
          $cr += $a;
          $tpastdue += $pasdue;
          $t1days += $b;
          $t2days += $c;
          $t3days += $d;
          $t4days += $e;

          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $pal = 0;
          $pasdue = 0;
          $rate = 0;


          $planname = $data[$i]['planname'];
          $planid = $data[$i]['planid'];

          if ($data[$i]['elapse'] <= 0) {
            $a = $a + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] > 0 && $data[$i]['elapse'] <= 30) {
            $b = $b + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $c = $c + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $d = $d + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] > 90) {
            $e = $e + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }

          $pal = $pal + $data[$i]['bal'];
        } else { //same plan name          
          $planname = $data[$i]['planname'];
          $planid = $data[$i]['planid'];

          if ($data[$i]['elapse'] <= 0) {
            $a = $a + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] > 0 && $data[$i]['elapse'] <= 30) {
            $b = $b + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] >= 31 && $data[$i]['elapse'] <= 60) {
            $c = $c + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] >= 61 && $data[$i]['elapse'] <= 90) {
            $d = $d + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }
          if ($data[$i]['elapse'] > 90) {
            $e = $e + $data[$i]['bal'];
            $pasdue = $pasdue + $data[$i]['bal'];
          }

          $pal = $pal +  $data[$i]['bal'];
        }
      }
    }

    $noemp = $this->getnoofclient($config, $planid);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($planname, '180', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($noemp, '60', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($pal, 2), '120', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($a != 0 ? number_format($a, 2) : '-', '140', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($pasdue != 0 ? number_format($pasdue, 2) : '-', '120', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($b != 0 ? number_format($b, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($c != 0 ? number_format($c, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($d != 0 ? number_format($d, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($e != 0 ? number_format($e, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

    $rate = $pasdue != 0 && $totalprinc != 0 ? ($pasdue / $totalprinc) * 100 : 0;

    $str .= $this->reporter->col(number_format($rate, 2) . '%', '80', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $count += $noemp;
    $aco += $pal;
    $cr += $a;
    $tpastdue += $pasdue;
    $t1days += $b;
    $t2days += $c;
    $t3days += $d;
    $t4days += $e;
    $trate = $tpastdue != 0 && $totalprinc != 0 ? ($tpastdue / $totalprinc) * 100 : 0;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Total', '180', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($count, '60', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($aco, 2), '120', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col($cr != 0 ? number_format($cr, 2) : '-', '140', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($tpastdue != 0 ? number_format($tpastdue, 2) : '-', '120', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($t1days != 0 ? number_format($t1days, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($t2days != 0 ? number_format($t2days, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($t3days != 0 ? number_format($t3days, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($t4days != 0 ? number_format($t4days, 2) : '-', '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');

    // $rate = ($pasdue / $pal) * 100;
    $str .= $this->reporter->col(number_format($trate, 2) . '%', '80', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}
