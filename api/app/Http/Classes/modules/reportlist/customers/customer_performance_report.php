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
use DatePeriod;
use DateInterval;

class customer_performance_report
{
  public $modulename = 'Customer Performance Report';
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $fields = ['radioprint', 'start', 'end', 'dcentername'];
    } else {
      $fields = ['radioprint', 'start', 'end', 'dcentername', 'categoryname', 'subcatname'];
      if ($companyid == 64) { //excelin
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname', 'ditemname', 'radioposttype', 'radioreporttype'];
      }
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
      case 64: //excelin
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.options', [
          ['label' => 'Customer Performance Report', 'value' => '0', 'color' => 'red'],
          ['label' => 'Salesman Performance Report', 'value' => '1', 'color' => 'red'],
          ['label' => 'Customer Performance Report Per Monthly', 'value' => '2', 'color' => 'red'],
          ['label' => 'Item Performance Report Per Monthly', 'value' => '3', 'color' => 'red']
        ]);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'customer');
        data_set($col1, 'dagentname.action', 'lookupagentreport');

        data_set($col1, 'radioposttype.options', [
          ['label' => 'Unposted', 'value' => 'unpost', 'color' => 'red'],
          ['label' => 'Posted', 'value' => 'post', 'color' => 'red'],
          ['label' => 'All', 'value' => 'all', 'color' => 'red']
        ]);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }
    // data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
    $fields = ['prepared', 'approved'];
    if ($companyid == 64) { //excelin
      $fields = [];
    }
    $col2 = $this->fieldClass->create($fields);

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

    // ADDDATE(LEFT(NOW(), 10), -360) AS start,
    // LEFT(NOW(), 10) AS end;

    $paramstr = "
    select 'default' as print,
    DATE_SUB(CURDATE(), INTERVAL 11 MONTH) as start,
    CURDATE() AS end,
    '' as prepared,'' as approved,
    '' as category, '' as subcat,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '0' as reporttype,
    '' as project,
    '' as projectid,
    '' as projectname,
    '' as ddeptname,
    '' as dept,
    '' as deptname,
    '' as client,
    '' as clientid,
    '' as clientname,
    '' as agent,
    '' as agentid,
    '' as agentname,
    '' as itemid,
    '' as itemname,
    'all' as posttype
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '1':
        $result = $this->reportSalesmanPerformace($config);
        break;
      case '2':
        $result = $this->reportCustomerPerformace_perMonthly($config);
        break;
      case '3':
        $result = $this->reportItemPerformace_perMonthly($config);
        break;

      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }
  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $fields = ['radioprint', 'start', 'end', 'dcentername', 'categoryname', 'subcatname'];

    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $prjid = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['ddeptname'];
    $project = $config['params']['dataparams']['projectid'];
    $center = $config['params']['dataparams']['center'];

    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];

    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];
    $posttype = $config['params']['dataparams']['posttype'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    $filter1 = "";
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 64: //excelin
        if ($client != "") {
          $filter1 .= " and client.client = '$client'";
        }
        if ($itemname != "") {
          $filter1 .= " and stock.itemid = $itemid";
        }
        if ($agent != "") {
          $filter1 .= " and agent.client = '$agent'";
        }

        break;
      case 12: //afti usd
      case 10: //afti
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $config['params']['dataparams']['deptid'];
        }
        if ($prjid != "") {
          $filter1 .= " and stock.projectid = $project";
        }
        if ($deptid != "") {
          $filter1 .= " and head.deptid = $dept";
        }
        break;

      default:
        $filter1 .= "";
        break;
    }

    if ($systemtype == 'AMS') {
      $query = "select client, clientname, sum(amount) as amount
      from (
        select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
        (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(detail.cr-detail.db) as amount
        from glhead as head 
        left join gldetail as detail on detail.trno=head.trno 
        left join client on client.clientid=head.clientid
        left join client as agent on agent.clientid=head.agentid 
        left join cntnum on cntnum.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        where head.doc in ('sj', 'sd', 'se', 'sf') and date(head.dateid) between '" . $start . "' and '" . $end . "' and left(coa.alias,2) in ('SA', 'SD', 'SR')
        " . $filter . " " . $filter1 . "
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname) as s
        group by client, clientname order by sum(s.amount) desc";
    } else {
      $addfield = '';
      $addfield2 = '';
      if ($companyid == 32) { //3m
        $addfield = ",client.brgy, client.area";
        $addfield2 = ',brgy,area';
      }

      switch ($companyid) {
        case 64: //excelin
          switch ($posttype) {
            case 'unpost':
              $qry_in = "
                select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
                (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
                cat.name as category, subcat.name as subcatname " . $addfield . "
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
                $filter $filter1 and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name " . $addfield . "";
              break;
            case 'post':
              $qry_in = "
                select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
                (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
                cat.name as category, subcat.name as subcatname " . $addfield . "
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
                $filter $filter1 and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name " . $addfield . "";
              break;

            default:
              $qry_in = "
                select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
                (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
                cat.name as category, subcat.name as subcatname " . $addfield . "
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
                $filter $filter1 and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name " . $addfield . "
                
                union all 

                select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
                (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
                cat.name as category, subcat.name as subcatname " . $addfield . "
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
                $filter $filter1 and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name " . $addfield . "";
              break;
          }
          break;

        default:
          $qry_in = "
          select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
          (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
          cat.name as category, subcat.name as subcatname " . $addfield . "
          from glhead as head 
          left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as agent on agent.clientid=head.agentid
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
          $filter $filter1 and item.isofficesupplies=0
          group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name " . $addfield . "";
          break;
      }


      $query = "select client, clientname,sum(amount) as amount " . $addfield2 . "
      from (
      $qry_in
      ) as s
      group by client,clientname " . $addfield2 . "
      order by sum(s.amount) desc";
    }

    return $this->coreFunctions->opentable($query);
  }
  // QUERY1
  public function reportDefault1($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $prjid = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['ddeptname'];
    $project = $config['params']['dataparams']['projectid'];

    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];

    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];

    $posttype = $config['params']['dataparams']['posttype'];

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    $filter1 = "";
    $center = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 64: //excelin
        if ($itemname != "") {
          $filter .= " and stock.itemid = $itemid";
        }
        if ($client != "") {
          $filter .= " and client.client = '$client'";
        }
        if ($agent != "") {
          $filter .= " and agent.client = '$agent'";
        }

        break;
      case 12: //afti usd
      case 10: //afti
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $config['params']['dataparams']['deptid'];
        }
        if ($prjid != "") {
          $filter1 .= " and stock.projectid = $project";
        }
        if ($deptid != "") {
          $filter1 .= " and head.deptid = $dept";
        }
        break;
      default:
        $filter1 .= "";
        break;
    }


    switch ($companyid) {
      case 64: //excelin
        switch ($posttype) {
          case 'unpost':
            $query = "select sum(amount) as amount from
            (select 'sales' as type, 'p' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
            (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
            cat.name as category, subcat.name as subcatname

            from lahead as head 
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno

            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat

            where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
            $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name) as s";
            break;
          case 'post':
            $query = "select sum(amount) as amount
            from ( select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
            (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
            cat.name as category, subcat.name as subcatname
    
            from glhead as head 
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
    
            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
    
            where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
            $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name) as s";
            break;

          default:
            $query = "select sum(amount) as amount from
            (select 'sales' as type, 'p' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
            (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
            cat.name as category, subcat.name as subcatname

            from lahead as head 
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno

            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat

            where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
            $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name

            union all

            select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
            (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
            cat.name as category, subcat.name as subcatname
    
            from glhead as head 
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
    
            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
    
            where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
            $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name) as s";

            break;
        }
        break;

      default:
        $query = "select sum(amount) as amount
   
        from ( select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
        (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
        cat.name as category, subcat.name as subcatname
    
        from glhead as head 
        left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as agent on agent.clientid=head.agentid
        left join cntnum on cntnum.trno=head.trno
    
        left join item on item.itemid=stock.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
    
        where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
        $filter $filter1
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name) as s";
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function SalesmanPerformace_query($config, $t)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center     = $config['params']['dataparams']['center'];

    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];

    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];
    $posttype = $config['params']['dataparams']['posttype'];

    $filter = "";
    if ($agent != "") {
      $filter .= " and ag.client = '$agent'";
    }
    if ($itemname != "") {
      $filter .= " and stock.itemid = $itemid";
    }
    if ($client != "") {
      $filter .= " and client.client= '$client'";
    }
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }
    $groupby = "group by agent, agname";
    $fields = ",agent,agname";
    if ($t == 1) {
      $groupby = "";
      $fields = "";
    }

    switch ($posttype) {
      case 'unpost':
        $qry_in = "select head.dateid, head.docno, ag.client as agent, ag.clientname as agname,sum(stock.ext) as amount  
             from client as ag 
             left join lahead as head on head.agent=ag.client
             left join lastock as stock on stock.trno=head.trno
             left join cntnum on cntnum.trno=head.trno
             left join item on item.itemid=stock.itemid
             where ag.isagent=1 and head.doc in ('sj') and 
             date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
             group by head.dateid, head.docno, ag.client, ag.clientname";
        break;
      case 'post':
        $qry_in = "select head.dateid, head.docno, ag.client as agent, ag.clientname as agname,sum(stock.ext) as amount  
             from client as ag 
             left join glhead as head on head.agentid=ag.clientid
             left join glstock as stock on stock.trno=head.trno
             left join cntnum on cntnum.trno=head.trno
             left join item on item.itemid=stock.itemid
             where ag.isagent=1 and head.doc in ('sj') and 
             date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
             group by head.dateid, head.docno, ag.client, ag.clientname";
        break;

      default:
        $qry_in = "select head.dateid, head.docno, ag.client as agent, ag.clientname as agname,sum(stock.ext) as amount  
             from client as ag 
             left join glhead as head on head.agentid=ag.clientid
             left join glstock as stock on stock.trno=head.trno
             left join cntnum on cntnum.trno=head.trno
             left join item on item.itemid=stock.itemid
             where ag.isagent=1 and head.doc in ('sj') and 
             date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
             group by head.dateid, head.docno, ag.client, ag.clientname
             union all
             select head.dateid, head.docno, ag.client as agent, ag.clientname as agname,sum(stock.ext) as amount  
            from client as ag 
             left join lahead as head on head.agent=ag.client
             left join lastock as stock on stock.trno=head.trno
             left join cntnum on cntnum.trno=head.trno
             left join item on item.itemid=stock.itemid
             where ag.isagent=1 and head.doc in ('sj') and 
             date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
             group by head.dateid, head.docno, ag.client, ag.clientname";
        break;
    }


    $query = "select sum(amount) as amount $fields from (
      $qry_in
    ) as v $groupby order by sum(amount) desc";
    return $this->coreFunctions->opentable($query);
  }

  public function Customer_Performance_Monthly_query($config)
  {

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center     = $config['params']['dataparams']['center'];

    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];

    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];
    $posttype = $config['params']['dataparams']['posttype'];

    $filter = "";



    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }
    if ($itemname != "") {
      $filter .= " and stock.itemid='$itemid'";
    }
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($agent != "") {
      $filter .= " and client.client='$agent'";
    }
    switch ($posttype) {
      case 'unpost':
        $qry_in = "select client.client,client.clientname,agent.clientname as agentname,date_format(head.dateid, '%Y-%m') as monyear,
                sum(stock.ext) as amount

                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join client on head.client=client.client
                left join client as agent on agent.client = head.agent
                left join cntnum on head.trno=cntnum.trno
                where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	              group by client.client,client.clientname,agent.clientname,head.dateid
	              order by  clientname";
        break;
      case 'post':
        $qry_in = "select client.client,client.clientname,agent.clientname as agentname,date_format(head.dateid, '%Y-%m') as monyear,
                sum(stock.ext) as amount

                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join client on head.clientid=client.clientid
                left join client as agent on agent.clientid = head.agentid
                left join cntnum on head.trno=cntnum.trno
                where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	              group by client.client,client.clientname,agent.clientname,head.dateid
	              order by  clientname";
        break;

      default:
        $qry_in = "
                select client.client,client.clientname,agent.clientname as agentname,date_format(head.dateid, '%Y-%m') as monyear,
                sum(stock.ext) as amount

                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join client on head.clientid=client.clientid
                left join client as agent on agent.clientid = head.agentid
                left join cntnum on head.trno=cntnum.trno
                where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	              group by client.client,client.clientname,agent.clientname,head.dateid

	              union all

                select client.client,client.clientname,agent.clientname as agentname,date_format(head.dateid, '%Y-%m') as monyear,
                sum(stock.ext) as amount
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join client on head.client=client.client
                left join client as agent on agent.client = head.agent
                left join cntnum on head.trno=cntnum.trno
                where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	              group by client.client,client.clientname,agent.clientname,head.dateid
                order by  clientname";
        break;
    }

    $query = "
    select 
 
    clientname,agentname,client,monyear,
    sum(amount) as amount
    from (
        $qry_in
    ) as v group by clientname,client,agentname,monyear order by clientname,monyear";
    $data = $this->coreFunctions->opentable($query);

    $result = [];
    if (!empty($data)) {
      foreach ($data as $cl) {
        $client = $cl->client;
        $yrmonth = $cl->monyear;

        if (!isset($result[$client])) {
          $result[$client] = [
            'clientname'  => $cl->clientname,
            'agentname'  => $cl->agentname,
            'months'   => [],
          ];
        }

        // If same month exists, sum the amount
        if (isset($result[$client]['months'][$yrmonth])) {
          $result[$client]['months'][$yrmonth]['amount'] += $cl->amount;
        } else {
          $result[$client]['months'][$yrmonth] = [
            'monyear' => $yrmonth,
            'amount'  => $cl->amount,
          ];
        }
      }
    }
    $result = array_values($result);
    return $result;
  }
  public function Item_Performance_Monthly_query($config)
  {

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center     = $config['params']['dataparams']['center'];

    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];

    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];

    $itemid = $config['params']['dataparams']['itemid'];
    $itemname = $config['params']['dataparams']['itemname'];
    $posttype = $config['params']['dataparams']['posttype'];

    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }
    if ($itemname != "") {
      $filter .= " and stock.itemid='$itemid'";
    }
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }
    switch ($posttype) {
      case 'unpost':
        $qry_in = "select item.barcode,item.itemname,date_format(head.dateid, '%Y-%m') as monyear,sum(stock.ext) as amount
           from item 
           left join lastock as stock on stock.itemid=item.itemid
           left join lahead as head on head.trno =stock.trno
           left join client on head.client=client.client
           left join client as agent on agent.client = head.agent
           left join cntnum on head.trno=cntnum.trno
  
           where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	         group by item.barcode,item.itemname,head.dateid
	         order by  itemname";

        break;
      case 'post':
        $qry_in = "select item.barcode,item.itemname,date_format(head.dateid, '%Y-%m') as monyear,sum(stock.ext) as amount
           from item 
           left join glstock as stock on stock.itemid=item.itemid
           left join glhead as head on head.trno =stock.trno
           left join client on head.clientid=client.clientid
           left join client as agent on agent.clientid = head.agentid
           left join cntnum on head.trno=cntnum.trno
  
           where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	         group by item.barcode,item.itemname,head.dateid
	         order by  itemname";
        break;

      default:
        $qry_in = "select item.barcode,item.itemname,date_format(head.dateid, '%Y-%m') as monyear,sum(stock.ext) as amount
           from item 
           left join glstock as stock on stock.itemid=item.itemid
           left join glhead as head on head.trno =stock.trno
           left join client on head.clientid=client.clientid
           left join client as agent on agent.clientid = head.agentid
           left join cntnum on head.trno=cntnum.trno
  
           where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	         group by item.barcode,item.itemname,head.dateid

           union all 

           select item.barcode,item.itemname,date_format(head.dateid, '%Y-%m') as monyear,sum(stock.ext) as amount
           from item 
           left join lastock as stock on stock.itemid=item.itemid
           left join lahead as head on head.trno =stock.trno
           left join client on head.client=client.client
           left join client as agent on agent.client = head.agent
           left join cntnum on head.trno=cntnum.trno
  
           where head.doc in ('SJ') and date(head.dateid) between '" . $start . "' and '" . $end . "'  $filter 
	         group by item.barcode,item.itemname,head.dateid
	         order by  itemname";
        break;
    }

    $query = "
    select 

    barcode,itemname,monyear,
    sum(amount) as amount
    from (
    $qry_in
    ) as v group by barcode,itemname,monyear order by itemname,monyear";
    $data =  $this->coreFunctions->opentable($query);
    $result = [];

    if (!empty($data)) {
      foreach ($data as $item) {
        $barcode = $item->barcode;
        $yrmonth = $item->monyear;

        if (!isset($result[$barcode])) {
          $result[$barcode] = [
            'barcode'  => $barcode,
            'itemname' => $item->itemname,
            'months'   => [],
          ];
        }
        // If same month exists, sum the amount
        if (isset($result[$barcode]['months'][$yrmonth])) {
          $result[$barcode]['months'][$yrmonth]['amount'] += $item->amount;
        } else {
          $result[$barcode]['months'][$yrmonth] = [
            'monyear' => $yrmonth,
            'amount'  => $item->amount,
          ];
        }
      }
    }


    $result = array_values($result);

    return $result;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];

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
    $str .= $this->reporter->col('CUSTOMER PERFORMANCE REPORT', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CODE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('CUSTOMER NAME', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    } elseif ($companyid == 32) { //3m
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AREA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    }
    return $str;
  }

  public function reportDefaultLayout($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      default:
        $result  = $this->reportDefault($config);
        $data1   = $this->reportDefault1($config);
        break;
    }

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];


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
    $str .= $this->default_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);


    $percent = 0;
    $total = 0;
    $tpercent = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $percent = ($data->amount / $data1[0]->amount) * 100;
      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $str .= $this->reporter->col($data->client, '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } elseif ($companyid == 32) { //3m
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }
      $tpercent += $percent;
      $percent = 0;

      $str .= $this->reporter->endrow();
      $total = $total + $data->amount;


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    } //end for each


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } elseif ($companyid == 32) { //3m
      $str .= $this->reporter->col('GRAND TOTAL :', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GRAND TOTAL :', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/><br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function Salesman_Performance_header_report($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];

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
    $str .= $this->reporter->col('AGENT PERFORMANCE REPORT', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->pagenumber('Page', 200, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '120', null, false, $border, 'TB', 'TL', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AGENT NAME', '580', null, false, $border, 'TB', 'TL', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PERCENT', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function reportSalesmanPerformace($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $result  = $this->SalesmanPerformace_query($config, 0);
    $data1  = $this->SalesmanPerformace_query($config, 1);

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
    $str .= $this->Salesman_Performance_header_report($config);


    $percent = 0;
    $total = 0;
    $tpercent = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $percent = ($data->amount / $data1[0]->amount) * 100;

      $str .= $this->reporter->col($data->agent, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agname, '580', null, false, $border, '', 'TL', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($percent, 2) . '%', '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $tpercent += $percent;
      $percent = 0;
      $total = $total + $data->amount;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->Salesman_Performance_header_report($config);
        $page = $page + $count;
      }
    } //end for each
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '580', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function Monthly_Performance_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER PERFORMANCE REPORT PER MONTHLY', null, null, false, $border, '', '', $font, 12, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->pagenumber('Page', 200, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '160', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Agent Name', '160', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');


    $r = $this->generateMonths($config, $start, $end);
    $str .= $r['str'];
    $str .= $this->reporter->col('Total Amount', '100', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function reportCustomerPerformace_perMonthly($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $result  = $this->Customer_Performance_Monthly_query($config);

    $count = 53;
    $page = 55;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $r = $this->generateMonths($config, $start, $end);
    if (!$r['status']) {
      return $r['str'];
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '25px;margin-top:5px;');
    $str .= $this->Monthly_Performance_header($config);


    $t1 = 0;
    $t2 = 0;
    $t3 = 0;
    $t4 = 0;
    $t5 = 0;
    $t6 = 0;
    $t7 = 0;
    $t8 = 0;
    $t9 = 0;
    $t10 = 0;
    $t11 = 0;
    $t12 = 0;


    $gtotal = 0;
    $sumrow = 0;

    foreach ($result as $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data['clientname'], '160', '', '', $border, '', 'TL', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data['agentname'], '160', '', '', $border, '', 'TL', $font, $fontsize, '', '', '', '');

      $countdisplay = 0;
      $mcount = 0;
      $continue = false;

      foreach ($data['months'] as $month) {
        $i = 0;
        if ($continue) {
          $i = $countdisplay;
          $this->coreFunctions->LogConsole(' Display: ' . $countdisplay);
        }
        for ($i; $i < count($r['monthsyear']); $i++) {
          $monyear = date('Y-m', strtotime($r['monthsyear'][$i]));
          $countdisplay++;
          if ($monyear == $month['monyear']) {
            $mcount = $mcount + 1;
            $str .= $this->reporter->col(number_format($month['amount'], 2), '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
            // $this->coreFunctions->LogConsole('barcode: ' . $data['barcode'] . ' ' . ' -- count: ' . count($data['months']) . ' mon: ' . $mcount . ' countdisplay: ' . $countdisplay);
            switch ($month['monyear']) { // month number
              case date('Y-m', strtotime($r['monthsyear'][0])):
                $t1 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][1])):
                $t2 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][2])):
                $t3 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][3])):
                $t4 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][4])):
                $t5 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][5])):
                $t6 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][6])):
                $t7 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][7])):
                $t8 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][8])):
                $t9 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][9])):
                $t10 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][10])):
                $t11 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][11])):
                $t12 += $month['amount'];
                break;
            }
            $sumrow += $month['amount'];
            if (count($data['months']) != $mcount) {
              $continue = true;
              $i = $countdisplay;
              break;
            }
          } else {
            $str .= $this->reporter->col('-', '65', '', '', '', '', 'RT', '', '', '', '', '', '');
            // $this->coreFunctions->LogConsole('barcode: ' . $data['barcode'] . ' ' . ' -- count: ' . count($data['months']) . ' mon: ' . $mcount . ' countdisplay: ' . $countdisplay);
          }
          if ($i == 11) {
            $countdisplay = 0;
            break;
          }
        }
        if ($countdisplay == 0) {
          $str .= $this->reporter->col(number_format($sumrow, 2), '100', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
          $gtotal = $gtotal + $sumrow;
          $sumrow = 0;
          $mcount = 0;
        }
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->Monthly_Performance_header($config);
        $page = $page + $count;
      }
    } //end for each

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '320', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t1, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t3, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t5, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t7, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t9, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t11, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '320', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t2, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t4, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t6, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t8, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t10, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t12, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  function generateMonths($config, $start, $end)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid";
    $toMonthIndex = function ($date) {
      $dt = new DateTime($date);
      return ((int)$dt->format('Y') * 12) + (int)$dt->format('m');
    };

    $fromIndex = $toMonthIndex($start);
    $toIndex   = $toMonthIndex($end);


    $totalmonths = ($toMonthIndex($end) - $toMonthIndex($start)) + 1;

    if ($totalmonths != 12) {
      $str = "
          <div style='position:relative;'>
            <div class='text-center' style='position:absolute; top:150px; left:400px;'>
              <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
              <br>
              <div style='font-size:32px; color:#1E1E1E'>DATE RANGE MUST BE 1 YEAR OR 12 MONTHS</div>
            </div>
          </div>
        ";
      return ['status' => false, 'str' => $str];
    }

    $monthsyear = [];
    $str = "";
    $yr = "";
    for ($i = $fromIndex; $i <= $toIndex; $i++) {
      $year = floor($i / 12);
      $month = $i % 12;

      if ($month === 0) {
        $month = 12;
        $year--;
      }

      $dt = DateTime::createFromFormat('Y-n', "$year-$month");

      if ($yr != "") {
        if ($yr == $dt->format('Y')) {
          $str .= $this->reporter->col($dt->format('M'), '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
          $yr = $dt->format('Y');
        } else {
          $str .= $this->reporter->col($dt->format('M - Y'), '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
          $yr = $dt->format('Y');
        }
      } else {
        $str .= $this->reporter->col($dt->format('M - Y'), '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $yr = $dt->format('Y');
      }

      $monthsyear[] = $dt->format('M - Y');
    }
    return ['status' => true, 'str' => $str, 'monthsyear' => $monthsyear];
  }
  public function Item_Monthly_Performance_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM PERFORMANCE REPORT PER MONTHLY', null, null, false, $border, '', '', $font, 12, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->pagenumber('Page', 200, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '160', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Item Name', '160', '', '', $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '');


    $r = $this->generateMonths($config, $start, $end);
    $str .= $r['str'];
    $str .= $this->reporter->col('Total Amount', '100', '', '', $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function reportItemPerformace_perMonthly($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $result  = $this->Item_Performance_Monthly_query($config);

    $count = 53;
    $page = 55;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $r = $this->generateMonths($config, $start, $end);
    if (!$r['status']) {
      return $r['str'];
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '25px;margin-top:5px;');
    $str .= $this->Item_Monthly_Performance_header($config);


    $t1 = 0;
    $t2 = 0;
    $t3 = 0;
    $t4 = 0;
    $t5 = 0;
    $t6 = 0;
    $t7 = 0;
    $t8 = 0;
    $t9 = 0;
    $t10 = 0;
    $t11 = 0;
    $t12 = 0;


    $gtotal = 0;
    $sumrow = 0;



    foreach ($result as $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      // $mon = 0;
      // $sumrow = 0;

      $str .= $this->reporter->col($data['barcode'], '160', '', '', $border, '', 'TL', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($data['itemname'], '160', '', '', $border, '', 'TL', $font, $fontsize, '', '', '', '');

      $countdisplay = 0;
      $mcount = 0;
      $continue = false;

      foreach ($data['months'] as $month) {
        // var_dump($data['months']);

        $i = 0;
        if ($continue) {
          $i = $countdisplay;
          $this->coreFunctions->LogConsole(' Display: ' . $countdisplay);
        }

        for ($i; $i < count($r['monthsyear']); $i++) {
          $monyear = date('Y-m', strtotime($r['monthsyear'][$i]));
          $countdisplay++;
          if ($monyear == $month['monyear']) {
            $mcount = $mcount + 1;
            $str .= $this->reporter->col(number_format($month['amount'], 2), '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
            // $this->coreFunctions->LogConsole('barcode: ' . $data['barcode'] . ' ' . ' -- count: ' . count($data['months']) . ' mon: ' . $mcount . ' countdisplay: ' . $countdisplay);
            switch ($month['monyear']) { // month number
              case date('Y-m', strtotime($r['monthsyear'][0])):
                $t1 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][1])):
                $t2 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][2])):
                $t3 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][3])):
                $t4 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][4])):
                $t5 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][5])):
                $t6 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][6])):
                $t7 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][7])):
                $t8 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][8])):
                $t9 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][9])):
                $t10 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][10])):
                $t11 += $month['amount'];
                break;
              case date('Y-m', strtotime($r['monthsyear'][11])):
                $t12 += $month['amount'];
                break;
            }
            $sumrow += $month['amount'];
            if (count($data['months']) != $mcount) {
              $continue = true;
              $i = $countdisplay;
              break;
            }
          } else {
            $str .= $this->reporter->col('-', '65', '', '', '', '', 'RT', '', '', '', '', '', '');
            // $this->coreFunctions->LogConsole('barcode: ' . $data['barcode'] . ' ' . ' -- count: ' . count($data['months']) . ' mon: ' . $mcount . ' countdisplay: ' . $countdisplay);
          }
          if ($i == 11) {
            $countdisplay = 0;
            break;
          }
        }
        if ($countdisplay == 0 || $countdisplay == 12) {
          $str .= $this->reporter->col(number_format($sumrow, 2), '100', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->endrow();
          $gtotal = $gtotal + $sumrow;
          $sumrow = 0;
          $mcount = 0;
        }
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->Item_Monthly_Performance_header($config);
        $page = $page + $count;
      }
    } //end for each

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '320', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($t1, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t3, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t5, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t7, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t9, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t11, 2), '65', '', '', $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, 'T', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($gtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '320', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t2, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t4, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t6, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t8, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t10, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '65', '', '', $border, '', 'RT', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($t12, 2), '65', '', '', $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class