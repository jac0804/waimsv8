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

class current_customer_receivables_aging
{
  public $modulename = 'Current Customer Receivables Aging';
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
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $fields = ['radioprint', 'dclientname', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        $fields = ['radioposttype', 'radioreporttype', 'radiotypeofreportsales'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.label', 'Report Format');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        break;

      default:
        $fields = ['radioprint'];
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            array_push($fields, 'dateid', 'enddate', 'dclientname', 'dcentername', 'collectorname', 'ddeptname', 'contra');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'ddeptname.label', 'Department');
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
            data_set($col1, 'dclientname.label', 'Customer');
            data_set($col1, 'dateid.label', 'Start Date');
            data_set($col1, 'dateid.readonly', false);
            break;
          case 55: //afli
            array_push($fields,  'start', 'end','dclientname', 'dcentername', 'contra');
            
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.lookupclass', 'lookupclient');
            data_set($col1, 'dclientname.label', 'Customer');
            break;
          case 34://evergreen
            array_push($fields, 'dclientname', 'dcentername', 'contra');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.lookupclass', 'lookupclient');
            data_set($col1, 'dclientname.label', 'Payor');
            break;

          case 63://ericco
            array_push($fields, 'dclientname', 'customercategory', 'groupid',  'dcentername', 'contra', 'radioreporttype');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.lookupclass', 'lookupclient');
            data_set($col1, 'dclientname.label', 'Customer');
            data_set($col1, 'customercategory.action', 'lookupcustcategory');
            data_set($col1, 'customercategory.label', 'Category');
            data_set($col1, 'groupid.label', 'Customer Group');
            data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
            data_set($col1, 'groupid.action', 'lookupclientgroupledger');
         
            data_set($col1, 'radioreporttype.options',[
                  ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
                  ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
                  ['label' => 'Customer Group', 'value' => '2', 'color' => 'orange']
                ]
                );
            break;
          default:
            array_push($fields, 'dclientname','dcentername', 'contra');
            if ($companyid == 32) array_push($fields, 'dagentname'); //3m
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.lookupclass', 'lookupclient');
            data_set($col1, 'dclientname.label', 'Customer');
            break;
        }


        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $fields = ['radioretagging', 'radioreporttype'];
            break;
          case 63: //ericco
            $fields = ['radioposttype'];
            break;
          default:
            $fields = ['radioposttype', 'radioreporttype'];
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
        data_set($col2, 'radioreporttype.options',
        [
          ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
        ]
        );

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        break;
    } // end switch

    return array('col1' => $col1, 'col2' => $col2, 'col' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 
      'default' as print,
      '' as client,
      '0' as clientid,
      '0' as posttype,
      '0' as reporttype,
      '' as dclientname,
      '' as agent,
      '' as agentname,
      '' as dagentname,
      '' as agentid,
      'report' as typeofreport,
      '' as contra,'' as acnoname,'0' as acnoid,
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
      adddate(left(now(),10),-360) as start, date_add(date(now()),interval 1 month) as end,

      '' as category_name, '' as category_id, '' as customerfilter
      ";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", adddate(left(now(),10),-30) as dateid,
      left(now(),10) as enddate, '' as ddeptname, '' as dept, '' as deptname,
      '' as contra,
      '0' as acnoid,
      '0' as tagging,
      '' as collectorname,
      '' as collectorcode,
      '' as collector,
      '0' as collectorid";
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
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $reporttype   = $config['params']['dataparams']['reporttype'];

    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $data = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result);
            break;

          case '1': // DETAILED
            $data = $this->vitaline_layout_detailed($config, $result);
            break;
        }
        break;
      case 10: //afti
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $data = $this->AFTI_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $data = $this->AFTI_LAYOUT_DETAILED($config, $result);
            break;
        }
        break;
      case 21: //kinggeorge
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $data = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $data = $this->kinggeorge_LAYOUT_DETAILED($config, $result);
            break;
        }
        break;
      case 32: //3m
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $data = $this->reportDefaultLayout_LAYOUT_SUMMARIZED3M($config, $result);
            break;
          case '1': // DETAILED
            $data = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result);
            break;
        }
        break;
        case 63: //3m
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $data = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $data = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result);
            break;
          case '2': // DETAILED
            $data = $this->reportDefaultLayout_LAYOUT_GROUP($config, $result);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $data = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $data = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result);
            break;

            // Added by Elmer 2026-02-27
          // case '2': // GROUP
          //   $data = $this->reportDefaultLayout_LAYOUT_GROUP($config, $result); 
          //   break;
        }
        break;
    }


    return $data;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype = $config['params']['dataparams']['posttype'];

    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->vitaline_query_posted($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->vitaline_query_unposted($config); // POSTED
            break;
        }
        break;

      case 6: // mitsukoshi
        switch ($posttype) {
          case '1': // UNPOSTED
            $query = $this->MITSUKOSHI_QUERY_UNPOSTED($config); // UNPOSTED
            break;
          case '0': // POSTED
            $query = $this->reportDefault_QUERY_POSTED($config); // POSTED
            break;
        }
        break;

      case 10: //afti
        $query = $this->reportDefault_QUERY_AFTI($config); // POSTED
        break;

      case 40: //cdo
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->CDO_QUERY_POSTED($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->CDO_QUERY_UNPOSTED($config); // POSTED
            break;
          case '2': // ALL
            $query = $this->CDO_QUERY_ALL($config); //ALL
            break;
        }
        break;
      case 55: //afli
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->AFLI_QUERY_POSTED($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->AFLI_QUERY_UNPOSTED($config); // POSTED
            break;
          case '2': // ALL
            $query = $this->AFLI_QUERY_ALL($config); //ALL
            break;
        }
        break;
      case 34: //evergreen
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->elsi_QUERY_POSTED($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->elsi_QUERY_UNPOSTED($config); // POSTED
            break;
          case '2': // ALL
            $query = $this->elsi_QUERY_ALL($config); //ALL
            break;
        }
        break;

        case 63: //evergreen
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->ericco_QUERY_POSTED($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->ericco_QUERY_UNPOSTED($config); // POSTED
            break;
          case '2': // ALL
            $query = $this->ericco_QUERY_ALL($config); //ALL
            break;
        }
        break;

      default:
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->reportDefault_QUERY_POSTED($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->reportDefault_QUERY_UNPOSTED($config); // POSTED
            break;
          case '2': // ALL
            $query = $this->reportDefault_QUERY_all($config); //ALL
            break;
        }
        break;
    }
    $result = $this->coreFunctions->opentable($query);
    return $this->reportplotting($config, $result);
  }

  
  public function AFLI_QUERY_POSTED($config)
  {
    
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }

    $filter1 .= "";
  

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";


    $elapsedate = 'detail.dateid';

    switch ($reporttype) {
      case '1': // DETAILED
        
            $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate
            from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid between '$start' and '$end' $filter $filter1 $filter2 ) as x
            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate
            order by tr, clientname";
           

        break;
      case '0': // SUMMARIZED
        $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m3 . "
        from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate " . $addfields3m . "
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid between '$start' and '$end'  $filter $filter1 $filter2
        union all
        select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref, null as deldate " . $addfields3m2 . "
        ) as x
        group by tr, clientname, name,elapse " . $addfields3m3 . "
        order by tr, clientname";

        break;
    } //end switch
    return $query;
  }

  public function AFLI_QUERY_UNPOSTED($config)
  {
    
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    
    $filter1 .= "";

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    

    $elapsedate = 'head.dateid';
    
    switch ($reporttype) {
      case '1': // DETAILED
        
        $ref1 = "detail.ref";
        $ref2 = " '' as ref";
        $field = "";

        $query = "select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        detail.db as balance,head.yourref,date(head.deldate) as deldate $field
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and head.dateid between '$start' and '$end' and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate $field
        union all
        select cntnum.center, 'u' as tr,head.trno,head.doc,$ref2, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','MJ','CM') and head.dateid between '$start' and '$end'  $filter $filter1 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref " . $addfields3m . "
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and head.dateid between '$start' and '$end' and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db " . $addfields3m . "
        ) as t
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref " . $addfields3m . "
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','MJ','CM') and head.dateid between '$start' and '$end' $filter $filter1 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref " . $addfields3m . ") as x
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select 'z' as clientname, '' as name, '' as balance, '' as elapse " . $addfields3m3 . "
        order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function AFLI_QUERY_ALL($config)
  {
    
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    // (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    $filter1 .= "";

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    
    $elapsedate = 'head.dateid';
    

    switch ($reporttype) {
      case '1': // DETAILED

        $ref1 = "detail.ref";
        $ref2 = " '' as ref";
        $field = "";
      

        $query = "select ref,clientname,dateid,docno, name, sum(balance) as balance,elapse
            from (
            select cntnum.center, 'p' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno,  datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
            head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and head.dateid between '$start' and '$end' $filter $filter1 $filter2

          union all

          select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
                  date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
                  detail.db as balance,head.yourref,date(head.deldate) as deldate $field
                  from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid between '$start' and '$end' $filter $filter1 $filter2 $filter3
                  group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate  $field
                  
                  union all
                 
                  select cntnum.center, 'u' as tr,head.trno,head.doc, $ref2, client.clientname, ifnull(client.clientname,'no name') as name,
                  date(head.dateid) as dateid, head.docno,  datediff(now(), $elapsedate) as elapse,
                  sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
                  from lahead as head left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join cntnum on cntnum.trno=head.trno
                  where cntnum.doc IN ('SJ','MJ','CM') and head.dateid between '$start' and '$end' $filter $filter1 $filter3
                  group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
                  order by clientname, dateid, docno) as x
                        group by clientname, dateid,docno,name,ref,elapse
                        order by  clientname";


        break;

      case '0': // SUMMARIZED

        $query = "select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m3 . "
        from (
        select cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
        head.yourref,date(head.deldate) as deldate  " . $addfields3m . "
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AR' and head.dateid between '$start' and '$end'  $filter $filter1 $filter2
        union all
        select  '' as center, 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, 0 as balance,'' as yourref, null as deldate " . $addfields3m2 . ") as x
        group by  clientname,  name,elapse " . $addfields3m3 . "


        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,
        head.yourref,date(head.deldate) as deldate  " . $addfields3m . "
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and head.dateid between '$start' and '$end' and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db, head.deldate" . $addfields3m . ") as t
        group by clientname, elapse, name  " . $addfields3m2 . "

        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance,
        head.yourref,date(head.deldate) as deldate " . $addfields3m . "
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','MJ','CM') and head.dateid between '$start' and '$end' $filter $filter1 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, head.deldate" . $addfields3m . ") as x
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select   'z' as clientname, '' as name, 0 as balance, '' as elapse " . $addfields3m3 . "
        order by clientname, name";
        break;
    } //end swicth
    return $query;
  }


  public function CDO_QUERY_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }

    $elapsedate = 'detail.dateid';

    switch ($reporttype) {
      case '1': // DETAILED

        $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate
            from (
            select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
            from arledger as detail 
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join client on client.clientid=gdetail.clientid
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter ) as x
            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate
            order by tr, clientname";
        break;
      case '0': // SUMMARIZED
        $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate 
        from arledger as detail 
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join client on client.clientid=gdetail.clientid
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter 
        union all
        select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref, null as deldate 
        ) as x
        group by tr, clientname, name,elapse 
        order by tr, clientname";

        break;
    } //end switch
    return $query;
  }

  public function CDO_QUERY_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }


    $elapsedate = 'head.dateid';
    switch ($reporttype) {
      case '1': // DETAILED

        $ref1 = "detail.ref";
        $ref2 = " '' as ref";
        $field = "";



        $query = "select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        detail.db as balance,head.yourref,date(head.deldate) as deldate $field
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate $field
        union all
        select cntnum.center, 'u' as tr,head.trno,head.doc,$ref2, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','CM') and head.dateid<=now() $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
        union all
        select cntnum.center, 'u' as tr,head.trno,head.doc,$ref2, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        sum(detail.db-detail.cr) as balance, head.yourref,date(head.deldate) as deldate $field
        from lahead as head 
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=detail.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('MJ') and head.dateid<=now() $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db 
        ) as t
        group by clientname, elapse, name

        union all

        select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref 
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','CM') and head.dateid<=now() $filter
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref ) as x
        group by clientname, elapse, name
        union all
        select 'z' as clientname, '' as name, '' as balance, '' as elapse 

        union all

        select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(detail.db-detail.cr) as balance, head.yourref 
        from lahead as head 
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=detail.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('MJ') and head.dateid<=now() $filter
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref ) as x
        group by clientname, elapse, name 
        order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function CDO_QUERY_all($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }

    $elapsedate = 'head.dateid';


    switch ($reporttype) {
      case '1': // DETAILED

        $ref1 = "detail.ref";
        $ref2 = " '' as ref";
        $field = "";

        $query = "select ref,clientname,dateid,docno, name, sum(balance) as balance,elapse
            from (
            select cntnum.center, 'p' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno,  datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
            head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter 

          union all

          select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
          detail.db as balance,head.yourref,date(head.deldate) as deldate $field
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate  $field
          
          union all
          
          select cntnum.center, 'u' as tr,head.trno,head.doc, $ref2, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno,  datediff(now(), $elapsedate) as elapse,
          sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
          from lahead as head left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno
          where cntnum.doc IN ('SJ','CM') and head.dateid<=now() $filter 
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
          union all
          
          select cntnum.center, 'u' as tr,head.trno,head.doc, $ref2, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno,  datediff(now(), $elapsedate) as elapse,
          sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
          from lahead as head left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno
          where cntnum.doc IN ('MJ') and head.dateid<=now() $filter 
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
          order by clientname, dateid, docno
          ) as x
          group by clientname, dateid,docno,name,ref,elapse
          order by  clientname";


        break;

      case '0': // SUMMARIZED

        $query = "select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
        select cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
        head.yourref,date(head.deldate) as deldate  
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter 
        ) as x
        group by  clientname,  name,elapse 


        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          detail.db as balance,
          head.yourref,date(head.deldate) as deldate  
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db, head.deldate
        ) as t
        group by clientname, elapse, name 

        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(stock.ext) as balance,
          head.yourref,date(head.deldate) as deldate 
          from lahead as head left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno
          where cntnum.doc IN ('SJ','CM') and head.dateid<=now() $filter 
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, head.deldate
        ) as x
        group by clientname, elapse, name
        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
        from (
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(stock.ext) as balance,
          head.yourref,date(head.deldate) as deldate 
          from lahead as head left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno
          where cntnum.doc IN ('MJ') and head.dateid<=now() $filter 
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, head.deldate
        ) as z
        group by clientname, elapse, name
        order by clientname, name";
        break;
    } //end swicth
    return $query;
  }

  public function reportDefault_QUERY_AFTI($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    $tagging = $config['params']['dataparams']['tagging'];
    $collectorid = $config['params']['dataparams']['collectorid'];
    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }
    $deptid = $config['params']['dataparams']['ddeptname'];
    if ($deptid == "") {
      $dept = "";
    } else {
      $dept = $config['params']['dataparams']['deptid'];
    }
    if ($deptid != "") {
      $filter1 .= " and head.deptid = $dept";
    }

    if ($contra != "") {
      $filter .= " and coa.acnoid = '" . $acnoid . "' ";
    }

    if ($tagging == 0) {
      $filter .= " and client.iscustomer = 1 ";
    } else if ($tagging == 1) {
      $filter .= " and client.isemployee = 1 ";
    }

    if ($collectorid != 0) {
      $filter = " and client.collectorid='$collectorid'";
    }


    switch ($reporttype) {
      case '1': // DETAILED

        $query = "select tr,trno,doc,ref,client,clientname,dateid,docno,yourref, name, sum(balance) as balance,(case doc when 'AR' then elapse else (case when receivedate is null then 0 else elapse end) end) as elapse
        from (select 'p' as tr,cntnum.doc,head.trno,detail.ref,client.client, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, case cntnum.doc when 'AR' then d.rem when 'SJ' then replace(detail.docno,'DR','SI') else detail.docno end as docno, 
        case cntnum.doc when 'AR' then datediff('$enddate', detail.dateid) else datediff('$enddate',ifnull(dl.receivedate,detail.dateid)) end as elapse,
        (detail.db-detail.cr)-ifnull((select sum(vpay.cr-vpay.db) from (          
          select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
          from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
          left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
          where detail.refx > 0 and date(head.dateid) between '$startdate' and '$enddate') as vpay where vpay.refx=detail.trno and vpay.linex=detail.line),0) as balance,head.yourref,dl.receivedate
        from (arledger as detail 
        left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as d on d.trno=detail.trno and d.line = detail.line
        left join coa as coa on coa.acnoid = detail.acnoid
        left join delstatus as dl on dl.trno = detail.trno
        where coa.alias not in ('AR5') and date(detail.dateid) between '$startdate' and '$enddate' $filter $filter1 ) as x
        group by tr, client,clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,receivedate
        order by tr, clientname";


        break;
      case '0': // SUMMARIZED
        $query = "select tr,client, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,(case doc when 'AR' then elapse else (case when receivedate is null then 0 else elapse end) end) as elapse
        from (select 'p' as tr,cntnum.doc, client.client,client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, case cntnum.doc when 'AR' then datediff('$enddate', detail.dateid) else datediff('$enddate',ifnull(dl.receivedate,detail.dateid)) end as elapse,
        (detail.db-detail.cr)-ifnull((select sum(vpay.cr-vpay.db) from ( 
          select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
          from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
          left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
          where detail.refx > 0 and date(head.dateid) between '$startdate' and '$enddate') as vpay where vpay.refx=detail.trno and vpay.linex=detail.line),0) as balance,
          head.yourref,dl.receivedate
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join delstatus as dl on dl.trno = detail.trno
        where  coa.alias not in ('AR5') and date(head.dateid) between '$startdate' and '$enddate'  $filter $filter1
        union all
        select 'z' as tr,'' as doc,'' as client, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref,'' as receivedate
        ) as x
        group by tr, client,clientname,doc, name,elapse,receivedate 
        order by tr, clientname";


        break;
    } //end switch


    return $query;
  }

  public function reportDefault_QUERY_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

     

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    if ($companyid == 32) { //3m
      $agentid = $config['params']['dataparams']['agentid'];
      if ($agentid != '' && $agentid != 0) $filter2 = " and head.agentid=" . $agentid;
      $addfields3m = ",head.doc, client.brgy, client.area ";
      $addfields3m2 = ",'' as doc, '' as brgy, '' as area";
      $addfields3m3 = ",doc,brgy,area";
    }


    $elapsedate = 'detail.dateid';
    if ($companyid == 19) { //housegem
      $elapsedate = 'head.deldate';
    }

    if ($companyid == 21) { //kinggeorge
      $elapsedate = 'case when head.due is null then detail.dateid else head.due end';
    }

    switch ($reporttype) {
      case '1': // DETAILED
        switch ($companyid) {
          case 11: //summit
            $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,reference
                      from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
                      date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
                      (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,
                      ifnull((select group_concat(distinct ref) from gldetail left join glhead on glhead.trno=gldetail.trno
                      where gldetail.trno=detail.trno and gldetail.line=detail.line and glhead.doc='AR' ),'') as reference
                      from (arledger as detail 
                      left join client on client.clientid=detail.clientid)
                      left join cntnum on cntnum.trno=detail.trno
                      left join glhead as head on head.trno=detail.trno
                      left join coa on coa.acnoid=detail.acnoid
                      where detail.bal<>0  $filter $filter1 ) as x
                      group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,reference
                      order by tr, clientname";
            break;

          case 21: //kinggeorge
            $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,reference,terms
                      from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
                      date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
                      (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,
                      ifnull((select group_concat(distinct ref) from gldetail left join glhead on glhead.trno=gldetail.trno
                      where gldetail.trno=detail.trno and gldetail.line=detail.line and glhead.doc='AR' ),'') as reference,head.terms
                      from (arledger as detail 
                      left join client on client.clientid=detail.clientid)
                      left join cntnum on cntnum.trno=detail.trno
                      left join glhead as head on head.trno=detail.trno
                      left join coa on coa.acnoid=detail.acnoid
                      where detail.bal<>0  $filter $filter1 ) as x
                      group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,reference,terms
                      order by tr, clientname";
            break;

          case 32: //3m
            $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate,brgy,area
            from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate,
            client.brgy, client.area
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2 ) as x
            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate,brgy,area
            order by tr, clientname";
            break;
          default:
            $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate
            from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2 ) as x
            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate
            order by tr, clientname";
            break;
        }

        break;
      case '0': // SUMMARIZED
          $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m3 . "
          from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate " . $addfields3m . "
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join category_masterfile as cat on cat.cat_id = client.category
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join coa on coa.acnoid=gdetail.acnoid
          where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter $filter1 $filter2
          union all
          select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref, null as deldate " . $addfields3m2 . "
          ) as x
          group by tr, clientname, name,elapse " . $addfields3m3 . "
          order by tr, clientname";
        break;
    } //end switch

    return $query;
  }

  public function reportDefault_QUERY_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    if ($companyid == 32) { //3m
      $agent = $config['params']['dataparams']['agent'];
      if ($agent != '') $filter3 = " and head.agent='" . $agent . "'";
      $addfields3m = ", head.doc, client.brgy, client.area ";
      $addfields3m2 = ", doc, brgy, area";
      $addfields3m3 = ", '' as doc, '' as brgy, '' as area";
    }

    $elapsedate = 'head.dateid';
    if ($companyid == 19) { //housegem
      $elapsedate = 'head.deldate';
    }
    if ($companyid == 21) { //kinggeorge
      $elapsedate = 'case when head.due is null then detail.dateid else head.due end';
    }

    switch ($reporttype) {
      case '1': // DETAILED
        if ($companyid == 11) { //summit
          $ref1 = "detail.ref as reference";
          $ref2 = " '' as reference";
        } else {
          $ref1 = "detail.ref";
          $ref2 = " '' as ref";
        }

        if ($companyid == 21) { //kinggeorge
          $field = ",head.terms";
        } elseif ($companyid == 32) { //3m
          $field = ",client.brgy, client.area";
        } else {
          $field = "";
        }



        $query = "select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        detail.db as balance,head.yourref,date(head.deldate) as deldate $field
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate $field
        union all
        select cntnum.center, 'u' as tr,head.trno,head.doc,$ref2, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref " . $addfields3m . "
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db " . $addfields3m . "
        ) as t
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref " . $addfields3m . "
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref " . $addfields3m . ") as x
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select 'z' as clientname, '' as name, '' as balance, '' as elapse " . $addfields3m3 . "
        order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function reportDefault_QUERY_all($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    // (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    if ($companyid == 32) { //3m
      $agent = $config['params']['dataparams']['agent'];
      if ($agent != '') $filter3 = " and head.agent='" . $agent . "'";
      $addfields3m = ", head.doc, client.brgy, client.area ";
      $addfields3m2 = ", doc, brgy, area";
      $addfields3m3 = ", '' as doc, '' as brgy, '' as area";
    }

    $elapsedate = 'head.dateid';
    if ($companyid == 19) { //housegem
      $elapsedate = 'head.deldate';
    }
    if ($companyid == 21) { //kinggeorge
      $elapsedate = 'case when head.due is null then detail.dateid else head.due end';
    }

    $query = "";

    switch ($reporttype) {
      case '1': // DETAILED

        if ($companyid == 11) { //summit
          $ref1 = "detail.ref as reference";
          $ref2 = " '' as reference";
        } else {
          $ref1 = "detail.ref";
          $ref2 = " '' as ref";
        }

        if ($companyid == 21) { //kinggeorge
          $field = ",head.terms";
        } elseif ($companyid == 32) { //3m
          $field = ",client.brgy, client.area";
        } else {
          $field = "";
        }

        $query = "select ref,clientname,dateid,docno, name, sum(balance) as balance,elapse
            from (
            select cntnum.center, 'p' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno,  datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
            head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2

          union all

          select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
                  date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
                  detail.db as balance,head.yourref,date(head.deldate) as deldate $field
                  from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join cntnum on cntnum.trno=head.trno
                  where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
                  group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate  $field
                  
                  union all
                 
                  select cntnum.center, 'u' as tr,head.trno,head.doc, $ref2, client.clientname, ifnull(client.clientname,'no name') as name,
                  date(head.dateid) as dateid, head.docno,  datediff(now(), $elapsedate) as elapse,
                  sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
                  from lahead as head left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join cntnum on cntnum.trno=head.trno
                  where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
                  group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
                  order by clientname, dateid, docno) as x
                        group by clientname, dateid,docno,name,ref,elapse
                        order by  clientname";


        break;

      case '0': // SUMMARIZED

        $query = "select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m3 . "
        from (
        select cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
        head.yourref,date(head.deldate) as deldate  " . $addfields3m . "
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter $filter1 $filter2
        union all
        select  '' as center, 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, 0 as balance,'' as yourref, null as deldate " . $addfields3m2 . ") as x
        group by  clientname,  name,elapse " . $addfields3m3 . "


        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,
        head.yourref,date(head.deldate) as deldate  " . $addfields3m . "
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join category_masterfile as cat on cat.cat_id = client.category
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db, head.deldate" . $addfields3m . ") as t
        group by clientname, elapse, name  " . $addfields3m2 . "

        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance,
        head.yourref,date(head.deldate) as deldate " . $addfields3m . "
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, head.deldate" . $addfields3m . ") as x
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select   'z' as clientname, '' as name, 0 as balance, '' as elapse " . $addfields3m3 . "
        order by clientname, name";
        break;
    } //end swicth
    return $query;
  }

  public function elsi_QUERY_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }
    
    $filter1 .= "";

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    
    $elapsedate = 'detail.dateid';
    
    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate
            from (select 'p' as tr,head.trno,head.doc,detail.ref, info.clientname, ifnull(info.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            left join heahead as ea on ea.catrno = cntnum.trno
            left join heainfo as info on info.trno = ea.trno
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2 ) as x
            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate
            order by tr, clientname";

        break;
      case '0': // SUMMARIZED
        $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m3 . "
        from (select 'p' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate " . $addfields3m . "
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter $filter1 $filter2
        union all
        select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref, null as deldate " . $addfields3m2 . "
        ) as x
        group by tr, clientname, name,elapse " . $addfields3m3 . "
        order by tr, clientname";

        break;
    } //end switch
    return $query;
  }

  public function elsi_QUERY_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    $filter1 .= "";

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
    

    $elapsedate = 'head.dateid';
    
    switch ($reporttype) {
      case '1': // DETAILED
        $ref1 = "detail.ref";
        $ref2 = " '' as ref";
        $field = "";

        $query = "select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, info.clientname, ifnull(info.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        detail.db as balance,head.yourref,date(head.deldate) as deldate $field
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate $field
        union all
        select cntnum.center, 'u' as tr,head.trno,head.doc,$ref2, info.clientname, ifnull(info.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
        sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
        group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref " . $addfields3m . "
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref,detail.db " . $addfields3m . "
        ) as t
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref " . $addfields3m . "
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
        group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref " . $addfields3m . ") as x
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select 'z' as clientname, '' as name, '' as balance, '' as elapse " . $addfields3m3 . "
        order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function elsi_QUERY_all($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    // (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

   
    $filter1 .= "";

    $addfields3m = "";
    $addfields3m2 = "";
    $addfields3m3 = "";
   
    $elapsedate = 'head.dateid';
    
    switch ($reporttype) {
      case '1': // DETAILED

        $ref1 = "detail.ref";
        $ref2 = " '' as ref";
        $field = "";

        $query = "select ref,clientname,dateid,docno, name, sum(balance) as balance,elapse
            from (
            select cntnum.center, 'p' as tr,head.trno,head.doc,$ref1, info.clientname, ifnull(info.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno,  datediff(now(), $elapsedate) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
            head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join heahead as ea on ea.catrno = cntnum.trno
            left join heainfo as info on info.trno = ea.trno
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2

          union all

          select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, info.clientname, ifnull(info.clientname,'no name') as name,
                  date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
                  detail.db as balance,head.yourref,date(head.deldate) as deldate $field
                  from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  left join heahead as ea on ea.catrno = cntnum.trno
                  left join heainfo as info on info.trno = ea.trno
                  where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
                  group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate  $field
                  
                  union all
                 
                  select cntnum.center, 'u' as tr,head.trno,head.doc, $ref2, info.clientname, ifnull(info.clientname,'no name') as name,
                  date(head.dateid) as dateid, head.docno,  datediff(now(), $elapsedate) as elapse,
                  sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate $field
                  from lahead as head left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join cntnum on cntnum.trno=head.trno
                  left join heahead as ea on ea.catrno = cntnum.trno
                  left join heainfo as info on info.trno = ea.trno
                  where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
                  group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate $field
                  order by clientname, dateid, docno) as x
                        group by clientname, dateid,docno,name,ref,elapse
                        order by  clientname";


        break;

      case '0': // SUMMARIZED

        $query = "select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m3 . "
        from (
        select cntnum.center, 'p' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
        head.yourref,date(head.deldate) as deldate  " . $addfields3m . "
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter $filter1 $filter2
        union all
        select  '' as center, 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, 0 as balance,'' as yourref, null as deldate " . $addfields3m2 . ") as x
        group by  clientname,  name,elapse " . $addfields3m3 . "


        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,
        head.yourref,date(head.deldate) as deldate  " . $addfields3m . "
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
        group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref,detail.db, head.deldate" . $addfields3m . ") as t
        group by clientname, elapse, name  " . $addfields3m2 . "

        union all

        select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse " . $addfields3m2 . "
        from (
        select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance,
        head.yourref,date(head.deldate) as deldate " . $addfields3m . "
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        left join heahead as ea on ea.catrno = cntnum.trno
        left join heainfo as info on info.trno = ea.trno
        where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
        group by cntnum.center, info.clientname, head.dateid, head.docno, head.yourref, head.deldate" . $addfields3m . ") as x
        group by clientname, elapse, name " . $addfields3m2 . "
        union all
        select   'z' as clientname, '' as name, 0 as balance, '' as elapse " . $addfields3m3 . "
        order by clientname, name";
        break;
    } //end swicth
    return $query;
  }

  public function vitaline_query_posted($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $agent       = $config['params']['dataparams']['agent'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $type   = $config['params']['dataparams']['typeofreport'];

    $format = "";
    $filter = "";


    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($agent != "") {
      $filter .= " and agent.client='$agent' ";
    }

    switch ($type) {
      case 'report': // sales report
        $format = "cntnum.doc IN ('AR','SJ')";
        break;
      case 'lessreturn': // less return
        $format = "cntnum.doc IN ('AR','SJ','CM')";
        break;
      case 'return': // sales report
        $format = "cntnum.doc IN ('AR','CM')";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select 'p' as tr, case
        when left(coa.acno,5) = '\\\\1105' then 'Accounts receivables - trade'
        when left(coa.acno,5) = '\\\\1106' then 'Accounts receivables - non trade' end as acno, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno,
        case
          when head.doc = 'AR' then datediff(now(), detail.dateid)
          else datediff(now(), head.due)
        end as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance, detail.db, detail.cr, head.yourref,
        case
          when head.doc = 'AR' then date(detail.dateid)
          else date(head.due)
        end as due, ifnull(head.terms,'') as terms, ifnull(gldetail.ref,'') as ref,
        agent.clientname as agentname
        from (arledger as detail 
        left join client on client.clientid=detail.clientid)
        left join client as agent on agent.clientid=detail.agentid
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        left join gldetail on head.trno = gldetail.trno
        left join coa on coa.acnoid=detail.acnoid
        where $format and detail.bal<>0  $filter 
        group by coa.acno, client.clientname,
        detail.dateid, detail.docno,head.yourref,detail.db,detail.bal, head.due, head.doc, head.terms, gldetail.ref, detail.db, detail.cr, agent.clientname
        order by tr, coa.acno, client.clientname, detail.dateid, detail.docno";
        break;
      case '0': // SUMMARIZED
        $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse
        from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref
        from (arledger as detail left join client on client.clientid=detail.clientid)
        left join client as agent on agent.clientid=detail.agentid
        left join cntnum on cntnum.trno=detail.trno
        left join glhead as head on head.trno=detail.trno
        where $format and detail.bal<>0  $filter
        union all
        select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref
        ) as x
        group by tr, clientname, name,elapse 
        order by tr, clientname";

        break;
    } //end switch
    return $query;
  }

  public function vitaline_query_unposted($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $agent       = $config['params']['dataparams']['agent'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $type   = $config['params']['dataparams']['typeofreport'];

    $filter = "";


    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    switch ($type) {
      case 'report': // sales report
        $format = "cntnum.doc IN ('AR','SJ')";
        break;
      case 'lessreturn': // less return
        $format = "cntnum.doc IN ('AR','SJ','CM')";
        break;
      case 'return': // sales report
        $format = "cntnum.doc IN ('AR','CM')";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref, coa.acno, detail.db as db, detail.cr as cr, head.due, head.terms,
        detail.ref as ref, agent.clientname as agentname
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join client as agent on agent.client=head.agent
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where $format and left(coa.alias,2)='AR'  $filter group by cntnum.center, client.clientname,
        head.dateid, head.docno, head.yourref, detail.db, coa.acno, detail.db, detail.cr, head.due, head.terms,detail.ref, agent.clientname
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join client as agent on agent.client=head.agent
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where $format and left(coa.alias,2)='AR'  $filter) as t
        group by clientname, elapse, name
        order by clientname, name";
        break;
    } //end swicth
    return $query;
  }

  public function MITSUKOSHI_QUERY_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $filter = "";

    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db
        union all
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SD','SE','SF','SJ','CM')  $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db
        ) as t
        group by clientname, elapse, name
        union all
        select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse
        from (
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno
        where cntnum.doc IN ('SD','SE','SF','SJ','CM')  $filter 
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref) as x
        group by clientname, elapse, name
        union all
        select 'z' as clientname, '' as name, '' as balance, '' as elapse
        order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

   public function ericco_QUERY_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

      // Added 2026-02-27 - Elmer
    $categoryname  = $config['params']['dataparams']['category_name'];
    $categoryid  = $config['params']['dataparams']['category_id'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
      // Added 2026-02-27 - end

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

          // Added Filter 2026--02-27 -start
    if ($categoryname != '') {
      if ($categoryid != '0') {
          $filter   .= " and client.category = '" . $categoryid . "'";
      }
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }
        // Added Filter 2026--02-27 -end

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }

    switch ($reporttype) {
      case '1': // DETAILED
            $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate
            from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), head.dateid) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2 ) as x
            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate
            order by tr, clientname"; 
            // var_dump($query);        
        break;

      case '0': // SUMMARIZED
            $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
            from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), head.dateid) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate 
            from (arledger as detail left join client on client.clientid=detail.clientid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter $filter1 $filter2
            union all
            select 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, '' as balance, '' as yourref, null as deldate 
            ) as x
            group by tr, clientname, name,elapse 
            order by tr, clientname";
        break;

      case '2': // GROUP
            $query = "select groupid as `groupid`,
            sum(case when elapse between 0 and 30 then balance else 0 end) as `Current`,
            sum(case when elapse between 31 and 60 then balance else 0 end) as `31-60 days`,
            sum(case when elapse between 61 and 90 then balance else 0 end) as `61-90 days`,
            sum(case when elapse between 91 and 120 then balance else 0 end) as `91-120 days`,
            sum(case when elapse > 120 then balance else 0 end) as `120+ days`,
            sum(balance) as TOTAL
            from (select client.groupid,datediff(now(), head.dateid) as elapse,
            (case when detail.db > 0 then detail.bal else detail.bal * -1 end) as balance
            from arledger as detail
            left join client on client.clientid = detail.clientid
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where detail.bal <> 0 and left(coa.alias,2) = 'AR' and detail.dateid <= now() $filter $filter1 $filter2
            ) as x
            group by groupid
            order by groupid";
        break;
    } //end switch
    return $query;
  }

  public function ericco_QUERY_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

     // Added 2026-02-27 - Elmer
    $categoryname  = $config['params']['dataparams']['category_name'];
    $categoryid  = $config['params']['dataparams']['category_id'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    // Added 2026-02-27 - end

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

        // Added Filter 2026--02-27 -start
    if ($categoryname != '') {
      if ($categoryid != '0') {
          $filter   .= " and client.category = '" . $categoryid . "'";
      }
    }
    // if ($groupid != "") {
    //   $filter .= " and client.groupid='$groupid'";
    // }
    if ($groupid != "") {
    $filter .= " and client.groupid='$groupid'";
    }
        // Added Filter 2026--02-27 -end


    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }


    
    switch ($reporttype) {
      case '1': // DETAILED
            $query = "select cntnum.center, 'u' as tr,head.trno,head.doc, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            detail.db as balance,head.yourref,date(head.deldate) as deldate 
            from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
            left join client on client.client=head.client)
            left join coa on coa.acnoid=detail.acnoid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate
            union all
            select cntnum.center, 'u' as tr,head.trno,head.doc, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance, head.yourref,date(head.deldate) as deldate 
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate
            order by clientname, dateid, docno";
            // var_dump($query);
        break;

      case '0': // SUMMARIZED
            $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
            from (
            select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            detail.db as balance,head.yourref 
            from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
            left join client on client.client=head.client)
            left join coa on coa.acnoid=detail.acnoid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db
            ) as t
            group by clientname, elapse, name 
            union all
            select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
            from (
            select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance, head.yourref 
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref ) as x
            group by clientname, elapse, name
            union all
            select 'z' as clientname, '' as name, '' as balance, '' as elapse
            order by clientname, name";
            // var_dump($query);
        break;

      case '2': // GROUP 
            $query = "select  groupid as `groupid`,
            sum(case when elapse between 0 and 30 then balance else 0 end) as `Current`,
            sum(case when elapse between 31 and 60 then balance else 0 end) as `31-60 days`,
            sum(case when elapse between 61 and 90 then balance else 0 end) as `61-90 days`,
            sum(case when elapse between 91 and 120 then balance else 0 end) as `91-120 days`,
            sum(case when elapse > 120 then balance else 0 end) as `120+ days`,
            sum(balance) as TOTAL
            from (select client.groupid, datediff(now(), head.dateid) as elapse, detail.db as balance
            from lahead as head
            left join ladetail as detail on detail.trno = head.trno
            left join client on client.client = head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            where cntnum.doc in ('GJ','AR','CR') and left(coa.alias,2) = 'AR'
            and detail.refx = 0 $filter $filter1 $filter2 $filter3
            union all
            select client.groupid,datediff(now(), head.dateid) as elapse,sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            inner join client on client.client = head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno = head.trno
            where cntnum.doc in ('SJ','MJ','CM') $filter $filter1 $filter3
            group by client.groupid, head.dateid
            ) as combined
            group by groupid
            order by groupid";
            // var_dump($query);
        break;
    } //end swicth

    return $query;
  }

  public function ericco_QUERY_ALL($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];
    $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

        // Added 2026-02-27 - Elmer
    $categoryname  = $config['params']['dataparams']['category_name'];
    $categoryid  = $config['params']['dataparams']['category_id'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
        // Added 2026-02-27 - end

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    // (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname

          // Added Filter 2026--02-27 -start
    if ($categoryname != '') {
      if ($categoryid != '0') {
          $filter   .= " and client.category = '" . $categoryid . "'";
      }
    }
    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }
        // Added Filter 2026--02-27 -end
        

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

 
    switch ($reporttype) {
      case '1': // DETAILED

        $query = "select clientname,dateid,docno, name, sum(balance) as balance,elapse
            from (
            select cntnum.center, 'p' as tr,head.trno,head.doc, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno,  datediff(now(), head.dateid) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
            date(head.deldate) as deldate
            from (arledger as detail 
            left join client on client.clientid=detail.clientid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter $filter1 $filter2
            union all
            select cntnum.center, 'u' as tr,head.trno,head.doc, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            detail.db as balance,date(head.deldate) as deldate
            from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
            left join client on client.client=head.client)
            left join coa on coa.acnoid=detail.acnoid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate 
            union all
            select cntnum.center, 'u' as tr,head.trno,head.doc, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno,  datediff(now(), head.dateid) as elapse,
            sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance,date(head.deldate) as deldate
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno,head.trno,head.doc,head.deldate 
            order by clientname, dateid, docno) as x
            group by clientname, dateid,docno,name,elapse
            order by  clientname";
            // var_dump($query);
        break;

      case '0': // SUMMARIZED
            $query = "select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
            from (
            select cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), head.dateid) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
            head.yourref,date(head.deldate) as deldate  
            from (arledger as detail left join client on client.clientid=detail.clientid)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter $filter1 $filter2
            union all
            select  '' as center, 'z' as tr, '' as clientname, '' as name, '' as dateid, '' as docno, '' as elapse, 0 as balance,'' as yourref, null as deldate ) as x
            group by  clientname,  name,elapse 
            union all
            select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse 
            from (
            select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            detail.db as balance,
            head.yourref,date(head.deldate) as deldate  
            from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
            left join client on client.client=head.client)
            left join category_masterfile as cat on cat.cat_id = client.category
            left join coa on coa.acnoid=detail.acnoid)
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db, head.deldate) as t
            group by clientname, elapse, name 
            union all
            select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, name, sum(balance) as balance,elapse
            from (
            select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance,
            head.yourref,date(head.deldate) as deldate 
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where cntnum.doc IN ('SJ','MJ','CM')  $filter $filter1 $filter3
            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref, head.deldate) as x
            group by clientname, elapse, name 
            union all
            select   'z' as clientname, '' as name, 0 as balance, '' as elapse 
            order by clientname, name";
        break;

      case '2': // GROUP
            $query = "select groupid as `groupid`,
            sum(case when elapse between 0 and 30 then balance else 0 end) as `Current`,
            sum(case when elapse between 31 and 60 then balance else 0 end) as `31-60 days`,
            sum(case when elapse between 61 and 90 then balance else 0 end) as `61-90 days`,
            sum(case when elapse between 91 and 120 then balance else 0 end) as `91-120 days`,
            sum(case when elapse > 120 then balance else 0 end) as `120+ days`,
            sum(balance) as TOTAL
            from (select client.groupid,datediff(now(), head.dateid) as elapse,
            (case when detail.db > 0 then detail.bal else detail.bal * -1 end) as balance
            from arledger as detail
            inner join client on client.clientid = detail.clientid
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno = detail.trno
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where detail.bal <> 0 and left(coa.alias,2) = 'AR'
            and detail.dateid <= now() $filter $filter1 $filter2
            union all
            select client.groupid,datediff(now(), head.dateid) as elapse, detail.db as balance
            from lahead as head
            left join ladetail as detail on detail.trno = head.trno
            inner join client on client.client = head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno
            where cntnum.doc in ('GJ','AR','CR') and left(coa.alias,2) = 'AR'
            and detail.refx = 0 $filter $filter1 $filter2 $filter3
            union all
            select client.groupid, datediff(now(), head.dateid) as elapse, 
            sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            inner join client on client.client = head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno = head.trno
            where cntnum.doc in ('SJ','MJ','CM')  $filter $filter1 $filter3
            group by client.groupid, head.dateid
            ) as combined
            group by groupid
            order by groupid";

            // var_dump($query);
      break;
    } //end swicth
    return $query;
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

    $contra       = $config['params']['dataparams']['contra'];

    $dept = "";
    if ($companyid == 10) { //afti
      $contra   = $config['params']['dataparams']['contra'];
    }

    
    if ($companyid == 55) { //afli
      $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
      $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    switch ($posttype) {
      case 0: //posted
        $reporttype = 'Posted';
        break;
      case 1: //unposted
        $reporttype = 'Unposted';
        break;
      case 2: //all
        $reporttype = 'ALL';
        break;
    }

    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DETAILED CURRENT CUSTOMER RECEIVABLES AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= '</br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
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
    if ($contra == '') {
      $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($companyid == 32) { //3m
      $agent = $config['params']['dataparams']['agentname'];
      if ($agent == '') $agent = 'ALL';
      $str .= $this->reporter->col('Agent: ' . strtoupper($agent), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
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

    if ($companyid == 10 || $companyid == 12 || $companyid == 55) { //afti, afti usd, afli
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '660px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 34) { // evergreen
      $str .= $this->reporter->col('PLAN HOLDERS NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CUSTOMER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    if ($companyid == 26) { //bee healthy
      $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Current', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_DETAILED($config, $result)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $this->reporter->linecounter = 0;
    $count = 52;
    $page = 55;

    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_DETAILED($config);

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

    $customer = "";
    $docno = "";
    $subtotal = 0;

    $suba = 0;
    $subb = 0;
    $subc = 0;
    $subd = 0;
    $sube = 0;

    foreach ($result as $key => $data) {
      if ($customer != $data->clientname) {
        if ($customer != '') {
          $this->reporter->addline();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 11: //summit
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('SUB TOTAL : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($suba, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($subb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($subc, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($subd, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($sube, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;

            case 26: //bee healthy
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;

            default:
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;
          }

          $str .= $this->reporter->endrow();

          $str .= '<br/>';
          $subtotal = 0;
          $suba = 0;
          $subb = 0;
          $subc = 0;
          $subd = 0;
          $sube = 0;

          if ($companyid != 36) { //not rozlab
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->page_break();
              $str .= $this->displayHeader_DETAILED($config);
              $page = $page + $count;
            } //end if
          }
        }

        $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 32) { //3m
          $str .= $this->reporter->col($data->clientname . ' - ' . $data->brgy . ', ' . $data->area, $layoutsize, null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col($data->clientname, $layoutsize, null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();

        if ($companyid != 36) { //not rozlab
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader_DETAILED($config);
            $page = $page + $count;
          }
        }
      }

      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;

      if ($data->elapse >= 0 && $data->elapse <= 30) {
        $a = $data->balance;
      }
      if ($data->elapse >= 31 && $data->elapse <= 60) {
        $b = $data->balance;
      }
      if ($data->elapse >= 61 && $data->elapse <= 90) {
        $c = $data->balance;
      }
      if ($data->elapse >= 91 && $data->elapse <= 120) {
        $d = $data->balance;
      }
      if ($data->elapse >= 121) {
        $e = $data->balance;
      }

      $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      if ($companyid == 11) { //summit
        if ($data->doc == 'AR') {
          if ($data->reference == "") {
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->reference, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          }
        } else {
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }
      } elseif ($companyid == 26) { //bee healthy
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      }

      if ($companyid == 19) { //housegem
        $str .= $this->reporter->col($data->deldate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }
              
      $str .= $this->reporter->col(($a > 0 ? number_format($a, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($b > 0 ? number_format($b, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($c > 0 ? number_format($c, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($d > 0 ? number_format($d, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($e > 0 ? number_format($e, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');


      $str .= $this->reporter->endrow();



      $tota += $a;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $subtotal += $data->balance;
      $gt += $data->balance;
      $customer = $data->clientname;

      $suba += $a;
      $subb += $b;
      $subc += $c;
      $subd += $d;
      $sube += $e;


      if ($companyid != 36) { //not rozlab
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader_DETAILED($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 11: //summit
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($suba, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($subb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($subc, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($subd, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($sube, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 26: //bee healthy
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    if ($companyid == 26) { //bee healthy
      $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function displayHeader_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $contra       = $config['params']['dataparams']['contra'];
    $dept = "";

    if ($companyid == 10) { //afti
      $contra   = $config['params']['dataparams']['contra'];
    }

    
    if ($companyid == 55) { //afli
      $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    }


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
      $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
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

    $str .=  '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CURRENT CUSTOMER RECEIVABLES AGING - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .=  '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($contra == '') {
      $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Acount: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');



    switch ($posttype) {
      case 0: //posted
        $reporttype = 'Posted';
        break;
      case 1: //unposted
        $reporttype = 'Unposted';
        break;
      case 2: //all
        $reporttype = 'ALL';
        break;
    }

    if ($filtercenter == "") {
      $filtercenter = "ALL";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    if ($companyid == 32) { //3m
      $agent = $config['params']['dataparams']['agentname'];
      if ($agent == '') $agent = 'ALL';
      $str .= $this->reporter->col('Agent: ' . strtoupper($agent), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->pagenumber('Page', $font);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($companyid == 10 || $companyid == 12|| $companyid == 55) { //afti, afti usd, afli
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '660px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('CLIENT NAME', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '66', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '66', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('CLIENT NAME', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Barangay', '95', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Area', '95', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Return', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 34: //evergreen
        $str .= $this->reporter->col('PLAN HOLDERS NAME', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('CLIENT NAME', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_SUMMARIZED3M($config, $result)
  {
    $this->reporter->linecounter = 0;
    $count = 60;
    $page = 64;
    $layoutsize = '1000';
    $companyid = $config['params']['companyid'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_SUMMARIZED($config);

    $clientname = "";
    $ret = 0;
    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $totret = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $gt = 0;

    $subtotret = 0;
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

    foreach ($result as $key => $data) {
      $ret = 0;
      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;
      if ($clientname == '') {
        $clientname = $data->clientname;
        if ($data->doc == 'CM') {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $ret = 0;
          $ret = $data->balance;
          $subtotret = $subtotret + $ret;
          $subgt = $subgt + $data->balance;
        } else {
          if ($data->elapse >= 0 && $data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse > 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        }
      } else {
        if ($clientname != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->brgy, '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->area, '95', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtota > 0 ? number_format($subtota, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotret == 0 ? '-' : number_format($subtotret, 2)), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotb > 0 ? number_format($subtotb, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotc > 0 ? number_format($subtotc, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotd > 0 ? number_format($subtotd, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtote > 0 ? number_format($subtote, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;
          $subtotret = 0;

          $clientname = $data->clientname;
          if ($data->doc == 'CM') {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $ret = 0;
            $ret = $data->balance;
            $subtotret = $subtotret + $ret;
            $subgt = $subgt + $data->balance;
          } else {
            if ($data->elapse >= 0 && $data->elapse <= 30) {
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $a = $data->balance;
              $subtota = $subtota + $a;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 31 && $data->elapse <= 60) {
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $b = $data->balance;
              $subtotb = $subtotb + $b;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 61 && $data->elapse <= 90) {
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $c = $data->balance;
              $subtotc = $subtotc + $c;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 91 && $data->elapse <= 120) {
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $d = $data->balance;
              $subtotd = $subtotd + $d;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 121) {
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $e = $data->balance;
              $subtote = $subtote + $e;
              $subgt = $subgt + $data->balance;
            }
          }
        } else {
          if ($data->doc == 'CM') {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $ret = 0;
            $ret = $data->balance;
            $subtotret = $subtotret + $ret;
            $subgt = $subgt + $data->balance;
          } else {
            if ($data->elapse >= 0 && $data->elapse <= 30) {
              $ret = 0;
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $a = $data->balance;
              $subtota = $subtota + $a;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 31 && $data->elapse <= 60) {
              $ret = 0;
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $b = $data->balance;
              $subtotb = $subtotb + $b;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 61 && $data->elapse <= 90) {
              $ret = 0;
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $c = $data->balance;
              $subtotc = $subtotc + $c;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 91 && $data->elapse <= 120) {
              $ret = 0;
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $d = $data->balance;
              $subtotd = $subtotd + $d;
              $subgt = $subgt + $data->balance;
            }
            if ($data->elapse >= 121) {
              $ret = 0;
              $a = 0;
              $b = 0;
              $c = 0;
              $d = 0;
              $e = 0;
              $e = $data->balance;
              $subtote = $subtote + $e;
              $subgt = $subgt + $data->balance;
            }
          }
        }
      }
      $tota += $a;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $totret += $ret;
      $gt = $gt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_SUMMARIZED($config);
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '110', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '95', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '95', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totret, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote + $totret, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result)
  {

    $this->reporter->linecounter = 0;
    $count = 60;
    $page = 64;
    $layoutsize = '1000';
    $companyid = $config['params']['companyid'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_SUMMARIZED($config);

    $clientname = "";
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

    foreach ($result as $key => $data) {
      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;
      if ($clientname == '') {

        $clientname = $data->clientname;

        if ($data->elapse >= 0 && $data->elapse <= 30) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $a = $data->balance;
          $subtota = $subtota + $a;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 31 && $data->elapse <= 60) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $b = $data->balance;
          $subtotb = $subtotb + $b;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 61 && $data->elapse <= 90) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $c = $data->balance;
          $subtotc = $subtotc + $c;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 91 && $data->elapse <= 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $d = $data->balance;
          $subtotd = $subtotd + $d;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse > 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $e = $data->balance;
          $subtote = $subtote + $e;
          $subgt = $subgt + $data->balance;
        }
      } else {

        if ($clientname != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();

          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->begintable($layoutsize);
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->addline();
              $str .= $this->reporter->col($clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($subtota > 0 ? number_format($subtota, 2) : '-'), '66', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($subtotb > 0 ? number_format($subtotb, 2) : '-'), '66', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($subtotc > 0 ? number_format($subtotc, 2) : '-'), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($subtotd > 0 ? number_format($subtotd, 2) : '-'), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($subtote > 0 ? number_format($subtote, 2) : '-'), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($subgt, 2), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              break;
            default:
              if ($subgt != 0) {
                $str .= $this->reporter->col($clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($subtota > 0 ? number_format($subtota, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($subtotb > 0 ? number_format($subtotb, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($subtotc > 0 ? number_format($subtotc, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($subtotd > 0 ? number_format($subtotd, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($subtote > 0 ? number_format($subtote, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($subgt, 2), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
              }

              break;
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;

          $clientname = $data->clientname;

          if ($data->elapse >= 0 && $data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 121) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        } else {

          if ($data->elapse >= 0 && $data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 121) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        }
      }
      $tota += $a;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $gt = $gt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_SUMMARIZED($config);
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL : ', '300', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tota, 2), '66', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totb, 2), '66', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totc, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totd, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tote, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        break;
      default:
        $str .= $this->reporter->col('TOTAL : ', '110', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tota, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totb, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totc, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totd, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tote, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function kinggeorge_Header_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $contra       = $config['params']['dataparams']['contra'];

    $dept = "";


    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DETAILED CURRENT CUSTOMER RECEIVABLES AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= '</br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($contra == '') {
      $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');



    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CUSTOMER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Current', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function kinggeorge_LAYOUT_DETAILED($config, $result)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $this->reporter->linecounter = 0;
    $count = 52;
    $page = 55;

    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_Header_DETAILED($config);

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

    $customer = "";
    $docno = "";
    $subtotal = 0;

    $suba = 0;
    $subb = 0;
    $subc = 0;
    $subd = 0;
    $sube = 0;

    foreach ($result as $key => $data) {
      if ($customer != $data->clientname) {
        if ($customer != '') {
          $this->reporter->addline();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');


          $str .= $this->reporter->endrow();

          $str .= '<br/>';
          $subtotal = 0;
          $suba = 0;
          $subb = 0;
          $subc = 0;
          $subd = 0;
          $sube = 0;

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->kinggeorge_Header_DETAILED($config);
            $page = $page + $count;
          } //end if

        }

        $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, $layoutsize, null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->kinggeorge_Header_DETAILED($config);
          $page = $page + $count;
        }
      }

      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;

      if ($data->elapse >= 0 && $data->elapse <= 30) {
        $a = $data->balance;
      }
      if ($data->elapse >= 31 && $data->elapse <= 60) {
        $b = $data->balance;
      }
      if ($data->elapse >= 61 && $data->elapse <= 90) {
        $c = $data->balance;
      }
      if ($data->elapse >= 91 && $data->elapse <= 120) {
        $d = $data->balance;
      }
      if ($data->elapse >= 121) {
        $e = $data->balance;
      }

      $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


      $str .= $this->reporter->col(($a > 0 ? number_format($a, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($b > 0 ? number_format($b, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($c > 0 ? number_format($c, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($d > 0 ? number_format($d, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($e > 0 ? number_format($e, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');


      $str .= $this->reporter->endrow();


      $tota += $a;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $subtotal += $data->balance;
      $gt += $data->balance;
      $customer = $data->clientname;

      $suba += $a;
      $subb += $b;
      $subc += $c;
      $subd += $d;
      $sube += $e;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->kinggeorge_Header_DETAILED($config);
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }



  // vitaline
  private function vitaline_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $agent       = $config['params']['dataparams']['agent'];
    $agentname       = $config['params']['dataparams']['agentname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED CURRENT CUSTOMER RECEIVABLES AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($agent == '') {
      $str .= $this->reporter->col('Agent : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Agent : ' . strtoupper($agentname), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter = '' ? $filtercenter : 'ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DUE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TERMS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('For Collection', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('0-30 days', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REF', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function vitaline_layout_detailed($config, $result)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $agent       = $config['params']['dataparams']['agent'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->vitaline_header_detailed($config);

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;

    $suba = 0;
    $subb = 0;
    $subc = 0;
    $subd = 0;
    $sube = 0;

    $ara = 0;
    $arb = 0;
    $arc = 0;
    $ard = 0;
    $are = 0;


    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;



    $subcol = $totalcol = $arcol = 0;
    $customer = $acno = $docno = "";
    $subtotal = $artotal = $gt = 0;
    foreach ($result as $key => $data) {

      if ($customer != $data->clientname || $acno != $data->acno) {

        if ($customer != '') {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $this->reporter->addline();

          $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('SUB-TOTAL:', '230', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subcol != null ? '(' . number_format(($subcol > 0 ? $subcol : $subcol * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($suba != null ? '(' . number_format(($suba > 0 ? $suba : $suba * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subb != null ? '(' . number_format(($subb > 0 ? $subb : $subb * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subc != null ? '(' . number_format(($subc > 0 ? $subc : $subc * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subd != null ? '(' . number_format(($subd > 0 ? $subd : $subd * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($sube != null ? '(' . number_format(($sube > 0 ? $sube : $sube * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotal != null ? '(' . number_format(($subtotal > 0 ? $subtotal : $subtotal * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $subtotal = $subcol = 0;
          $suba = 0;
          $subb = 0;
          $subc = 0;
          $subd = 0;
          $sube = 0;

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->vitaline_header_detailed($config);
            $page = $page + $count;
          }
        }


        if ($acno != $data->acno) {

          if ($acno != '') {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $this->reporter->addline();

            $str .= $this->reporter->col('', '40', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '40', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('Total ' . $acno, '230', null, false, '2px solid', 'T', 'R', $font, '12', 'B', '', '');
            $str .= $this->reporter->col('', '40', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($arcol != null ? '(' . number_format(($arcol > 0 ? $arcol : $arcol * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($ara != null ? '(' . number_format(($ara > 0 ? $ara : $ara * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($arb != null ? '(' . number_format(($arb > 0 ? $arb : $arb * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($arc != null ? '(' . number_format(($arc > 0 ? $arc : $arc * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($ard != null ? '(' . number_format(($ard > 0 ? $ard : $ard * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($are != null ? '(' . number_format(($are > 0 ? $are : $are * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(($artotal != null ? '(' . number_format(($artotal > 0 ? $artotal : $artotal * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '80', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $artotal = $arcol = 0;
            $ara = 0;
            $arb = 0;
            $arc = 0;
            $ard = 0;
            $are = 0;

            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->page_break();
              $str .= $this->vitaline_header_detailed($config);
              $page = $page + $count;
            }
          }
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->acno, '320', null, false, '2px solid', 'B', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '50', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, '2px solid', 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $this->reporter->addline();
        $str .= $this->reporter->col('[ ' . $data->clientname . ' - ' . $data->agentname . ']', '320', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->vitaline_header_detailed($config);
          $page = $page + $count;
        }
      }


      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;

      if ($data->elapse <= 0) {
        $data->elapse = 0;
      }

      if ($data->elapse >= 0 && $data->elapse < 30) {
        $a = $data->db - $data->cr;
      }
      if ($data->elapse > 31 && $data->elapse < 60) {
        $b = $data->db - $data->cr;
      }
      if ($data->elapse > 61 && $data->elapse < 90) {
        $c = $data->db - $data->cr;
      }
      if ($data->elapse > 91 && $data->elapse < 120) {
        $d = $data->db - $data->cr;
      }
      if ($data->elapse > 120) {
        $e = $data->db - $data->cr;
      }

      $collect = $data->db - $data->cr;
      $balance = $data->balance;

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->due, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->terms, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('(' . number_format(($collect > 0 ? $collect : $collect * -1), 2) . ')', '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($a != null ? '(' . number_format(($a > 0 ? $a : $a * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($b != null ? '(' . number_format(($b > 0 ? $b : $b * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($c != null ? '(' . number_format(($c > 0 ? $c : $c * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($d != null ? '(' . number_format(($d > 0 ? $d : $d * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($e != null ? '(' . number_format(($e > 0 ? $e : $e * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($balance != null ? '(' . number_format(($balance > 0 ? $balance : $balance * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $tota += $a;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;

      $suba += $a;
      $subb += $b;
      $subc += $c;
      $subd += $d;
      $sube += $e;

      $ara += $a;
      $arb += $b;
      $arc += $c;
      $ard += $d;
      $are += $e;

      $subtotal += $data->balance;
      $gt += $data->balance;
      $artotal += $data->balance;

      $subcol += $data->db - $data->cr;
      $totalcol += $data->db - $data->cr;
      $arcol += $data->db - $data->cr;

      $customer = $data->clientname;
      $acno = $data->acno;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->vitaline_header_detailed($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('SUB-TOTAL:', '230', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($subcol != null ? '(' . number_format(($subcol > 0 ? $subcol : $subcol * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($suba != null ? '(' . number_format(($suba > 0 ? $suba : $suba * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($subb != null ? '(' . number_format(($subb > 0 ? $subb : $subb * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($subc != null ? '(' . number_format(($subc > 0 ? $subc : $subc * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($subd != null ? '(' . number_format(($subd > 0 ? $subd : $subd * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($sube != null ? '(' . number_format(($sube > 0 ? $sube : $sube * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($subtotal != null ? '(' . number_format(($subtotal > 0 ? $subtotal : $subtotal * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '40', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total ' . $acno, '230', null, false, '2px solid', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($arcol != null ? '(' . number_format(($arcol > 0 ? $arcol : $arcol * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($ara != null ? '(' . number_format(($ara > 0 ? $ara : $ara * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($arb != null ? '(' . number_format(($arb > 0 ? $arb : $arb * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($arc != null ? '(' . number_format(($arc > 0 ? $arc : $arc * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($ard != null ? '(' . number_format(($ard > 0 ? $ard : $ard * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($are != null ? '(' . number_format(($are > 0 ? $are : $are * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($artotal != null ? '(' . number_format(($artotal > 0 ? $artotal : $artotal * -1), 2) . ')' : '-'), '80', null, false, '2px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $grandtotal = $tota + $totb + $totc + $totd + $tote;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL:', '230', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($totalcol != null ? '(' . number_format(($totalcol > 0 ? $totalcol : $totalcol * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($tota != null ? '(' . number_format(($tota > 0 ? $tota : $tota * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($totb != null ? '(' . number_format(($totb > 0 ? $totb : $totb * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($totc != null ? '(' . number_format(($totc > 0 ? $totc : $totc * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($totd != null ? '(' . number_format(($totd > 0 ? $totd : $totd * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($tote != null ? '(' . number_format(($tote > 0 ? $tote : $tote * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($grandtotal != null ? '(' . number_format(($grandtotal > 0 ? $grandtotal : $grandtotal * -1), 2) . ')' : '-'), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function AFTI_LAYOUT_SUMMARIZED($config, $result)
  {

    $this->reporter->linecounter = 0;
    $count = 60;
    $page = 64;
    $layoutsize = '1000';
    $companyid = $config['params']['companyid'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->AftiHeader_SUMMARIZED($config);

    $clientname = "";
    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $cc = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $totcc = 0;
    $gt = 0;


    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotcc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subgt = 0;
    $a = 0;
    $b = 0;
    $c = 0;
    $cc = 0;
    $d = 0;
    $e = 0;

    foreach ($result as $key => $data) {
      $a = 0;
      $b = 0;
      $c = 0;
      $cc = 0;
      $d = 0;
      $e = 0;
      if ($clientname == '') {

        $clientname = $data->clientname;

        if ($data->elapse <= 0) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $cc = $data->balance;
          $subtotcc = $subtotcc + $cc;
          $subgt = $subgt + $data->balance;
        }

        if ($data->elapse >= 1 && $data->elapse <= 30) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $a = $data->balance;
          $subtota = $subtota + $a;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 31 && $data->elapse <= 60) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $b = $data->balance;
          $subtotb = $subtotb + $b;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 61 && $data->elapse <= 90) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $c = $data->balance;
          $subtotc = $subtotc + $c;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 91 && $data->elapse <= 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $d = $data->balance;
          $subtotd = $subtotd + $d;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse > 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $e = $data->balance;
          $subtote = $subtote + $e;
          $subgt = $subgt + $data->balance;
        }
      } else {

        if ($clientname != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();



          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();

          $str .= $this->reporter->col($clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotcc <> 0 ? number_format($subtotcc, 2) : '-'), '66', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtota <> 0 ? number_format($subtota, 2) : '-'), '66', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotb <> 0 ? number_format($subtotb, 2) : '-'), '66', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotc <> 0 ? number_format($subtotc, 2) : '-'), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotd <> 0 ? number_format($subtotd, 2) : '-'), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtote <> 0 ? number_format($subtote, 2) : '-'), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '67', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotcc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;

          $clientname = $data->clientname;

          if ($data->elapse <= 0) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $cc = $data->balance;
            $subtotcc = $subtotcc + $cc;
            $subgt = $subgt + $data->balance;
          }

          if ($data->elapse >= 1 && $data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 121) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        } else {

          if ($data->elapse <= 0) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $cc = $data->balance;
            $subtotcc = $subtotcc + $cc;
            $subgt = $subgt + $data->balance;
          }

          if ($data->elapse >= 1 && $data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 121) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        }
      }
      $tota += $a;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $totcc += $cc;
      $gt = $gt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->AftiHeader_SUMMARIZED($config);
        $page = $page + $count;
      }
    } // end foreach

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '300', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totcc, 2), '66', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota, 2), '66', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totb, 2), '66', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totc, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totd, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tote, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote + $totcc, 2), '67', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function AftiHeader_SUMMARIZED($config)
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

    $contra   = $config['params']['dataparams']['contra'];
    $dept   = $config['params']['dataparams']['ddeptname'];
    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
    if ($dept != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
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

    $str .=  '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CURRENT CUSTOMER RECEIVABLES AGING - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .=  '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else {
      $posttype = 'Unposted';
    }

    if ($filtercenter == "") {
      $filtercenter = "ALL";
    }

    $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->pagenumber('Page', $font);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '660px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLIENT NAME', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Current', '66', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('1-30 days', '66', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '66', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '67', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function AFTI_LAYOUT_DETAILED($config, $result)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $this->reporter->linecounter = 0;
    $count = 52;
    $page = 55;

    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->AftiHeader_DETAILED($config);

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;
    $cc = 0;

    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totcc = 0;
    $totd = 0;
    $tote = 0;
    $gt = 0;

    $customer = "";
    $docno = "";
    $subtotal = 0;

    $suba = 0;
    $subb = 0;
    $subc = 0;
    $subcc = 0;
    $subd = 0;
    $sube = 0;

    foreach ($result as $key => $data) {
      if ($customer != $data->client) {
        if ($customer != '') {
          $this->reporter->addline();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($companyid == 11) { //summit
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUB TOTAL : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subcc, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($suba, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subc, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subd, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($sube, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          }

          $str .= $this->reporter->endrow();

          $str .= '<br/>';
          $subtotal = 0;
          $suba = 0;
          $subb = 0;
          $subc = 0;
          $subcc = 0;
          $subd = 0;
          $sube = 0;

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->AftiHeader_DETAILED($config);
            $page = $page + $count;
          } //end if

        }

        $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname . '(' . $data->client . ')', $layoutsize, null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->AftiHeader_DETAILED($config);
          $page = $page + $count;
        }
      }

      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;
      $cc = 0;

      if ($data->elapse <= 0) {
        $cc = $data->balance;
      }

      if ($data->elapse >= 1 && $data->elapse <= 30) {
        $a = $data->balance;
      }
      if ($data->elapse >= 31 && $data->elapse <= 60) {
        $b = $data->balance;
      }
      if ($data->elapse >= 61 && $data->elapse <= 90) {
        $c = $data->balance;
      }
      if ($data->elapse >= 91 && $data->elapse <= 120) {
        $d = $data->balance;
      }
      if ($data->elapse >= 121) {
        $e = $data->balance;
      }

      $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(($cc <> 0 ? number_format($cc, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($a <> 0 ? number_format($a, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($b <> 0 ? number_format($b, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($c <> 0 ? number_format($c, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($d <> 0 ? number_format($d, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($e <> 0 ? number_format($e, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');


      $str .= $this->reporter->endrow();



      $tota += $a;
      $totcc += $cc;
      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $subtotal += $data->balance;
      $gt += $data->balance;
      $customer = $data->client;

      $suba += $a;
      $subb += $b;
      $subc += $c;
      $subd += $d;
      $sube += $e;
      $subcc += $cc;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->AftiHeader_DETAILED($config);
        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totcc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote + $totcc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function AftiHeader_DETAILED($config)
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
    $contra   = $config['params']['dataparams']['contra'];

    $dept   = $config['params']['dataparams']['ddeptname'];
    $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
    if ($dept != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DETAILED CURRENT CUSTOMER RECEIVABLES AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= '</br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $startdate . ' - ' . $enddate, '660px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CUSTOMER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Current', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('1-30 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }


  // Added By Elmer 2026-02-27
  private function displayHeader_GROUP($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $contra       = $config['params']['dataparams']['contra'];

    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
   
  
    $str = '';
    $layoutsize = '1050';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CURRENT CUSTOMER RECEIVABLES AGING - CUSTOMER GROUP', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .=  '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
   

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } 
    if ($groupid == '') {
      $str .= $this->reporter->col('Group : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Group : ' . strtoupper($groupid), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } 
    if ($contra == '') {
      $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    switch ($posttype) {
      case '0':
        $posttype = 'Posted';
      break;
      case '1':
        $posttype = 'Unposted';
      break;
      default:
        $posttype = 'All';
      break;
    }
    
    $str .= $this->reporter->col('Transaction: ' . $posttype, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GROUP NAME', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');      
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_GROUP($config, $result)
  {
      $this->reporter->linecounter = 0;
      $count = 60;
      $page = 64;
      $layoutsize = '1050';
      $companyid = $config['params']['companyid'];
      $font = $this->companysetup->getrptfont($config['params']);
      $fontsize = "10";
      $border = "1px solid ";
      $str = '';

      if (empty($result)) {
          return $this->othersClass->emptydata($config);
      }

      $str .= $this->reporter->beginreport($layoutsize);
      $str .= $this->displayHeader_GROUP($config);

      $tota = 0;
      $totb = 0;
      $totc = 0;
      $totd = 0;
      $tote = 0;
      $gt   = 0;

      foreach ($result as $key => $data) {

          // Print Row
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($data->groupid, '110', null, false, $border, '', 'L', $font, $fontsize);
              $str .= $this->reporter->col(($data->{'Current'} != 0 ? number_format($data->{'Current'}, 2) : '-'), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($data->{'31-60 days'} != 0 ? number_format($data->{'31-60 days'}, 2) : '-'), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($data->{'61-90 days'} != 0 ? number_format($data->{'61-90 days'}, 2) : '-'), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($data->{'91-120 days'} != 0 ? number_format($data->{'91-120 days'}, 2) : '-'), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(($data->{'120+ days'} != 0 ? number_format($data->{'120+ days'}, 2) : '-'), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($data->TOTAL, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          // Grand Total Value for Computation
          $tota += $data->{'Current'};
          $totb += $data->{'31-60 days'};
          $totc += $data->{'61-90 days'};
          $totd += $data->{'91-120 days'};
          $tote += $data->{'120+ days'};
          $gt   += $data->TOTAL;

          if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->page_break();
              $str .= $this->displayHeader_GROUP($config);
              $page = $page + $count;
          }
      }

      // Grand Total Row
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('TOTAL : ', '110', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($tota, 2), '110', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totb, 2), '110', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totc, 2), '110', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totd, 2), '110', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($tote, 2), '110', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gt, 2), '110', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
      $str .= $this->reporter->endreport();

      return $str;
  }
  // Added By Elmer 2026-02-27 end

}//end class