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

class current_customer_receivables
{
  public $modulename = 'Current Customer Receivables';
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

        $fields = ['radioretagging', 'radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        break;
      default:
        switch ($companyid) {
          case 1: //vitaline
            array_push($fields, 'dclientname', 'dcentername', 'dagentname');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'dclientname.label', 'Customer');
            break;
          case 23: //labsol cebu
          case 52: //technolab
            array_push($fields, 'start', 'end', 'dclientname', 'dcentername', 'dagentname');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'start.required', true);
            data_set($col1, 'end.required', true);
            data_set($col1, 'dclientname.label', 'Customer');
            break;
          case 32: //3m
          case 36: //rozlab
            array_push($fields, 'dclientname', 'dcentername', 'contra', 'dagentname');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'dclientname.label', 'Customer');
            break;
          case 34: //evergreen
            array_push($fields, 'dclientname', 'dcentername', 'contra');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'dclientname.label', 'Payor');
            break;

          case 63: //Ericco
            array_push($fields, 'dclientname', 'customercategory', 'groupid', 'dcentername', 'contra', 'radioreporttype');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.label', 'Customer');
            data_set($col1, 'customercategory.action', 'lookupcustcategory');
            data_set($col1, 'customercategory.label', 'Category');
            data_set($col1, 'groupid.label', 'Customer Group');
            data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
            data_set($col1, 'groupid.action', 'lookupclientgroupledger');

            data_set(
              $col1,
              'radioreporttype.options',
              [
                ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
                ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
                ['label' => 'Customer Group', 'value' => '2', 'color' => 'orange']
              ]
            );
            break;

          default:
            array_push($fields, 'dclientname', 'dcentername', 'contra');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'contra.lookupclass', 'AR');
            data_set($col1, 'dclientname.lookupclass', 'lookupclient');
            data_set($col1, 'dclientname.label', 'Customer');

            break;
        }
        switch ($companyid) {
          case 63: //ericco
            $fields = ['radioposttype'];
            break;
          default:
            $fields = ['radioposttype', 'radioreporttype'];
            break;
        }

        // $fields = ['radioposttype','radioreporttype'];

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
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
            // ['label' => 'Customer Group', 'value' => '2', 'color' => 'orange']
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

    $paramstr = "select 'default' as print, '' as client,'' as dclientname, '0' as clientid,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,'0' as reporttype,
                        
                      adddate(left(now(),10),-30) as dateid,left(now(),10) as enddate, 
                      '' as ddeptname, '' as dept, '' as deptname,'' as contra,'0' as acnoid,
                      '0' as tagging,'' as collectorname,'' as collectorcode,'' as collector,'0' as collectorid,
                      
                      adddate(left(now(),10),-360) as start, left(now(),10) as end,

                      '' as dagentname,'' as agent,'' as agentname,'' as agentid,'0' as posttype,

                      '' as category_name, '' as category_id, '' as customerfilter
                      ";


    // switch ($companyid) {
    //   case 10: //afti
    //   case 12: //afti usd
    //     $paramstr .= " , adddate(left(now(),10),-30) as dateid,left(now(),10) as enddate, 
    //                 '' as ddeptname, '' as dept, '' as deptname,'' as contra,'0' as acnoid,
    //                 '0' as tagging,'' as collectorname,'' as collectorcode,'' as collector,'0' as collectorid";
    //     break;

    //   default:
    //     switch ($companyid) {
    //       case 1: //vitaline
    //       case 23: //labsol cebu
    //       case 52: //technolab
    //         if ($companyid != 1) { //not vitaline
    //           $paramstr .= ",adddate(left(now(),10),-360) as start, left(now(),10) as end";
    //         }
    //         $paramstr .= ",'' as dagentname,'' as agent,'' as agentname,'' as agentid,'0' as posttype";
    //         break;

    //       default:
    //         $paramstr .= ",'' as dagentname,'' as agent,'' as agentname,'' as agentid,'' as contra,'0' as posttype";
    //         break;
    //     }
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
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $reporttype   = $config['params']['dataparams']['reporttype'];

    $customerfilter   = $config['params']['dataparams']['customerfilter'];

    switch ($reporttype) {
      case '0': // SUMMARIZED
        $result = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result); // POSTED
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result); // POSTED
        break;

      case '2': // GROUP
        $result = $this->reportDefaultLayout_LAYOUT_GROUP($config, $result); // POSTED
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        break;
      default:
        $posttype = $config['params']['dataparams']['posttype'];
        break;
    }

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 52: //technolab
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->VITALINE_QUERY_POSTED($config); // POSTED
            break;
          case '1': // UNPOSTED
            $query = $this->VITALINE_QUERY_UNPOSTED($config); // UNPOSTED
            break;
          case '2': // ALL
            $query = $this->VITALINE_QUERY_ALL($config); // UNPOSTED
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
          case '2': // ALL
            $query = $this->reportDefault_QUERY_ALL($config); // POSTED
            break;
        }
        break;

      case 10: //afti
      case 12: //afti usd
        $query = $this->reportDefault_QUERY_AFTI($config); // POSTED
        break;
      case 40: //CDO
        switch ($posttype) {
          case '0': // POSTED
            $query = $this->CDO_QUERY_POSTED($config); // POSTED                
            break;
          case '1': // UNPOSTED
            $query = $this->CDO_QUERY_UNPOSTED($config); // POSTED
            break;
          case '2': // ALL
            $query = $this->CDO_QUERY_ALL($config); // POSTED
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
            $query = $this->elsi_QUERY_ALL($config); // POSTED
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
            $query = $this->ericco_QUERY_ALL($config); // POSTED
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
            $query = $this->reportDefault_QUERY_ALL($config); // POSTED
            break;
        }
        break;
    }


    $result = $this->coreFunctions->opentable($query);
    return $this->reportplotting($config, $result);
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



    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter .= " and coa.acnoid='$acnoid'";
    }

    switch ($companyid) {
      case 32: //3m
      case 36: //rozlab
        $agentid = $config['params']['dataparams']['agentid'];
        if ($agentid != '' && $agentid != 0) $filter1 .= " and head.agentid=" . $agentid;
        break;
    }

    $addfield = '';
    $addfield2 = '';
    $addleftjoin = '';
    switch ($companyid) {
      case 19: //housegem
        $addfield = ', gdetail.rem as drem ';
        break;
      case 32: //3m
      case 36: //rozlab
        $addfield = ",ag.clientname as agentname, client.brgy, client.area";
        $addfield2 = ',agentname,brgy,area';
        $addleftjoin = "left join client as ag on ag.clientid=head.agentid";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        switch ($companyid) {
          case 11: //summit
            $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                    date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,
                    ifnull((select group_concat(distinct ref) from gldetail left join glhead on glhead.trno=gldetail.trno
                    where gldetail.trno=detail.trno and gldetail.line=detail.line and glhead.doc='AR' ),'') as reference,head.doc
                    from (arledger as detail 
                    left join client on client.clientid=detail.clientid)
                    left join cntnum on cntnum.trno=detail.trno
                    left join glhead as head on head.trno=detail.trno
                    left join coa on coa.acnoid=detail.acnoid
                    where detail.bal<>0  $filter $filter1
                    order by client.clientname, detail.dateid, detail.docno,head.yourref";
            break;

          default:
            $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                    date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
                    from (arledger as detail 
                    left join client on client.clientid=detail.clientid)
                    left join cntnum on cntnum.trno=detail.trno
                    left join glhead as head on head.trno=detail.trno
                    left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                    left join coa on coa.acnoid=gdetail.acnoid
                    " . $addleftjoin . "
                    where detail.bal<>0 and left(coa.alias,2)='AR'  
                    $filter $filter1
                    order by client.clientname, detail.dateid, detail.docno,head.yourref";
            break;
        }

        break;
      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance " . $addfield2 . "
          from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join coa on coa.acnoid=gdetail.acnoid
          " . $addleftjoin . "
          where detail.bal<>0 and left(coa.alias,2)='AR'  
          $filter $filter1
          ) as x
          group by clientname, name " . $addfield2 . " order by clientname";
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
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }
    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";

    switch ($companyid) {
      case 32: //3m
      case 36: //rozlab
        $agent = $config['params']['dataparams']['agent'];
        if ($agent != '') $filter3 = " and head.agent='" . $agent . "'";

        $addfield = ",ag.clientname as agentname, client.brgy, client.area";
        $addfield2 = ',agentname,brgy,area';
        $addleftjoin = "left join client as ag on ag.client=head.agent";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $grp = '';
        switch ($companyid) {
          case 11: //summit
            $ref1 = "detail.ref as reference";
            $ref2 = " '' as reference";
            break;
          default:
            if ($companyid == 19) { //housegems
              $ref1 = " detail.ref, detail.rem as drem ";
              $ref2 = " '' as ref, stock.rem as drem ";
              $grp = " , stock.rem ";
            } else {
              $ref1 = " detail.ref ";
              $ref2 = " '' as ref ";
            }

            break;
        }


        $query = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db as balance,head.yourref,head.doc,$ref1 " . $addfield . "
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      " . $addleftjoin . "
      where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(stock.ext) as balance, head.yourref,head.doc,$ref2 " . $addfield . "
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      " . $addleftjoin . "
      where head.doc in ('sj','mj')  $filter $filter1 $filter3
      group by center, tr,doc, clientname, name, dateid, docno, elapse, yourref $grp " . $addfield2 . "
      order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance " . $addfield2 . "
         from ( select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name, 
         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, detail.db as balance,
         head.yourref " . $addfield . " 
         from (((lahead as head left join ladetail as detail on detail.trno=head.trno) 
         left join client on client.client=head.client) left join coa on coa.acnoid=detail.acnoid)
         left join cntnum on cntnum.trno=head.trno " . $addleftjoin . " where head.doc in ('ar','gj','cr') and 
         left(coa.alias,2)='AR' and detail.refx = 0 $filter $filter1 $filter2 $filter3 
         union all 
         select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name, 
         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, sum(stock.ext) as balance, 
         head.yourref " . $addfield . " 
         from (((lahead as head left join lastock as stock on stock.trno=head.trno) 
         left join client on client.client=head.client)) 
         left join cntnum on cntnum.trno=head.trno " . $addleftjoin . " where head.doc in ('sj','mj','cm') $filter $filter1 $filter3 
         group by center, tr, clientname, name, dateid, docno, elapse, yourref " . $addfield2 . ") as x 
         group by clientname, name " . $addfield2 . " 
         order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function reportDefault_QUERY_ALL($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];
    $contra       = $config['params']['dataparams']['contra'];



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
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    if ($companyid == 32) { //3m

    }


    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";
    $addleftjoin2 = "";
    $addgrp3m = '';

    switch ($companyid) {
      case 32: //3m
      case 36: //rozlab
        $agent = $config['params']['dataparams']['agent'];
        if (
          $agent != ''
        ) $filter3 = " and ag.client='" . $agent . "'";


        $addfield = ",ag.clientname as agentname, client.brgy, client.area";
        $addfield2 = ',agentname,brgy,area';
        $addgrp3m = ',ag.clientname,client.brgy,client.area';
        $addleftjoin = "left join client as ag on ag.client=head.agent";
        $addleftjoin2 = "left join client as ag on ag.clientid=head.agentid";
        break;
    }

    switch ($reporttype) {

      case '1': // DETAILED
        $grp = '';
        switch ($companyid) {
          case 11: //summit
            $ref1 = "detail.ref as reference";
            $ref2 = " '' as reference";
            break;
          default:
            if ($companyid == 19) { //housegem
              $ref1 = " detail.ref, detail.rem as drem ";
              $ref2 = " '' as ref, stock.rem as drem ";
              $grp = " , stock.rem ";
            } else {
              $ref1 = " detail.ref ";
              $ref2 = " '' as ref ";
            }

            break;
        }

        $query = "select cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                         date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref, 
                        head.doc,$ref2 " . $addfield . "
                  from (arledger as detail 
                  left join client on client.clientid=detail.clientid)
                  left join cntnum on cntnum.trno=detail.trno
                  left join glhead as head on head.trno=detail.trno
                  left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                  left join coa on coa.acnoid=gdetail.acnoid
                  " . $addleftjoin2 . "
                  where detail.bal<>0 and left(coa.alias,2)='AR'
                  $filter $filter1
                  union all
                  select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                         detail.db as balance,head.yourref, head.doc,$ref1 " . $addfield . "
                  from (((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  " . $addleftjoin . "
                  where head.doc in ('ar','gj') and left(coa.alias,2)='AR' 
                        and detail.refx = 0  $filter $filter1 $filter2 $filter3
                  union all
                  select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                         sum(stock.ext) as balance, head.yourref, head.doc,$ref2  " . $addfield . "
                  from (((lahead as head 
                  left join lastock as stock on stock.trno=head.trno)
                  left join client on client.client=head.client))
                  left join cntnum on cntnum.trno=head.trno
                  " . $addleftjoin . "
                  where head.doc in ('sj','mj') $filter $filter1 $filter3
                  group by center, tr,doc, clientname, name, head.dateid, docno, elapse, yourref $addgrp3m
                  order by clientname, dateid, docno,yourref";
        break;


      case '0': // SUMMARIZED

        $query = "select clientname, name, sum(balance) as balance " . $addfield2 . "
        
            from (
          select  cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join coa on coa.acnoid=gdetail.acnoid
           " . $addleftjoin2 . "
          where detail.bal<>0 and left(coa.alias,2)='AR'
           $filter $filter1

        union all

          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
           detail.db as balance,head.yourref " . $addfield . "
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
             " . $addleftjoin . "
          where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
          
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(stock.ext) as balance, head.yourref " . $addfield . "
        
          from (((lahead as head left join lastock as stock on stock.trno=head.trno)
          left join client on client.client=head.client))
          left join cntnum on cntnum.trno=head.trno
           " . $addleftjoin . "
          where head.doc in ('sj','mj','cm') $filter $filter1 $filter3
          group by center, tr, clientname, name, head.dateid, docno, elapse, yourref " . $addfield2 . ") as x
          group by clientname, name " . $addfield2 . "
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

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }


    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter .= " and coa.acnoid='$acnoid'";
    }

    $addfield = '';
    $addfield2 = '';
    $addleftjoin = '';

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select 'p' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
                    date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
                    from (arledger as detail 
                    left join client on client.clientid=detail.clientid)
                    left join cntnum on cntnum.trno=detail.trno
                    left join glhead as head on head.trno=detail.trno
                    left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                    left join coa on coa.acnoid=gdetail.acnoid
                    left join heahead as ea on ea.catrno = cntnum.trno
                    left join heainfo as info on info.trno = ea.trno
                    " . $addleftjoin . "
                    where detail.bal<>0 and left(coa.alias,2)='AR'  
                    $filter $filter1
                    order by info.clientname, detail.dateid, detail.docno,head.yourref";

        break;
      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance " . $addfield2 . "
          from (select 'p' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join coa on coa.acnoid=gdetail.acnoid
          left join heahead as ea on ea.catrno = cntnum.trno
          left join heainfo as info on info.trno = ea.trno
          " . $addleftjoin . "
          where detail.bal<>0 and left(coa.alias,2)='AR'  
          $filter $filter1
          ) as x
          group by clientname, name " . $addfield2 . " order by clientname";
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
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }
    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";


    switch ($reporttype) {
      case '1': // DETAILED
        $grp = '';
        $ref1 = " detail.ref ";
        $ref2 = " '' as ref ";


        $query = "select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db as balance,head.yourref,head.doc,$ref1 " . $addfield . "
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.catrno = cntnum.trno
      left join heainfo as info on info.trno = ea.trno
      " . $addleftjoin . "
      where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
      union all
      select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(stock.ext) as balance, head.yourref,head.doc,$ref2 " . $addfield . "
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.catrno = cntnum.trno
      left join heainfo as info on info.trno = ea.trno
      " . $addleftjoin . "
      where head.doc in ('sj','mj')  $filter $filter1 $filter3
      group by center, tr,doc, clientname, name, dateid, docno, elapse, yourref $grp " . $addfield2 . "
      order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "
      select clientname, name, sum(balance) as balance " . $addfield2 . "
      from ( select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db as balance,head.yourref " . $addfield . "
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.catrno = cntnum.trno
      left join heainfo as info on info.trno = ea.trno
      " . $addleftjoin . "
      where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
      union all
      select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(stock.ext) as balance, head.yourref " . $addfield . "
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      left join heahead as ea on ea.catrno = cntnum.trno
      left join heainfo as info on info.trno = ea.trno
      " . $addleftjoin . "
      where head.doc in ('sj','mj','cm') $filter $filter1 $filter3
      group by center, tr, clientname, name, dateid, docno, elapse, yourref " . $addfield2 . ") as x
      group by clientname, name " . $addfield2 . "
      order by clientname, name";
        break;
    } //end swicth


    return $query;
  }

  public function elsi_QUERY_ALL($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];
    $contra       = $config['params']['dataparams']['contra'];

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
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";
    $addleftjoin2 = "";
    $addgrp3m = '';


    switch ($reporttype) {

      case '1': // DETAILED
        $grp = '';
        $ref1 = " detail.ref ";
        $ref2 = " '' as ref ";

        $query = "select cntnum.center, 'p' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
                         date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref, 
                        head.doc,$ref2 " . $addfield . "
                  from (arledger as detail 
                  left join client on client.clientid=detail.clientid)
                  left join cntnum on cntnum.trno=detail.trno
                  left join glhead as head on head.trno=detail.trno
                  left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                  left join coa on coa.acnoid=gdetail.acnoid
                  left join heahead as ea on ea.catrno = cntnum.trno
                  left join heainfo as info on info.trno = ea.trno
                  " . $addleftjoin2 . "
                  where detail.bal<>0 and left(coa.alias,2)='AR'
                  $filter $filter1
                  union all
                  select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
                         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                         detail.db as balance,head.yourref, head.doc,$ref1 " . $addfield . "
                  from (((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  left join heahead as ea on ea.catrno = cntnum.trno
                  left join heainfo as info on info.trno = ea.trno
                  " . $addleftjoin . "
                  where head.doc in ('ar','gj') and left(coa.alias,2)='AR' 
                  and detail.refx = 0  $filter $filter1 $filter2 $filter3
                  order by clientname, dateid, docno,yourref";
        break;


      case '0': // SUMMARIZED

        $query = "select clientname, name, sum(balance) as balance " . $addfield2 . "        
            from (
          select  cntnum.center, 'p' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join coa on coa.acnoid=gdetail.acnoid
          left join heahead as ea on ea.catrno = cntnum.trno
          left join heainfo as info on info.trno = ea.trno
           " . $addleftjoin2 . "
          where detail.bal<>0 and left(coa.alias,2)='AR'
           $filter $filter1

        union all

          select cntnum.center, 'u' as tr, info.clientname, ifnull(info.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
           detail.db as balance,head.yourref " . $addfield . "
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          left join heahead as ea on ea.catrno = cntnum.trno
          left join heainfo as info on info.trno = ea.trno
             " . $addleftjoin . "
          where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3) as x
          group by clientname, name " . $addfield2 . "
          order by clientname, name";
        break;
    } //end swicth
    return $query;
  }

  public function VITALINE_QUERY_POSTED($config)
  {

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $agent        = $config['params']['dataparams']['agent'];
    $agentid      = $config['params']['dataparams']['agentid'];
    $filter = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }


    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($agent != "") {
      $filter .= " and ag.client='$agent'";
    }

    $datefilter = "";
    switch ($config['params']['companyid']) {
      case 23: //labsol cebu
      case 52: //technolab
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $datefilter = "and head.dateid between '" . $start . "' and '" . $end . "'";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
      (
        case
          when coa.alias = 'AR3' then (detail.bal * -1)
          else (case when detail.db >0 then detail.bal else detail.bal*-1 end)
        end
      ) as balance,
      head.yourref
      from (arledger as detail 
      left join client on client.clientid=detail.clientid)
      left join cntnum on cntnum.trno=detail.trno
      left join glhead as head on head.trno=detail.trno
      left join client as ag on ag.clientid = head.agentid
      left join coa as coa on coa.acnoid = detail.acnoid
      where detail.bal<>0 $datefilter $filter
      order by client.clientname, detail.dateid, detail.docno,head.yourref";
        break;
      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance
      from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
      (
        case
          when coa.alias = 'AR3' then (detail.bal * -1)
          else (case when detail.db >0 then detail.bal else detail.bal*-1 end)
        end
      ) as balance,
      head.yourref
      from (arledger as detail 
      left join client on client.clientid=detail.clientid)
      left join cntnum on cntnum.trno=detail.trno
      left join glhead as head on head.trno=detail.trno
      left join client as ag on ag.clientid = head.agentid
      left join coa as coa on coa.acnoid = detail.acnoid
      where detail.bal<>0 $datefilter $filter) as x
      group by clientname, name order by clientname";
        break;
    } //end switch
    return $query;
  }

  public function VITALINE_QUERY_UNPOSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $agent        = $config['params']['dataparams']['agent'];
    $agentid      = $config['params']['dataparams']['agentid'];

    $filter = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }


    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($agent != "") {
      $filter .= " and ag.client='$agent'";
    }

    $datefilter = "";
    switch ($config['params']['companyid']) {
      case 23: //labsol cebu
      case 52: //technolab
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $datefilter = "and head.dateid between '" . $start . "' and '" . $end . "'";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db-detail.cr as balance,head.yourref
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      left join client as ag on ag.client = head.agent
      where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      case head.doc when 'sj' then sum(stock.ext) else sum(stock.ext)*-1 end as balance, head.yourref
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      left join client as ag on ag.client = head.agent
      where head.doc = 'sj' $datefilter $filter
      group by center, tr, clientname, name, head.dateid,  head.doc, docno, elapse, yourref
      order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "
      select clientname, name, sum(balance) as balance
      from ( select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db-detail.cr as balance,head.yourref
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      left join client as ag on ag.client = head.agent
      where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      case head.doc when 'sj' then sum(stock.ext) else sum(stock.ext)*-1 end as balance, head.yourref
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      left join client as ag on ag.client = head.agent
      where head.doc in ('sj','cm') $datefilter $filter
      group by center, tr, clientname, name, head.dateid, head.doc, docno, elapse, yourref) as x
      group by clientname, name
      order by clientname, name";
        break;
    } //end switch

    return $query;
  }

  public function VITALINE_QUERY_ALL($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $agent        = $config['params']['dataparams']['agent'];
    $agentid      = $config['params']['dataparams']['agentid'];

    $filter = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }


    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($agent != "") {
      $filter .= " and ag.client='$agent'";
    }

    $datefilter = "";
    switch ($config['params']['companyid']) {
      case 23: //labsol cebu
      case 52: //technolab
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $datefilter = "and head.dateid between '" . $start . "' and '" . $end . "'";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                          detail.db-detail.cr as balance,head.yourref
                  from (((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  left join client as ag on ag.client = head.agent
                  where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
                  union all
                  select 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                          case head.doc when 'sj' then sum(stock.ext) else sum(stock.ext)*-1 end as balance, head.yourref
                  from (((lahead as head 
                  left join lastock as stock on stock.trno=head.trno)
                  left join client on client.client=head.client))
                  left join cntnum on cntnum.trno=head.trno
                  left join client as ag on ag.client = head.agent
                  where head.doc = 'sj' $datefilter $filter
                  group by clientname, name, head.dateid, head.doc, docno, elapse, yourref
                  union all
                  select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                         (case when coa.alias = 'AR3' then (detail.bal * -1)
                          else (case when detail.db >0 then detail.bal else detail.bal*-1 end) end) as balance,
                          head.yourref
                  from (arledger as detail 
                  left join client on client.clientid=detail.clientid)
                  left join cntnum on cntnum.trno=detail.trno
                  left join glhead as head on head.trno=detail.trno
                  left join client as ag on ag.clientid = head.agentid
                  left join coa as coa on coa.acnoid = detail.acnoid
                  where detail.bal<>0 $datefilter $filter
                  order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance
                  from (select 'u' as tr, client.clientname, 
                                ifnull(client.clientname,'no name') as name,
                                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                detail.db-detail.cr as balance,head.yourref
                        from (((lahead as head 
                        left join ladetail as detail on detail.trno=head.trno)
                        left join client on client.client=head.client)
                        left join coa on coa.acnoid=detail.acnoid)
                        left join cntnum on cntnum.trno=head.trno
                        left join client as ag on ag.client = head.agent
                        where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
                        union all
                        select 'u' as tr, client.clientname, 
                               ifnull(client.clientname,'no name') as name,
                               date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                               case head.doc when 'sj' then sum(stock.ext) else sum(stock.ext)*-1 end as balance, 
                               head.yourref
                        from (((lahead as head left join lastock as stock on stock.trno=head.trno)
                        left join client on client.client=head.client))
                        left join cntnum on cntnum.trno=head.trno
                        left join client as ag on ag.client = head.agent
                        where head.doc in ('sj','cm') $datefilter $filter
                        group by  tr, clientname, name, head.dateid, head.doc, docno, elapse, yourref
                        union all
                        select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                                date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                                (case when coa.alias = 'AR3' then (detail.bal * -1)
                                else (case when detail.db >0 then detail.bal else detail.bal*-1 end) end) as balance,
                                head.yourref
                        from (arledger as detail 
                        left join client on client.clientid=detail.clientid)
                        left join cntnum on cntnum.trno=detail.trno
                        left join glhead as head on head.trno=detail.trno
                        left join client as ag on ag.clientid = head.agentid
                        left join coa as coa on coa.acnoid = detail.acnoid
                        where detail.bal<>0 $datefilter $filter) as x
                  group by clientname, name
                  order by clientname, name";

        break;
    } //end switch

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
      where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(stock.ext) as balance, head.yourref
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      where head.doc in ('sd','se','sf','sj') $filter
      group by center, tr, clientname, name, dateid, docno, elapse, yourref
      order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "
      select clientname, name, sum(balance) as balance
      from ( select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db as balance,head.yourref
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(stock.ext) as balance, head.yourref
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      where head.doc in ('sd','se','sf','sj','cm') $filter
      group by center, tr, clientname, name, dateid, docno, elapse, yourref) as x
      group by clientname, name
      order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function reportDefault_QUERY_AFTI($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
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
      $filter = " and client.clientid='$clientid'";
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
        $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(detail.dateid) as dateid, detail.docno,case cntnum.doc when 'AR' then datediff(now(), detail.dateid) else datediff(now(),ifnull(dl.receivedate,detail.dateid)) end as elapse,
      (detail.db-detail.cr)-ifnull((select sum(vpay.cr-vpay.db) from ( 
      select detail.refx AS refx,detail.linex AS linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
      from ladetail detail left join lahead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
      where detail.refx > 0 and date(head.dateid) between '$startdate' and '$enddate' 
      union all 
      select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
      from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
      left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
      where detail.refx > 0 and date(head.dateid) between '$startdate' and '$enddate') as vpay where vpay.refx=detail.trno and vpay.linex=detail.line),0) as balance,head.yourref
      from (arledger as detail 
      left join client on client.clientid=detail.clientid)
      left join cntnum on cntnum.trno=detail.trno
      left join glhead as head on head.trno=detail.trno
      left join coa as coa on coa.acnoid = detail.acnoid
      left join delstatus as dl on dl.trno = detail.trno
      where coa.alias not in ('AR5') and date(detail.dateid) between '$startdate' and '$enddate' $filter $filter1
      order by client.clientname, detail.dateid, detail.docno,head.yourref";
        break;
      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance
      from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(detail.dateid) as dateid, detail.docno, case cntnum.doc when 'AR' then datediff(now(), detail.dateid) else datediff(now(),ifnull(dl.receivedate,detail.dateid)) end as elapse,
      (detail.db-detail.cr)-ifnull((select sum(vpay.cr-vpay.db) from ( 
        select detail.refx AS refx,detail.linex AS linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
        from ladetail detail left join lahead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
        where detail.refx > 0 and date(head.dateid) between '$startdate' and '$enddate' 
        union all 
        select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
        from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
        left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
        where detail.refx > 0 and date(head.dateid) between '$startdate' and '$enddate') as vpay where vpay.refx=detail.trno and vpay.linex=detail.line),0) as balance,head.yourref
      from (arledger as detail 
      left join client on client.clientid=detail.clientid)
      left join cntnum on cntnum.trno=detail.trno
      left join glhead as head on head.trno=detail.trno
      left join coa as coa on coa.acnoid = detail.acnoid
      left join delstatus as dl on dl.trno = detail.trno
      where coa.alias not in ('AR5') and date(head.dateid) between '$startdate' and '$enddate' $filter $filter1) as x
      group by clientname, name order by clientname";
        break;
    } //end switch

    return $query;
  }

  public function CDO_QUERY_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra       = $config['params']['dataparams']['contra'];


    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter .= " and coa.acnoid='$acnoid'";
    }

    $addfield = '';
    $addfield2 = '';
    $addleftjoin = '';

    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                    date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref 
                    from arledger as detail 
                    left join cntnum on cntnum.trno=detail.trno
                    left join glhead as head on head.trno=detail.trno
                    left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                    left join client on client.clientid=gdetail.clientid
                    left join coa on coa.acnoid=gdetail.acnoid
                    
                    where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()
                    $filter 
                    order by client.clientname, detail.dateid, detail.docno,head.yourref";
        break;
      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance 
          from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref 
          from arledger as detail 
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join client on client.clientid=gdetail.clientid
          left join coa on coa.acnoid=gdetail.acnoid
          
          where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()
          $filter 
          ) as x
          group by clientname, name 
          order by clientname";
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
    $contra       = $config['params']['dataparams']['contra'];
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
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter .= " and coa.acnoid='$acnoid'";
    }

    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";

    switch ($reporttype) {
      case '1': // DETAILED
        $grp = '';
        $ref1 = " detail.ref ";
        $ref2 = " '' as ref ";

        $query = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        detail.db as balance,head.yourref,head.doc, detail.ref 
        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno
        
        where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter
        union all
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref,head.doc,'' as ref
        from (((lahead as head left join lastock as stock on stock.trno=head.trno)
        left join client on client.client=head.client))
        left join cntnum on cntnum.trno=head.trno
        
        where head.doc = 'sj' and head.dateid<=now() $filter 
        group by center, tr,doc, clientname, name, dateid, docno, elapse, yourref  
        union all
        select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(detail.db-detail.cr) as balance, head.yourref,head.doc,'' as ref
        from lahead as head 
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=detail.client
        left join cntnum on cntnum.trno=head.trno
        
        where head.doc = 'MJ' and head.dateid<=now() $filter
        group by center, tr,doc, clientname, name, dateid, docno, elapse, yourref  
        order by clientname, dateid, docno";
        break;

      case '0': // SUMMARIZED
        $query = "
      select clientname, name, sum(balance) as balance 
      from ( select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      detail.db as balance,head.yourref 
      from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)
      left join coa on coa.acnoid=detail.acnoid)
      left join cntnum on cntnum.trno=head.trno
      where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(stock.ext) as balance, head.yourref 
      from (((lahead as head left join lastock as stock on stock.trno=head.trno)
      left join client on client.client=head.client))
      left join cntnum on cntnum.trno=head.trno
      where head.doc in ('sj','cm') and head.dateid<=now() $filter
      group by center, tr, clientname, name, dateid, docno, elapse, yourref
      union all
      select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
      date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
      sum(detail.db-detail.cr) as balance, head.yourref 
      from lahead as head 
      left join ladetail as detail on detail.trno=head.trno
      left join client on client.client=detail.client
      left join cntnum on cntnum.trno=head.trno
      where head.doc in ('MJ') and head.dateid<=now() $filter
      group by center, tr, clientname, name, dateid, docno, elapse, yourref
      
      ) as x
      group by clientname, name 
      order by clientname, name";
        break;
    } //end swicth

    return $query;
  }

  public function CDO_QUERY_ALL($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $contra       = $config['params']['dataparams']['contra'];

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
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    if ($companyid == 32) { //3m
      $agent = $config['params']['dataparams']['agent'];
      if ($agent != '') $filter3 = " and head.agent='" . $agent . "'";
    }

    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";

    switch ($reporttype) {

      case '1': // DETAILED
        $grp = '';
        $ref1 = " detail.ref ";
        $ref2 = " '' as ref ";

        $query = "select  cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref, head.doc,'' as ref
          from arledger as detail 
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join client on client.clientid=gdetail.clientid
          left join coa on coa.acnoid=gdetail.acnoid
          where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()
           $filter 

        union all

          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
           detail.db as balance,head.yourref, head.doc,detail.ref 
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter 
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(stock.ext) as balance, head.yourref, head.doc,'' as ref  
          from (((lahead as head left join lastock as stock on stock.trno=head.trno)
          left join client on client.client=head.client))
          left join cntnum on cntnum.trno=head.trno
          where head.doc = 'sj' and head.dateid<=now() $filter
          group by center, tr,doc, clientname, name, head.dateid, docno, elapse, yourref 
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(detail.db-detail.cr) as balance, head.yourref,head.doc,'' as ref
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=detail.client
          left join cntnum on cntnum.trno=head.trno
          
          where head.doc = 'MJ' and head.dateid<=now() $filter
          group by center, tr,doc, clientname, name, dateid, docno, elapse, yourref  
           order by clientname, dateid, docno,yourref";
        break;


      case '0': // SUMMARIZED

        $query = "select clientname, name, sum(balance) as balance 
        
            from (
          select  cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref 
          from arledger as detail 
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join client on client.clientid=gdetail.clientid
          left join coa on coa.acnoid=gdetail.acnoid
          where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()
           $filter 

        union all

          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
           detail.db as balance,head.yourref
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0 and head.dateid<=now() $filter 
          
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(stock.ext) as balance, head.yourref 
        
          from (((lahead as head left join lastock as stock on stock.trno=head.trno)
          left join client on client.client=head.client))
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('sj','cm') and head.dateid<=now() $filter 
          group by center, tr, clientname, name, head.dateid, docno, elapse, yourref 
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(detail.db-detail.cr) as balance, head.yourref 
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=detail.client
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('MJ') and head.dateid<=now() $filter
          group by center, tr, clientname, name, dateid, docno, elapse, yourref
          
          ) as x
          group by clientname, name 
          order by clientname, name";
        break;
    } //end swicth
    return $query;
  }

  // Added by Elmer - 2026-02-27 Start
  public function ericco_QUERY_POSTED($config)
  {
    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];
    $contra       = $config['params']['dataparams']['contra'];

    // Added 2026-02-25 - Elmer
    $categoryname  = $config['params']['dataparams']['category_name'];
    $categoryid  = $config['params']['dataparams']['category_id'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    // Added 2026-02-25 - end

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    // Added Filter 2026--02-25 -start
    if ($categoryname != '') {
      if ($categoryid != '0') {
        $filter   .= " and client.category = '" . $categoryid . "'";
      }
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }
    // Added Filter 2026--02-25 -end

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter .= " and coa.acnoid='$acnoid'";
    }



    $addfield = '';
    $addfield2 = '';
    $addleftjoin = '';




    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                    date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref " . $addfield . "
                    from (arledger as detail 
                    left join client on client.clientid=detail.clientid)
                    left join cntnum on cntnum.trno=detail.trno
                    left join glhead as head on head.trno=detail.trno
                    left join category_masterfile as cat on cat.cat_id = client.category
                    left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                    left join coa on coa.acnoid=gdetail.acnoid         
                    where detail.bal<>0 and left(coa.alias,2)='AR'  
                    $filter $filter1
                    order by client.clientname, detail.dateid, detail.docno,head.yourref";
        break;

      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance 
                  from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                  date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                  (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref 
                  from (arledger as detail left join client on client.clientid=detail.clientid)
                  left join cntnum on cntnum.trno=detail.trno
                  left join glhead as head on head.trno=detail.trno
                  left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join coa on coa.acnoid=gdetail.acnoid
                  where detail.bal<>0 and left(coa.alias,2)='AR'  
                  $filter $filter1
                  ) as x
                  group by clientname, name 
                  order by clientname";
        break;

      case '2': // GROUP SUMMARY
        $query = "select groupid, sum(balance) as balance
                  from (select client.groupid,
                  (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
                  from (arledger as detail 
                  left join client on client.clientid=detail.clientid)
                  left join cntnum on cntnum.trno=detail.trno
                  left join glhead as head on head.trno=detail.trno
                  left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join coa on coa.acnoid=gdetail.acnoid
                  where detail.bal<>0 and left(coa.alias,2)='AR'
                  $filter $filter1
                  ) as x
                  group by groupid
                  having sum(balance) <> 0
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

    // Added 2026-02-25 - Elmer
    $categoryname  = $config['params']['dataparams']['category_name'];
    $categoryid  = $config['params']['dataparams']['category_id'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    // Added Filter 2026--02-25 -start
    if ($categoryname != '') {
      if ($categoryid != '0') {
        $filter   .= " and client.category = '" . $categoryid . "'";
      }
    }

    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }
    // Added Filter 2026--02-25 -end

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }
    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";





    switch ($reporttype) {
      case '1': // DETAILED
        $query = "select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            detail.db as balance,head.yourref,head.doc
            from lahead as head left join ladetail as detail on detail.trno=head.trno
            left join client on client.client=head.client
            left join coa on coa.acnoid=detail.acnoid
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
            union all
            select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
            sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance, head.yourref,head.doc
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join category_masterfile as cat on cat.cat_id = client.category
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('sj','mj','cm')  $filter $filter1 $filter3
            group by center, tr,doc, clientname, name, dateid, docno, elapse, yourref 
            order by clientname, dateid, docno";
        // var_dump($query);
        break;

      case '0': // SUMMARIZED
        $query = "select clientname, name, sum(balance) as balance
         from ( select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name, 
         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, detail.db as balance,
         head.yourref 
         from (((lahead as head left join ladetail as detail on detail.trno=head.trno) 
         left join client on client.client=head.client) left join coa on coa.acnoid=detail.acnoid) 
         left join category_masterfile as cat on cat.cat_id = client.category
         left join cntnum on cntnum.trno=head.trno  where head.doc in ('ar','gj','cr') and 
         left(coa.alias,2)='AR' and detail.refx = 0 $filter $filter1 $filter2 $filter3 
         union all 
         select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name, 
         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
         sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance, head.yourref 
         from (((lahead as head left join lastock as stock on stock.trno=head.trno) 
         left join client on client.client=head.client)) 
         left join category_masterfile as cat on cat.cat_id = client.category
         left join cntnum on cntnum.trno=head.trno  where head.doc in ('sj','mj','cm') $filter $filter1 $filter3 
         group by center, tr, clientname, name, dateid, docno, elapse, yourref ) as x 
         group by clientname, name  
         order by clientname, name";
        //  var_dump($query);
        break;

      case '2': // GROUP
        $query = "select client.groupid, detail.db as balance
        from lahead head left join ladetail detail on detail.trno = head.trno
        left join client on client.client = head.client left join coa on coa.acnoid = detail.acnoid
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno = head.trno
        where head.doc in ('ar','gj','cr') and left(coa.alias,2) = 'AR'
        and detail.refx = 0 $filter $filter1 $filter2 $filter3
        union all
        select client.groupid, sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance
        from lahead head left join lastock stock on stock.trno = head.trno
        left join client on client.client = head.client
        left join category_masterfile as cat on cat.cat_id = client.category
        left join cntnum on cntnum.trno = head.trno
        where head.doc in ('sj','mj','cm') $filter $filter1 $filter3
        group by groupid 
        order by groupid";
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

    // Added 2026-02-25 - Elmer
    $categoryname  = $config['params']['dataparams']['category_name'];
    $categoryid  = $config['params']['dataparams']['category_id'];
    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    // Added Filter 2026--02-25 -start
    if ($categoryname != '') {
      if ($categoryid != '0') {
        $filter   .= " and client.category = '" . $categoryid . "'";
      }
    }
    if ($groupid != "") {
      $filter .= " and client.groupid='$groupid'";
    }
    // Added Filter 2026--02-25 -end

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    if ($contra != '') {
      $acnoid       = $config['params']['dataparams']['acnoid'];
      $filter2 .= " and coa.acnoid='$acnoid'";
    }

    if ($companyid == 32) { //3m

    }


    $addfield = "";
    $addfield2 = '';
    $addleftjoin = "";
    $addleftjoin2 = "";
    $addgrp3m = '';



    switch ($reporttype) {

      case '1': // DETAILED

        $query = "select cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                         date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref, 
                        head.doc
                  from (arledger as detail 
                  left join client on client.clientid=detail.clientid)
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join cntnum on cntnum.trno=detail.trno
                  left join glhead as head on head.trno=detail.trno
                  left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                  left join coa on coa.acnoid=gdetail.acnoid
                  where detail.bal<>0 and left(coa.alias,2)='AR'
                  $filter $filter1
                  union all
                  select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                         detail.db as balance,head.yourref, head.doc
                  from (((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join client on client.client=head.client)
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join coa on coa.acnoid=detail.acnoid)
                  left join cntnum on cntnum.trno=head.trno
                  where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' 
                        and detail.refx = 0  $filter $filter1 $filter2 $filter3
                  union all
                  select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
                         date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                         sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance, head.yourref, head.doc
                  from (((lahead as head 
                  left join lastock as stock on stock.trno=head.trno)
                  left join client on client.client=head.client))
                  left join category_masterfile as cat on cat.cat_id = client.category
                  left join cntnum on cntnum.trno=head.trno
                  where head.doc in ('sj','mj','cm') $filter $filter1 $filter3
                  group by center, tr,doc, clientname, name, head.dateid, docno, elapse, yourref
                  order by clientname, dateid, docno,yourref";
        break;


      case '0': // SUMMARIZED

        $query = "select clientname, name, sum(balance) as balance 
        
            from (
          select  cntnum.center, 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
          (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref 
          from (arledger as detail left join client on client.clientid=detail.clientid)
          left join category_masterfile as cat on cat.cat_id = client.category
          left join cntnum on cntnum.trno=detail.trno
          left join glhead as head on head.trno=detail.trno
          left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
          left join coa on coa.acnoid=gdetail.acnoid
          where detail.bal<>0 and left(coa.alias,2)='AR'
           $filter $filter1

        union all

          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
           detail.db as balance,head.yourref
          from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join category_masterfile as cat on cat.cat_id = client.category
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter $filter1 $filter2 $filter3
          
          union all
          select cntnum.center, 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
          sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance, head.yourref
        
          from (((lahead as head left join lastock as stock on stock.trno=head.trno)
          left join client on client.client=head.client))
          left join category_masterfile as cat on cat.cat_id = client.category
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('sj','mj','cm') $filter $filter1 $filter3
          group by center, tr, clientname, name, head.dateid, docno, elapse, yourref ) as x
          group by clientname, name 
          order by clientname, name";
        break;

      case '2': // GROUP
        $query = "select groupid, sum(balance) as balance
            from (select client.groupid, (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
            from (arledger as detail left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid  
            where detail.bal<>0 and left(coa.alias,2)='AR' $filter $filter1
            union all
            select client.groupid, detail.db as balance from lahead head left join ladetail detail on detail.trno = head.trno
            left join client on client.client = head.client left join coa on coa.acnoid = detail.acnoid
            left join cntnum on cntnum.trno = head.trno where head.doc in ('ar','gj','cr') and left(coa.alias,2) = 'AR'
            and detail.refx = 0  $filter $filter1 $filter2 $filter3
            union all
            select client.groupid, sum(case when cntnum.doc='CM' then -stock.ext else stock.ext end) as balance
            from lahead head left join lastock stock on stock.trno = head.trno
            left join client on client.client = head.client left join cntnum on cntnum.trno = head.trno
            where head.doc in ('sj','mj','cm')  $filter $filter1 $filter3
            group by client.groupid
            ) as x
            group by groupid
            having sum(balance) <> 0
            order by groupid";
        break;
    } //end swicth
    return $query;
  }
  // Added by Elmer - 2026-02-27 End


  private function displayHeader_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $contra = '';
    $dept = "";

    switch ($companyid) {
      case 1: //vitaline
        break;
      case 23: //labsol cebu
      case 52: //technolab
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        break;
      default:
        $contra   = $config['params']['dataparams']['contra'];
        break;
    }

    switch ($companyid) {
      case 10:
      case 12:
        $dept   = $config['params']['dataparams']['ddeptname'];
        $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
        $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
        if ($dept != "") {
          $deptname = $config['params']['dataparams']['deptname'];
        } else {
          $deptname = "ALL";
        }
        break;
      default:
        $posttype     = $config['params']['dataparams']['posttype'];
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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED CURRENT CUSTOMER RECEIVABLES', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 23) {
      $str .= '<br/>';
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, '', '', '', '', 0, '', 0, 8);
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    // Added 2026-02-25 - Elmer
    // if ($category == '') {
    //   $str .= $this->reporter->col('Category : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // } else {
    //   $str .= $this->reporter->col('Category : ' . strtoupper($category), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // }
    // if ($groupid == '') {
    //   $str .= $this->reporter->col('Group : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // } else {
    //   $str .= $this->reporter->col('Group : ' . strtoupper($groupid), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // }
    // Added 2026-02-25 - end

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

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 52: //technolab
      case 32: //3m
        $agent = $config['params']['dataparams']['agentname'];
        if ($agent == '') $agent = 'ALL';
        $str .= $this->reporter->col('Agent: ' . strtoupper($agent), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        break;
    }



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

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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
      case 19: //housegem
        $str .= $this->reporter->col('CUSTOMER NAME', '270', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No. of days', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NOTES', '280', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        break;
      case 26: //bee healthy
        $str .= $this->reporter->col('CUSTOMER NAME', '175', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '55', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Yourref', '85', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No. of days', '55', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('CUSTOMER NAME', '170', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AGENT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No. of days', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 34: //evergreen
        $str .= $this->reporter->col('PLAN HOLDERS NAME', '270', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No. of days', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('CUSTOMER NAME', '270', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No. of days', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
    }


    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_DETAILED($config, $result)
  {

    $companyid = $config['params']['companyid'];

    $count = 40;
    $page = 40;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $this->reporter->linecounter = 0;
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_DETAILED($config);

    $itemname = "";
    $date = "";
    $docno = "";
    $yourref = "";
    $totalext = 0;
    $totalqty = 0;
    $totaltons = 0;
    $subtotalqty = 0;
    $subtotalext = 0;
    $subtotalpv = 0;
    $subtotaltons = 0;
    $gsubtotalqty = 0;
    $gsubtotalext = 0;
    $gsubtotalpv = 0;
    $member = "";
    $grandtotalpv = 0;
    $grandtotalqty = 0;
    $gsubtotaltons = 0;

    $iitem = "";

    foreach ($result as $key => $data) {
      $display = $data->clientname;
      $docno = $data->docno;
      $date = $data->dateid;
      $order = $data->elapse;
      $served = $data->balance;

      if ($itemname == "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        switch ($companyid) {
          case 19: //housegem
            $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '280', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            break;

          case 26: //bee healthy
            $str .= $this->reporter->col($data->clientname, '175', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '55', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '85', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '55', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            break;
          case 32: //afti
            $str .= $this->reporter->col($data->clientname . ' - ' . $data->brgy . ', ' . $data->area, '170', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            break;
          case 23: //labsol
          case 41: //labsol paranaque
          case 52: //technolab
            $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            break;
          default:
            $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
            break;
        }

        $str .= $this->reporter->endrow();
      }

      if (strtoupper($itemname) == strtoupper($data->clientname)) {
        $itemname = "";

        if (strtoupper($docno) == strtoupper($data->clientname)) {
          $docno = "";
        } else {
          if ($docno != '') {
            $subtotalqty = 0;
            $subtotalext = 0;
          }
          $itemname = strtoupper($data->clientname);
        }
      } else {
        if ($docno != '') {
        }

        if ($itemname != '') {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          switch ($companyid) {
            case 19: //housegem
              $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '70', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('SUB TOTAL :', '170', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '280', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              break;

            case 26: //bee healthy
              $str .= $this->reporter->col('', '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '55', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('SUB TOTAL :', '55', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;
            case 32: //3m
              $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('SUB TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;

            default:
              $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('SUB TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
              break;
          }


          $str .= $this->reporter->endrow();


          $str .= $this->reporter->addline();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader_DETAILED($config);
            $page = $page + $count;
          }
        }

        if ($itemname != '') {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 19: //housegem
              $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '280', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              break;
            case 26: //bee healthy
              $str .= $this->reporter->col($data->clientname, '175', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col('', '55', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '85', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '55', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              break;
            case 32: //3m
              $str .= $this->reporter->col($data->clientname . ' - ' . $data->brgy . ', ' . $data->area, '170', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              break;
            default:
              $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
              break;
          }

          $str .= $this->reporter->addline();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader_DETAILED($config);
            $page = $page + $count;
          }
        }

        $subtotalext = 0;
        $gsubtotalext = 0;
        $docno = $data->clientname;
        if (strtoupper($docno) == strtoupper($data->clientname)) {
          $docno = "";
        } else {
          $docno = strtoupper($data->clientname);
        }
      }

      if ($iitem == $data->clientname) {
        $iitem = "";
      } else {
        $iitem = $data->clientname;
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      switch ($companyid) {
        case 19: //housegem
          $str .= $this->reporter->col('', '270', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($date, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($order, 0), '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($served, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data->drem, '280', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          break;

        case 26: //bee healthy
          $str .= $this->reporter->col('', '175', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($date, '55', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->docno, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->yourref, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col(number_format($order, 0), '55', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col(number_format($served, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
          break;
        case 32: //3m
          $str .= $this->reporter->col('', '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($date, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->docno, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col(number_format($order, 0), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col(number_format($served, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
          break;
        default:
          $str .= $this->reporter->col('', '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($date, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');

          switch ($companyid) {
            case 11: //summit
              if ($data->doc == 'AR') {
                if ($data->reference == "") {
                  $str .= $this->reporter->col($data->docno, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
                } else {
                  $str .= $this->reporter->col($data->reference, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
                }
              } else {
                $str .= $this->reporter->col($data->docno, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
              }
              break;

            default:
              $str .= $this->reporter->col($data->docno, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
              break;
          }


          $str .= $this->reporter->col(number_format($order, 0), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col(number_format($served, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
          break;
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $subtotalext = $subtotalext + $data->balance;
      $gsubtotalext = $gsubtotalext + $data->balance;
      $totalext = $totalext + $data->balance;
      $itemname = strtoupper($data->clientname);
      $docno = $data->clientname;
      $iitem = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_DETAILED($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($companyid) {
      case 19: //housegem
        $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('SUB TOTAL :', '170', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '280', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        break;

      case 26: //bee healthy
        $str .= $this->reporter->col('', '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '55', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUB TOTAL :', '55', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUB TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUB TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->startrow();

    switch ($companyid) {
      case 19: //housegem
        $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL :', '70', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '280', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        break;

      case 26: //bee healthy
        $str .= $this->reporter->col('', '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '55', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL :', '55', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 32: //3m
        $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();

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



    $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra = '';

    $dept = "";

    switch ($companyid) {
      case 1: //vitaline
        break;
      case 23: //labsol cebu
      case 52: //technolab
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        break;
      default:
        $contra   = $config['params']['dataparams']['contra'];
        break;
    }

    switch ($companyid) {
      case 10:
      case 12:
        $dept   = $config['params']['dataparams']['ddeptname'];
        $startdate = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
        $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
        if ($dept != "") {
          $deptname = $config['params']['dataparams']['deptname'];
        } else {
          $deptname = "ALL";
        }
        break;
      default:
        $posttype     = $config['params']['dataparams']['posttype'];

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

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CURRENT CUSTOMER RECEIVABLES - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    if ($companyid == 23 || $companyid == 52) { //labsol cebu , technolab
      $str .= '<br/>';
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, '', '', '', '', 0, '', 0, 8);
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($contra == '') {
      $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');






    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }


    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 52: //technolab
      case 32: //3m
        $agent = $config['params']['dataparams']['agentname'];
        if ($agent == '') $agent = 'ALL';
        $str .= $this->reporter->col('Agent: ' . strtoupper($agent), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        break;
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

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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
      case 32: //3m
        $str .= $this->reporter->col('CUSTOMER NAME', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AREA', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AGENT', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      case 34: //evergreen 
        $str .= $this->reporter->col('PLAN HOLDERS NAME', '110px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('CUSTOMER NAME', '110px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        break;
    }
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result)
  {

    $companyid = $config['params']['companyid'];
    $count = 60;
    $page = 60;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $this->reporter->linecounter = 0;
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_SUMMARIZED($config);
    $amt = null;
    foreach ($result as $key => $data) {
      $bal = number_format($data->balance, 2);
      if ($bal == 0) {
        $bal = '-';
      }

      $display = $data->clientname;
      $served = $data->balance;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 32) { //3m
        $str .= $this->reporter->col($display, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->agentname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($bal, '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($display, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($bal, '110px', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->endrow();

      $amt = $amt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_SUMMARIZED($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->startrow();
    if ($companyid == 32) { //3m
      $str .= $this->reporter->col('GRAND TOTAL : ', '400', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GRAND TOTAL : ', '110px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '110px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();

    return $str;
  }
  // Added by Elmer - 2026-02-26 Start
  private function displayHeader_GROUP($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $client       = $config['params']['dataparams']['client'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $filtercenter = $config['params']['dataparams']['center'];

    $groupid =  isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';

    $contra = '';

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
    $str .= $this->reporter->col('CURRENT CUSTOMER RECEIVABLES - CUSTOMER GROUP', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GROUP NAME', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '700', null, false, $border, 'TB', '', $font, $fontsize, '');
    $str .= $this->reporter->col('TOTAL', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_GROUP($config, $result)
  {

    $companyid = $config['params']['companyid'];
    $count = 60;
    $page = 60;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    $gsubtotalext = 0;
    $grandTotalAmount = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_GROUP($config);

    foreach ($result as $key => $data) {

      $grandTotalAmount += (float)$data->balance;

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->groupid, '150', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col('', '700', null, false, $border, '', 'R', $font, $fontsize, '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '150', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $gsubtotalext += (float)$data->balance;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '150', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B');
    $str .= $this->reporter->col('', '700', null, false, '1px dotted', 'T', 'R', $font, $fontsize);
    $str .= $this->reporter->col(number_format($grandTotalAmount, 2), '150', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
  // Added by Elmer - 2026-02-26 End

}//end class