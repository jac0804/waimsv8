<?php

namespace App\Http\Classes\modules\reportlist\accounting_books;

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

class cash_receipt_book
{
  public $modulename = 'Cash Receipt Book';
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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);
    $companyid = $config['params']['companyid'];
    $fields = ['start', 'end', 'dprojectname', 'dagentname', 'dcentername'];

    switch ($companyid) {
      case 19: //housegem
        array_push($fields, 'dclientname');
        break;
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.readonly', false);

    data_set($col2, 'dclientname.lookupclass', 'lookupclient');
    data_set($col2, 'dclientname.label', 'Customer');


    $fields = ['radioposttype', 'radioreporttype'];
    $col3 = $this->fieldClass->create($fields);
    data_set(
      $col3,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    switch ($config['params']['companyid']) {
      case 1:  //vitaline
      case 23: //labsol cebu
        data_set($col3, 'radioreporttype.options', [
          ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Account Summary', 'value' => '2', 'color' => 'orange']
        ]);
        break;
    }

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("select 'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as dprojectname,
        '' as projectname,
        '' as projectcode,
        0 as projectid,
        0 as agentid,
        '' as agent,
        '' as agentname,
        '' as dagentname,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '0' as reporttype,
        '0' as posttype,
        '' as contra,
        '' as acnoname,
        '' as dacnoname,
        0 as clientid,
        '' as dclientname,
        '' as client,
        '' as clientname");
        break;
      default:
        return $this->coreFunctions->opentable("select 'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as dprojectname,
        '' as projectname,
        '' as projectcode,
        0 as projectid,
        0 as agentid,
        '' as agent,
        '' as agentname,
        '' as dagentname,
        '' as center,
        '' as centername,
        '' as dcentername,
        '0' as reporttype,
        '0' as posttype,
        '' as contra,
        '' as acnoname,
        '' as dacnoname,
        '' as dclientname,
        0 as clientid,
        '' as client,
        '' as clientname");
        break;
    }
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

  public function default_query($filters)
  {
    $agent = $filters['params']['dataparams']['agent'];
    $center = $filters['params']['dataparams']['center'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $isdetailed = $filters['params']['dataparams']['reporttype'];
    $client = $filters['params']['dataparams']['client'];
    $projectcode = $filters['params']['dataparams']['projectcode'];
    $companyid = $filters['params']['companyid'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));

    $filter = "";
    $sort = " order by docno,credit";
    if ($client != "") {
      $clientid = $filters['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    } //end if
    if ($agent != "") {
      $agentid = $filters['params']['dataparams']['agentid'];
      $filter .= " and ag.clientid=" . $agentid;
    } //end if
    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($projectcode != "") {
      $projectid = $filters['params']['dataparams']['projectid'];
      $filter .= " and head.projectid=" . $projectid;
    }

    $condition = '';
    $condition = " head.doc in ('CR')";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $sort = " order by dateid2 ";
    }

    switch ($companyid) {
      case 1: //vitaline
      case 23: // labsol cebu
        $query = $this->VITALINE_QUERY($filters);
        break;
      default:
        switch ($isdetailed) {
          case 0: //summarized
            switch ($isposted) {
              case 1: // unposted
                $query = "select acno, description, sum(debit) as debit, sum(credit) as credit
                from (select 'u' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname, 
                left(head.dateid,10) as dateid, coa.acno, coa.acnoname as description, 
                detail.ref, detail.db as debit, detail.cr as credit
                from ((lahead as head 
                left join ladetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid) 
                left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as ag on ag.client=head.agent
                where $condition and date(head.dateid) between '" . $start . "' 
                and '" . $end . "'  " . $filter . "
                group by client.agent,head.docno,head.rem,head.clientname,head.dateid, 
                coa.acno, coa.acnoname, detail.ref, detail.db, detail.cr) as x 
                where ifnull(acno,'') <>'' 
                group by acno, description
                order by credit";
                break; // end case unposted
              case 0: // posted
                $query = "select 'p' as tr, 'cr' as bk, coa.acno, coa.acnoname as description, 
                sum(detail.db) as debit, sum(detail.cr) as credit
                from ((glhead as head
                left join gldetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid)
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as ag on ag.clientid=head.agentid
                where $condition and date(head.dateid) between '" . $start . "' and '" . $end . "' 
                and ifnull(coa.acno,'')<>'' " . $filter . "
                group by coa.acno, coa.acnoname
                order by credit";
                break; //end posted
              case 2: //all
                $query = "select acno, description, sum(debit) as debit, sum(credit) as credit
                from (select 'u' as tr, 'cr' as bk,  coa.acno, coa.acnoname as description,  
                detail.db as debit, detail.cr as credit
                from ((lahead as head 
                left join ladetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid) 
                left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as ag on ag.client=head.agent
                where $condition and date(head.dateid) between '" . $start . "' 
                and '" . $end . "'   $filter
                group by  coa.acno, coa.acnoname, detail.db, detail.cr
                
                UNION ALL

                select 'p' as tr, 'cr' as bk, coa.acno, coa.acnoname as description,detail.db as debit, 
                detail.cr as credit
                from ((glhead as head
                left join gldetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid)
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as ag on ag.clientid=head.agentid
                where $condition  and date(head.dateid)  between '" . $start . "' 
                and '" . $end . "'   $filter and ifnull(coa.acno,'')<>''
                group by coa.acno, coa.acnoname,detail.db, detail.cr ) as x 
                where ifnull(acno,'') <> ''
                group by acno, description
                order by credit";
            }
            break;
          case 1: //detailed
            switch ($isposted) {
              case 1: // unposted
                $query = "select 'u' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname, 
                left(head.dateid,10) as dateid,head.dateid as dateid2, coa.acno, 
                coa.acnoname as description, detail.ref, detail.db as debit, detail.cr as credit, 
                date(detail.postdate) as postdate, head.crref, detail.checkno
                from ((lahead as head 
                left join ladetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid) 
                left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as ag on ag.client = head.agent
                where $condition and date(head.dateid) between '" . $start . "' 
                and '" . $end . "' " . $filter . $sort;
                break; // end case unposted
              case 0: // posted
                $query = "select 'p' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname,
                left(head.dateid,10) as dateid,head.dateid as dateid2, coa.acno, 
                coa.acnoname as description, detail.ref, detail.db as debit, detail.cr as credit, 
                date(detail.postdate) as postdate, head.crref, detail.checkno
                from ((glhead as head 
                left join gldetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid) 
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as ag on ag.clientid=head.agentid
                where $condition and date(head.dateid) between '" . $start . "' 
                and '" . $end . "' " . $filter . $sort;
                break; //end posted
              case 2: //all
                $query = "select  tr, bk, agent,docno,rem, clientname,dateid, dateid2, acno,description, ref, debit, credit,
                postdate, crref, checkno
                from (select 'p' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname,
                left(head.dateid,10) as dateid,head.dateid as dateid2, coa.acno, 
                coa.acnoname as description, detail.ref, detail.db as debit, 
                detail.cr as credit,date(detail.postdate) as postdate, head.crref, 
                detail.checkno
                from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid) left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as ag on ag.clientid=head.agentid
                where $condition  and date(head.dateid) between '" . $start . "' 
                and '" . $end . "'  $filter 
                
                UNION ALL

                select 'u' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname, 
                left(head.dateid,10) as dateid,head.dateid as dateid2, coa.acno, coa.acnoname
                as description, detail.ref, detail.db as debit, detail.cr as credit, 
                date(detail.postdate) as postdate, head.crref, detail.checkno
                from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid) left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as ag on ag.client = head.agent
                where $condition  and date(head.dateid)  between '" . $start . "' 
                and '" . $end . "'   $filter ) as x 
                $sort";
            }
            break;
        }
        // end switch
        break;
    }

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function VITALINE_QUERY($filters)
  {
    $client = $filters['params']['dataparams']['agent'];
    $center = $filters['params']['dataparams']['center'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $isdetailed = $filters['params']['dataparams']['reporttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));

    $filter = "";
    if ($client != "") {
      $clientid = $filters['params']['dataparams']['agentid'];
      $filter = " and client.clientid=" . $clientid;
    } //end if
    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    switch ($isposted) {
      case 1: // unposted
        switch ($isdetailed) {
          case 1: // detailed unposted
            $query = "
            select 'u' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname, left(head.dateid,10) as dateid, coa.acno, coa.acnoname
            as description, detail.ref, detail.db as debit, detail.cr as credit, date(cntnum.postdate) as postdate
            from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) left join cntnum on cntnum.trno=head.trno
            left join client on client.client=head.agent
            left join client as ag on ag.client = head.agent
            where head.doc='cr' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
            order by docno";
            break;

          case 0: // summarized unposted
            $added = "";
            if ($client != "") {
              $agentid = $filters['params']['dataparams']['agentid'];
              $added = " and agent.clientid=" . $agentid;
            } //end if
            if ($center != "") {
              $added .= " and cnt.center='" . $center . "' ";
            }

            $query = "
            select 'as' as a, head.trno, date(cnt.postdate) as posteddate, head.docno, date(head.dateid) as dateid, client.client, client.clientname, coa.acnoname, left(coa.alias, 2) as alias, sum(detail.db) as db, sum(detail.cr) as cr, detail.ref, date(detail.postdate) as postdate,
            case 
            when left(coa.alias, 2) = 'CA' then 'Cash'
            when left(coa.alias, 2) = 'CB' then 'Good Check'
            when left(coa.alias, 2) = 'CR' AND date(detail.postdate) <= date(head.dateid) then 'Good Check'
            when left(coa.alias, 2) = 'CR' AND date(detail.postdate) > date(head.dateid) then 'PDC'
            else ''
            end as paymenttype, detail.checkno
            from lahead as head
            left join ladetail as detail on detail.trno = head.trno
            left join client as client on client.client = head.client
            left join coa as coa on coa.acnoid = detail.acnoid
            left join cntnum as cnt on cnt.trno = head.trno
            left join client as agent on agent.client = head.agent
            where head.doc = 'CR' and left(coa.alias, 2) IN ('CB', 'CR', 'CA') and 
            date(head.dateid) between '$start' and '$end' " . $added . "
            group by docno, head.dateid, client.client, client.clientname, coa.acnoname, coa.alias, detail.ref,
            detail.postdate, detail.checkno, cnt.postdate, head.trno

            UNION ALL

            select 'b' as a, head.trno, date(cnt.postdate) as posteddate, head.docno, date(head.dateid) as dateid, client.client, client.clientname, coa.acnoname, left(coa.alias, 2) as alias, sum(detail.db) as db, sum(detail.cr) as cr, detail.ref, date(detail.postdate) as postdate,
            '' as paymenttype, detail.checkno
            from lahead as head
            left join ladetail as detail on detail.trno = head.trno
            left join client as client on client.client = head.client
            left join coa as coa on coa.acnoid = detail.acnoid
            left join cntnum as cnt on cnt.trno = head.trno
            left join client as agent on agent.client = head.agent
            where head.doc = 'CR' and left(coa.alias, 2) IN ('AR') and 
            date(head.dateid) between '$start' and '$end' " . $added . "
            group by docno, head.dateid, client.client, client.clientname, coa.acnoname, coa.alias, detail.ref,
            detail.postdate, detail.checkno, cnt.postdate, head.trno
            order by docno, a";
            break;

          case 2: // VITALINE accnt summary
            $added = "";
            if ($client != "") {
              $agentid = $filters['params']['dataparams']['agentid'];
              $added = " and agent.clientid=" . $agentid;
            } //end if
            if ($center != "") {
              $added .= " and cnt.center='" . $center . "' ";
            }

            $query = "
            select coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
            from lahead as head
            left join ladetail as detail on detail.trno = head.trno
            left join coa as coa on coa.acnoid = detail.acnoid
            left join cntnum as cnt on cnt.trno = head.trno
            left join client as agent on head.agent = agent.client
            where head.doc = 'CR' and
            date(head.dateid) between '$start' and '$end' " . $added . "
            group by coa.acnoname, coa.acno
          ";
            break;
        } // end switch isdetailed unposted
        break; // end case unposted
      case 0: // posted
        switch ($isdetailed) {
          case 1: // detailed posted
            $query = "
              select 'p' as tr, 'cr' as bk, client.agent,head.docno, head.rem, head.clientname,left(head.dateid,10) as dateid, coa.acno, coa.acnoname
              as description, detail.ref, detail.db as debit, detail.cr as credit, date(cntnum.postdate) as postdate
              from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
              left join coa on coa.acnoid=detail.acnoid) left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.agentid
              where head.doc='cr' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
              order by docno";
            break;

          case 0: // summarized posted
            $added = "";
            if ($client != "") {
              $agentid = $filters['params']['dataparams']['agentid'];
              $added = " and agent.clientid=" . $agentid;
            } //end if
            if ($center != "") {
              $added .= " and cnt.center='" . $center . "' ";
            }

            $query = "
            select 'as' as a, head.trno, date(cnt.postdate) as posteddate, head.docno, date(head.dateid) as dateid, client.client, client.clientname, coa.acnoname, left(coa.alias, 2) as alias, sum(detail.db) as db, sum(detail.cr) as cr, detail.ref, date(detail.postdate) as postdate,
            case 
            when left(coa.alias, 2) = 'CA' then 'Cash'
            when left(coa.alias, 2) = 'CB' then 'Good Check'
            when left(coa.alias, 2) = 'CR' AND date(detail.postdate) <= date(head.dateid) then 'Good Check'
            when left(coa.alias, 2) = 'CR' AND date(detail.postdate) > date(head.dateid) then 'PDC'
            else ''
            end as paymenttype, detail.checkno
            from glhead as head
            left join gldetail as detail on detail.trno = head.trno
            left join client as client on client.clientid = head.clientid
            left join coa as coa on coa.acnoid = detail.acnoid
            left join cntnum as cnt on cnt.trno = head.trno
            left join client as agent on agent.clientid = head.agentid
            where head.doc = 'CR' and left(coa.alias, 2) IN ('CB', 'CR', 'CA') and 
            date(head.dateid) between '$start' and '$end' " . $added . "
            group by docno, head.dateid, client.client, client.clientname, coa.acnoname, coa.alias, detail.ref,
            detail.postdate, detail.checkno, cnt.postdate, head.trno

            UNION ALL

            select 'b' as a, head.trno, date(cnt.postdate) as posteddate, head.docno, date(head.dateid) as dateid, client.client, client.clientname, coa.acnoname, left(coa.alias, 2) as alias, sum(detail.db) as db, sum(detail.cr) as cr, detail.ref, date(detail.postdate) as postdate,
            '' as paymenttype, detail.checkno
            from glhead as head
            left join gldetail as detail on detail.trno = head.trno
            left join client as client on client.clientid = head.clientid
            left join coa as coa on coa.acnoid = detail.acnoid
            left join cntnum as cnt on cnt.trno = head.trno
            left join client as agent on agent.clientid = head.agentid
            where head.doc = 'CR' and left(coa.alias, 2) IN ('AR') and 
            date(head.dateid) between '$start' and '$end' " . $added . "
            group by docno, head.dateid, client.client, client.clientname, coa.acnoname, coa.alias, detail.ref,
            detail.postdate, detail.checkno, cnt.postdate, head.trno
            order by docno, a";
            break;

          case 2: // VITALINE accnt summary
            $added = "";
            if ($client != "") {
              $agentid = $filters['params']['dataparams']['agentid'];
              $added = " and agent.clientid=" . $agentid;
            } //end if
            if ($center != "") {
              $added .= " and cnt.center='" . $center . "' ";
            }

            $query = "
            select coa.acno, coa.acnoname as description, sum(detail.db) as debit, sum(detail.cr) as credit
            from glhead as head
            left join gldetail as detail on detail.trno = head.trno
            left join coa as coa on coa.acnoid = detail.acnoid
            left join cntnum as cnt on cnt.trno = head.trno
            left join client as agent on head.agentid = agent.clientid
            where head.doc = 'CR' and
            date(head.dateid) between '$start' and '$end' " . $added . "
            group by coa.acnoname, coa.acno
          ";
            break;
        }
        break;
    } // end switch

    return $query;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    switch ($config['params']['dataparams']['reporttype']) {
      case 1: // detailed
        switch ($config['params']['companyid']) {
          case 10: //afti
          case 12: //afti usd
            $reportdata =  $this->AFTI_CRBOOK_detailed($result, $config);
            break;
          case 15: //nathina
          case 17: //unihome
          case 28: //xcomp
          case 39: //CBBSI
            $reportdata =  $this->MSJOY_CRBOOK_detailed($result, $config); // CURRENTLY FOR UNIHOME/NATHINA 
            break;
          default:
            $reportdata =  $this->default_CRBOOK_detailed($result, $config);
            break;
        }
        break;

      case 2: // VITALINE accnt summary
        $reportdata =  $this->VITALINE_ACCOUNT_SUMMARY_LAYOUT($result, $config);
        break;

      case 0: // summarized
        switch ($config['params']['companyid']) {
          case 1: //vitaline
          case 23: //labsol cebu
          case 41: // labsol paranaque
          case 52: //technolab
            $reportdata =  $this->VITALINE_summarized($result, $config);
            break;
          case 15: //nathina
          case 17: //unihome
          case 28: //xcomp
          case 39: //CBBSI
            $reportdata =  $this->MSJOY_CRBOOK_summarized($result, $config); // CURRENTLY FOR UNIHOME/NATHINA 
            break;
          default:
            $reportdata =  $this->default_CRBOOK_summarized($result, $config);
            break;
        }
        break;
    }

    return $reportdata;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $fontsize10 = '10';
    $companyid = $config['params']['companyid'];
    if ($config['params']['dataparams']['reporttype'] == 1) {

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->begintable('1200');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', 100, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Reference No.', 150, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Account Name', 300, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Debit', 150, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Credit', 150, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Details', 350, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->endrow();
          break;
        default:
          $str .= $this->reporter->begintable('1200');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('DATE', 100, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('POSTED <br> DATE', 100, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('DOCUMENT #', 120, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('PAYOR NAME', 160, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('ACCOUNT CODE', 100, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 160, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('REFFERENCE #', 120, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('DEBIT', 120, null, '', $border, 'TB', 'r', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('CREDIT', 120, null, '', $border, 'TB', 'r', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('PARTICULARS', 100, null, '', $border, 'TB', 'r', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->endrow();
          break;
      }
    } else {
      $str .= $this->reporter->begintable('800', null, '', $border, '', '', '', '', '', '', '');
      $str .= $this->reporter->startrow('', null, '', $border, '', '', $font, 'B', 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', 100, null, '', $border, 'B', 'l', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 400, null, '', $border, 'B', 'c', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('DEBIT', 150, null, '', $border, 'B', 'r', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('CREDIT', 150, null, '', $border, 'B', 'r', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->endrow();
    } //end if
    return $str;
  }

  private function generateDefaultHeader($params)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $str = '';
    $posttype = $params['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $post = 'Posted';
        break;
      case 1:
        $post = 'Unposted';
        break;
      default:
        $post = 'All';
        break;
    }

    switch ($params['params']['companyid']) {
      case 17: // UNIHOME
      case 39: //CBBSI
        $project = $params['params']['dataparams']['projectname'];
        if ($project == "") {
          $project = "ALL";
        }
        break;
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    if ($params['params']['dataparams']['reporttype'] == 1) {

    
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    
 

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('1200', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('DETAILED CASH RECEIPT BOOK', null, null, '', $border, '', 'l', $font, '18', 'b', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['start'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['end'])), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      switch ($companyid) {
        case 17: //unihome
        case 39: //CBBSI
          $str .= $this->reporter->col('Project: ' . $project, null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
          break;
      }
      $str .= $this->reporter->col('Center: ' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {

     
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      
      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUMMARIZED CASH RECEIPT BOOK', null, null, '', $border, '', 'l', $font, '18', 'b', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['start'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['end'])), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      switch ($companyid) {
        case 17: //unihome
        case 39: //CBBSI
          $str .= $this->reporter->col('Project: ' . $project, null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
          break;
      }
      $str .= $this->reporter->col('Center: ' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
    } //end if

    return $str;
  } //end fn

  private function default_CRBOOK_summarized($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $count = 60;
    $page = 59;

    $str .= $this->reporter->beginreport();
    $str .= $this->generateDefaultHeader($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, $decimal_currency);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, $decimal_currency);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $str .= $this->reporter->col($value->acno, 100, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, 400, null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, 150, null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, 150, null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->generateDefaultHeader($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', 100, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', 400, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function default_CRBOOK_detailed($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $layoutSize = 1200;
    $this->reporter->linecounter = 0;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 51;
    $page = 50;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->generateDefaultHeader($params);
    $str .= $this->default_table_cols($layoutSize, $border, $font, $fontsize12, $params);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $postdate = "";
    $rem = "";
    $cname = "";

    foreach ($data as $key => $value) {
      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $cname = "";
        $postdate = "";
        $rem = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', 100, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 100, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 160, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 100, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 160, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 100, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }

        $docno = $value->docno;
        $date = $value->dateid;
        $postdate = $value->postdate;
        $cname = $value->clientname;
        $rem = $value->rem;
      } //end fn

      $debit = number_format($value->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($value->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->generateDefaultDetailPart($params, $docno, $cname, $date, $value->acno, $value->description, $value->ref, $debit, $credit, $postdate, $rem);

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $docno = $value->docno;
      $date = $value->dateid;
      $cname = $value->clientname;


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->generateDefaultHeader($params);
        }
        $str .= $this->default_table_cols($layoutSize, $border, $font, $fontsize12, $params);

        $page = $page + $count;
      } //end if
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '120', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '160', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '160', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', '120', null, '', $border, 'T', 'c', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), '120', null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), '120', null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('', '100', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function MSJOY_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $fontsize10 = '10';
    $str = '';
    $companyid = $params['params']['companyid'];
    if ($params['params']['dataparams']['reporttype'] == 1) {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DATE', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('POSTED <br> DATE', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('PAYOR NAME', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('REFFERENCE #', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('PARTICULARS', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', '', '', '', '', '');
      $str .= $this->reporter->startrow('', null, '', $border, '', '', $font, 'B', 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', $border, 'B', 'c', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->endrow();
    } //end if
    return $str;
  }

  // CURRENTLY FOR UNIHOME/NATHINA START
  private function MSJOY_Header($params)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $str = '';

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    switch ($params['params']['companyid']) {
      case 17: // UNIHOME
      case 39: //CBBSI
        $project = $params['params']['dataparams']['projectname'];
        if ($project == "") {
          $project = "ALL";
        }
        break;
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";

    if ($params['params']['dataparams']['reporttype'] == 1) {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DETAILED CASH RECEIPT BOOK', null, null, '', $border, '', 'l', $font, '18', 'b', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['start'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['end'])), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      switch ($companyid) {
        case 17: //unihome
        case 39: //CBBSI
          $str .= $this->reporter->col('Project: ' . $project, null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
          break;
      }
      $str .= $this->reporter->col('Center: ' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
    } else {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SUMMARIZED CASH RECEIPT BOOK', null, null, '', $border, '', 'l', $font, '18', 'b', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['start'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['end'])), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      switch ($companyid) {
        case 17: //unihome
        case 39: //CBBSI
          $str .= $this->reporter->col('Project: ' . $project, null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
          break;
      }
      $str .= $this->reporter->col('Center: ' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
    } //end if

    return $str;
  } //end fn

  private function MSJOY_DetailPart($field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8, $field9, $field10, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($field3, '150', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field9, '150', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field1, '75', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field2, '125', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field4, '100', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field5, '200', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field6, '75', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field7, '150', null, '', $border, '', 'RT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field8, '150', null, '', $border, '', 'RT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field10, '250', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function MSJOY_CRBOOK_detailed($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $this->reporter->linecounter = 0;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 51;
    $page = 50;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_Header($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $postdate = "";
    $rem = "";
    $cname = "";

    foreach ($data as $key => $value) {
      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $cname = "";
        $postdate = "";
        $rem = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }

        $docno = $value->docno;
        $date = $value->dateid;
        $postdate = $value->postdate;
        $cname = $value->clientname;
        $rem = $value->rem;
      } //end fn

      $debit = number_format($value->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($value->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->MSJOY_DetailPart($docno, $cname, $date, $value->acno, $value->description, $value->ref, $debit, $credit, $postdate, $rem, $params);

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $docno = $value->docno;
      $date = $value->dateid;
      $cname = $value->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_Header($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '150', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '125', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('', '75', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', '200', null, '', $border, 'T', 'c', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), '150', null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), '150', null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('', '250', null, '', $border, 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function MSJOY_CRBOOK_summarized($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $count = 60;
    $page = 59;

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_Header($params);
    $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, $decimal_currency);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, $decimal_currency);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MSJOY_Header($params);
        }
        $str .= $this->MSJOY_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn
  // CURRENTLY FOR UNIHOME/NATHINA END

  private function AFTI_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $fontsize10 = '10';
    if ($config['params']['dataparams']['reporttype'] == 1) {

      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date', 100, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('Reference No.', 150, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('Account Name', 300, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('Debit', 150, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('Credit', 150, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('Details', 350, null, '', '1px solid', 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    }
    return $str;
  }

  private function AFTI_CRBOOK_detailed($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $this->reporter->linecounter = 0;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->generateDefaultHeader($params);
    $str .= $this->AFTI_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";

    foreach ($data as $key => $value) {
      $date = $value->dateid;

      $debit = number_format($value->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($value->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($date, null, null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col($value->crref, null, null, '', $border, 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', $border, 'LTRB', 'L', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col($debit, null, null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, null, null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($value->checkno . ' ' . $value->postdate, null, null, '', $border, 'LTRB', 'L', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $date = $value->dateid;
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', $border, 'LTRB', 'C', '', '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'LTRB', 'C', '', '', '', '', '');
    $str .= $this->reporter->col('Totals', null, null, '', $border, 'LTRB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col('', null, null, '', $border, 'LTRB', 'C', '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function generateDefaultDetailPart($params, $field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8, $field9, $field10)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($field3, '100', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field9, '100', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field1, '120', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field2, '160', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field4, '100', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field5, '160', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field6, '120', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field7, '120', null, '', $border, '', 'RT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field8, '120', null, '', $border, '', 'RT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field10, '100', null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function VITALINE_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $str = '';
    $fontsize10 = '10';
    
    if ($params['params']['dataparams']['reporttype'] == 2) {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Posted <br> Date', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('Document <br> Date', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('Document <br> No.', '150', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '', '');
      $str .= $this->reporter->col('Reference <br> No.', '150', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Payment <br> Type', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Check <br> Date', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Check <br> Number', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Payor <br> Name', '150', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Total <br> Amt Collected', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Final <br> WithHolding <br> Tax', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Creditable <br> WithHolding <br> Tax', '100', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->col('Deposit <br> Document No.', '150', null, '', '1px solid ', 'BT', 'C', $font, $fontsize10, 'b', '');
      $str .= $this->reporter->endrow();
    } //end if
    return $str;
  }

  public function VITALINE_HEADER($params, $layoutSize)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    $str = '';
    $str .= $this->reporter->begintable($layoutSize);
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutSize, null, '', '1px solid ', '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUMMARIZED CASH RECEIPT BOOK', null, null, '', '1px solid ', '', 'l', $font, '18', 'b', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['start'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['end'])), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '');
    $str .= $this->reporter->col('Center: ' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br>";


    return $str;
  }

  private function VITALINE_summarized($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = '';
    $count = 60;
    $page = 59;
    $layoutSize = "1000";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    $str .= $this->VITALINE_HEADER($params, $layoutSize);
    $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);

    $docno = "";
    $db = 0;
    $docno = "";
    $border = "";
    $totamtcollected = 0;
    $totfwhtax = 0;
    $totcwhtax = 0;

    foreach ($data as $key => $value) {
      $db = number_format($value->db, 2);
      $alias = $value->alias;
      $paymenttype = $value->paymenttype;
      $checkno = $value->checkno;

      $dsref = $this->coreFunctions->opentable(
        "select head.docno from lahead as head
          left join ladetail as detail on detail.trno = head.trno
          where detail.refx = " . $value->trno . "
          UNION ALL
          select head.docno from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          where detail.refx = " . $value->trno . ""
      );

      $fwhtax = $this->coreFunctions->opentable(
        "select detail.db as finaltax
          from gldetail as detail
          left join coa as coa on coa.acnoid = detail.acnoid
          where detail.trno = '" . $value->trno . "' and coa.alias = 'WT3'
          UNION ALL
          select detail.db
          from ladetail as detail
          left join coa as coa on coa.acnoid = detail.acnoid
          where detail.trno = '" . $value->trno . "' and coa.alias = 'WT3'"
      );

      $cwhtax = $this->coreFunctions->opentable(
        "select detail.db as creditabletax
          from gldetail as detail
          left join coa as coa on coa.acnoid = detail.acnoid
          where detail.trno = '" . $value->trno . "' and coa.alias = 'WT1'
          UNION ALL
          select detail.db
          from ladetail as detail
          left join coa as coa on coa.acnoid = detail.acnoid
          where detail.trno = '" . $value->trno . "' and coa.alias = 'WT1'"
      );

      if ($docno != $value->docno) {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      if ($paymenttype != "") {
        $str .= $this->reporter->col($value->posteddate, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->dateid, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->docno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->ref, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($paymenttype, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->postdate, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($checkno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->clientname, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        if ($alias == 'AR') {
          $str .= $this->reporter->col('-', null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        } else {
          $str .= $this->reporter->col($db, null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          $totamtcollected += $value->db;
        }

        if ($docno != $value->docno) {
          $str .= $this->reporter->col(isset($fwhtax[0]->finaltax) ? number_format($fwhtax[0]->finaltax, 2) : '-', null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col(isset($cwhtax[0]->creditabletax) ? number_format($cwhtax[0]->creditabletax, 2) : '-', null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col(isset($dsref[0]->docno) ? $dsref[0]->docno : "", null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

          $totfwhtax += isset($fwhtax[0]->finaltax) ? $fwhtax[0]->finaltax : 0;
          $totcwhtax += isset($cwhtax[0]->creditabletax) ? $cwhtax[0]->creditabletax : 0;
        }
      }

      if ($value->ref != "") {
        $str .= $this->reporter->col($value->posteddate, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->dateid, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->docno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->ref, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($paymenttype, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->postdate, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($checkno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->clientname, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        if ($alias == 'AR') {
          $str .= $this->reporter->col('-', null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        } else {
          $str .= $this->reporter->col($db, null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          $totamtcollected += $value->db;
        }

        if ($docno != $value->docno) {
          $str .= $this->reporter->col(isset($fwhtax[0]->finaltax) ? number_format($fwhtax[0]->finaltax, 2) : '-', null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col(isset($cwhtax[0]->creditabletax) ? number_format($cwhtax[0]->creditabletax, 2) : '-', null, null, '', '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col(isset($dsref[0]->docno) ? $dsref[0]->docno : "", null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

          $totfwhtax += isset($fwhtax[0]->finaltax) ? $fwhtax[0]->finaltax : 0;
          $totcwhtax += isset($cwhtax[0]->creditabletax) ? $cwhtax[0]->creditabletax : 0;
        }
      }

      $str .= $this->reporter->endrow();

      $docno = $value->docno;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->VITALINE_HEADER($params, $layoutSize);
        }
        $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totamtcollected, $decimal_currency), null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totfwhtax, $decimal_currency), null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totcwhtax, $decimal_currency), null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function VITALINE_ACCOUNT_SUMMARY_HEADER($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $str = '';
    $center = $params['params']['dataparams']['center'];
    $startdate = $params['params']['dataparams']['start'];
    $enddate = $params['params']['dataparams']['end'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT SUMMARY', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($startdate)) . ' TO ' . date('M-d-Y', strtotime($enddate)), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center: ' . $center, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center: ' . 'ALL', null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    }
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    return $str;
  } //end fn

  private function VITALINE_ACCOUNT_SUMMARY_LAYOUT($data, $params)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = 12;

    $str = '';
    $count = 60;
    $page = 59;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->VITALINE_ACCOUNT_SUMMARY_HEADER($params);
    $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, 2);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, 2);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb += $value->debit;
      $totalcr += $value->credit;

      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->VITALINE_ACCOUNT_SUMMARY_HEADER($params);
        }
        $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12, $params);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'BT', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), null, null, '', '1px solid ', 'BT', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn
}//end class