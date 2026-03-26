<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class supplier_purchase_report
{
  public $modulename = 'Supplier Purchase Report';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
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

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.required', true);

    if ($systemtype == 'AMS') {
      $fields = ['radioposttype', 'radiosortby'];
    } else {
      if ($companyid == 8) { //maxipro
        $fields = ['radiotypeofreport', 'radioposttype'];
      } else {
        $fields = ['radiotypeofreport', 'radioposttype', 'radiosortby'];
      }
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

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $type = "'report' as typeofreport,";

    if ($systemtype == 'AMS') {
      $type = "";
    }

    $paramstr = "select
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '' as clientname,
    $type
    '0' as posttype,
    'docno' as sortby,
    '' as dclientname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as project, 0 as projectid, '' as projectname, 
    '' as dprojectname, '' as projectcode, 
    0 as deptid, '' as ddeptname, '' as dept, '' as deptname,
    '' as subprojectname ";

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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $result = $this->reportDefaultLayout_REPORT($config);
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];

      switch ($companyid) {
        case 28: //xcomp
          switch ($typeofreport) {
            case 'report':
            case 'lessreturn':
            case 'return':
              $result = $this->xcomp_Layout_REPORT($config);
              break;
          }
          break;
        default:
          switch ($typeofreport) {
            case 'report':
              $result = $this->reportDefaultLayout_REPORT($config);
              break;
            case 'lessreturn':
              $result = $this->reportDefaultLayout_LESSRETURN($config);
              break;
            case 'return':
              $result = $this->reportDefaultLayout_RETURN($config);
              break;
          }
          break;
      }
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0':
        $query = $this->reportDefault_POSTED($config);
        break;
      case  '1':
        $query = $this->reportDefault_UNPOSTED($config);
        break;
      default:
        $query = $this->default_QUERY_ALL($config);
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_POSTED($config)
  {
    $center       = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $typeofreport = '';
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid       = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $projectid = $config['params']['dataparams']['projectid'];
        $project = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['deptid'];
        $deptname = $config['params']['dataparams']['ddeptname'];

        if ($project != "") {
          $filter1 .= " and stock.projectid = $projectid";
        }
        if ($deptname != "") {
          $filter1 .= " and head.deptid = $deptid";
        }
        break;
      case 8: //maxipro
        $project = $config['params']['dataparams']['projectname'];
        $subprojectname = $config['params']['dataparams']['subprojectname'];

        if ($project != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and head.projectid = " . $projectid . "";
        }
        if ($subprojectname != "") {
          $filter1 .= " and head.subproject = '" . $subprojectname . "' ";
        }
        break;
    }

    $report_addedqry = "";
    $lessreturn_addedqry = "";
    switch ($companyid) {
      case 6: //mitsukoshi
        $report_addedqry = "
        union all
        select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
        head.yourref, sum(stock.ext) as amount
        from glhead as head left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join client as agent on agent.clientid=head.agentid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";

        $lessreturn_addedqry = "
        union all
        select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref,
        sum(case when head.doc='rp' then (stock.ext) else (stock.ext*-1) end) as amount
        from glhead as head left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join client as agent on agent.clientid=head.agentid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";
        break;
    }

    $amount = ",sum(stock.ext) as amount";
    $join = "left join glstock as stock on stock.trno=head.trno";
    $docfilter = "head.doc='rr'";
    $amountfilter = "";

    if ($systemtype == 'AMS') {
      $amount = ",sum(detail.cr-detail.db) as amount";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";

      $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
      client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
      head.yourref $amount
      from glhead as head 
      $join
      left join client on client.clientid=head.clientid
      left join client as agent on agent.clientid=head.agentid
      left join cntnum on cntnum.trno=head.trno
      where $docfilter and date(head.dateid) between '$start' and '$end'  $filter $filter1 $amountfilter
      group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
      $report_addedqry
      order  by $sortby";
    } else {
      $field = "";
      $ljoin = "";
      $group = "";
      $docfilter = "";
      if ($companyid == 28) { //xcomp
        switch ($typeofreport) {
          case 'lessreturn':
            $field = ",sum(ap.bal) as bal,head.ourref
            ,ifnull(sum(apdm.db),0) as lessreturn";
            $ljoin = "
            left join apledger as ap on ap.trno=stock.trno and ap.line=stock.line
            left join glstock as dm on dm.refx=stock.trno and dm.linex=stock.line
            left join apledger as apdm on apdm.trno=dm.trno
            ";
            $group = ",head.ourref";
            $docfilter = "('rr')";
            break;
          default:
            $field = ",sum(ap.bal) as bal,head.ourref";
            $ljoin = "left join apledger as ap on ap.trno=stock.trno and ap.line=stock.line";
            $group = ",head.ourref";
            $docfilter = "('rr','dm')";
            break;
        }
      }

      switch ($typeofreport) {
        case 'report':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, 
            head.yourref,ponum
            $report_addedqry
            order by head.dateid";
          } else {
            $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
            head.yourref, sum(stock.ext) as amount $field
            from glhead as head 
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $report_addedqry
            order  by $sortby";
          }

          break;
        case 'lessreturn':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref,
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount,
            ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('rr','dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref, ponum
            $lessreturn_addedqry
            order by head.dateid";
          } else {
            $query = "select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref,
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount $field
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc in ('rr') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $lessreturn_addedqry
            order by $sortby";
          }
          break;
        case 'return':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc='dm' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            order by head.dateid";
          } else {
            $query = "select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount $field
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc='dm' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            order by $sortby";
          }
          break;
      }
    }

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $center       = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $typeofreport = '';
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid       = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $projectid = $config['params']['dataparams']['projectid'];
        $project = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['deptid'];
        $deptname = $config['params']['dataparams']['ddeptname'];

        if ($project != "") {
          $filter1 .= " and stock.projectid = $projectid";
        }
        if ($deptname != "") {
          $filter1 .= " and head.deptid = $deptid";
        }
        break;
      case 8: //maxipro
        $project = $config['params']['dataparams']['projectname'];
        $subprojectname = $config['params']['dataparams']['subprojectname'];

        if ($project != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and head.projectid = " . $projectid . "";
        }
        if ($subprojectname != "") {
          $filter1 .= " and head.subproject = '" . $subprojectname . "' ";
        }
        break;
    }

    $report_addedqry = "";
    $lessreturn_addedqry = "";
    switch ($companyid) {
      case 6: //mitsukoshi
        $report_addedqry = "
        union all
        select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
        head.yourref, sum(stock.ext) as amount
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join client as agent on agent.client=head.agent
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";

        $lessreturn_addedqry = "
        union all
        select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, 
        sum(case when head.doc='rp' then (stock.ext) else (stock.ext*-1) end) as amount
        from lahead as head left join lastock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join client as agent on agent.client=head.agent
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";
        break;
    }


    $amount = ",sum(stock.ext) as amount";
    $join = "left join lastock as stock on stock.trno=head.trno";
    $docfilter = "head.doc='rr'";
    $amountfilter = "";

    if ($systemtype == 'AMS') {
      $amount = ",sum(detail.cr-detail.db) as amount";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";

      $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
      client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
      head.yourref $amount
      from lahead as head 
      $join
      left join client on client.client=head.client
      left join client as agent on agent.client=head.agent
      left join cntnum on cntnum.trno=head.trno
      where $docfilter and date(head.dateid) between '$start' and '$end'  $filter $filter1 $amountfilter
      group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
      $report_addedqry
      order by $sortby ";
    } else {
      $field = "";
      $ljoin = "";
      $group = "";
      $docfilter = "";
      if ($companyid == 28) { //xcomp
        switch ($typeofreport) {
          case 'lessreturn':
            $field = ",sum(ap.bal) as bal,head.ourref
            ,ifnull(sum(apdm.db),0) as lessreturn";
            $ljoin = "
            left join apledger as ap on ap.trno=stock.trno and ap.line=stock.line
            left join glstock as dm on dm.refx=stock.trno and dm.linex=stock.line
            left join apledger as apdm on apdm.trno=dm.trno
            ";
            $group = ",head.ourref";
            $docfilter = "('rr')";
            break;
          default:
            $field = ",sum(ap.bal) as bal,head.ourref";
            $ljoin = "left join apledger as ap on ap.trno=stock.trno and ap.line=stock.line";
            $group = ",head.ourref";
            $docfilter = "('rr','dm')";
            break;
        }
      }
      switch ($typeofreport) {
        case 'report':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from lastock as stock where stock.trno=head.trno),'') as ponum
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            $report_addedqry
            order by $sortby ";
          } else {
            $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount $field
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $report_addedqry
            order by $sortby ";
          }
          break;
        case 'lessreturn':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, 
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount,
            ifnull((select group_concat(distinct stock.ref separator ', ')
            from lastock as stock where stock.trno=head.trno),'') as ponum
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('rr','dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            $lessreturn_addedqry
            order by $sortby";
          } else {
            $query = "select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, 
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount $field
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc in ('rr') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $lessreturn_addedqry
            order by $sortby";
          }
          break;
        case 'return':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from lastock as stock where stock.trno=head.trno),'') as ponum
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            order by $sortby";
          } else {
            $query = "select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount $field
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc in ('dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            order by $sortby";
          }
          break;
      }
    }

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $center       = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if ($systemtype == 'AMS') {
      $typeofreport = '';
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid       = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $projectid = $config['params']['dataparams']['projectid'];
        $project = $config['params']['dataparams']['project'];
        $deptid = $config['params']['dataparams']['deptid'];
        $deptname = $config['params']['dataparams']['ddeptname'];

        if ($project != "") {
          $filter1 .= " and stock.projectid = $projectid";
        }
        if ($deptname != "") {
          $filter1 .= " and head.deptid = $deptid";
        }
        break;
      case 8: //maxipro
        $project = $config['params']['dataparams']['projectname'];
        $subprojectname = $config['params']['dataparams']['subprojectname'];

        if ($project != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and head.projectid = " . $projectid . "";
        }
        if ($subprojectname != "") {
          $filter1 .= " and head.subproject = '" . $subprojectname . "' ";
        }
        break;
    }

    $report_addedqry = "";
    $lessreturn_addedqry = "";
    switch ($companyid) {
      case 6: //mitsukoshi
        if ($posttype == 0) {
          $report_addedqry = "
          union all
          select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
          head.yourref, sum(stock.ext) as amount
          from glhead as head left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=stock.suppid
          left join client as agent on agent.clientid=head.agentid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
          group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";

          $lessreturn_addedqry = "
          union all
          select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref,
          sum(case when head.doc='rp' then (stock.ext) else (stock.ext*-1) end) as amount
          from glhead as head left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=stock.suppid
          left join client as agent on agent.clientid=head.agentid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
          group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";
        } else if ($posttype == 1) {
          $report_addedqry = "
          union all
          select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
          head.yourref, sum(stock.ext) as amount
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.clientid=stock.suppid
          left join client as agent on agent.client=head.agent
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
          group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";

          $lessreturn_addedqry = "
          union all
          select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, 
          sum(case when head.doc='rp' then (stock.ext) else (stock.ext*-1) end) as amount
          from lahead as head left join lastock as stock on stock.trno=head.trno
          left join client on client.clientid=stock.suppid
          left join client as agent on agent.client=head.agent
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('rp') and date(head.dateid) between '$start' and '$end'  $filter 
          group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref";
        }

        break;
    }

    $amount = ",sum(stock.ext) as amount";
    $docfilter = "head.doc='rr'";
    $amountfilter = '';
    $join = '';
    $from = '';

    if ($posttype == 0) {
      $join = "left join lastock as stock on stock.trno=head.trno";
      $from = 'from lahead as head';
    } else {
      $join = "left join glstock as stock on stock.trno=head.trno";
      $from = 'from glhead as head';
    }

    if ($systemtype == 'AMS') {
      $amount = ",sum(detail.cr-detail.db) as amount";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";

      $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
      client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
      head.yourref $amount
      $from 
      $join
      left join client on client.client=head.client
      left join client as agent on agent.client=head.agent
      left join cntnum on cntnum.trno=head.trno
      where $docfilter and date(head.dateid) between '$start' and '$end'  $filter $filter1 $amountfilter
      group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
      $report_addedqry
      order by $sortby ";
    } else {

      $field = "";
      $ljoin = "";
      $group = "";
      $docfilter = "";

      if ($companyid == 28) { //xcomp
        switch ($typeofreport) {
          case 'lessreturn':
            $field = ",sum(ap.bal) as bal,head.ourref
            ,ifnull(sum(apdm.db),0) as lessreturn";
            $ljoin = "
            left join apledger as ap on ap.trno=stock.trno and ap.line=stock.line
            left join glstock as dm on dm.refx=stock.trno and dm.linex=stock.line
            left join apledger as apdm on apdm.trno=dm.trno
            ";
            $group = ",head.ourref";
            $docfilter = "('rr')";
            break;
          default:
            $field = ",sum(ap.bal) as bal,head.ourref";
            $ljoin = "left join apledger as ap on ap.trno=stock.trno and ap.line=stock.line";
            $group = ",head.ourref";
            $docfilter = "('rr','dm')";
            break;
        }
      }

      switch ($typeofreport) {
        case 'report':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum $report_addedqry
            union all
            select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from lastock as stock where stock.trno=head.trno),'') as ponum
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum $report_addedqry
            order by $sortby;";
          } else {
            $query = "select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount $field
            from lahead as head 
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $report_addedqry
            union all
            select 'purchases' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
            head.yourref, sum(stock.ext) as amount 
            from glhead as head 
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc='rr' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $report_addedqry 
            order by $sortby;";
          }
          break;
        case 'lessreturn':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref,
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount,
            ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('rr','dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref, ponum
            $lessreturn_addedqry
            union all
            select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, 
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount,
            ifnull((select group_concat(distinct stock.ref separator ', ')
            from lastock as stock where stock.trno=head.trno),'') as ponum
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('rr','dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            $lessreturn_addedqry
            order by $sortby;";
          } else {
            $query = "select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref,
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount $field
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc in ('rr') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
            $group
            $lessreturn_addedqry
            union all
            select 'purchase less return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, 
            sum(case when head.doc='rr' then (stock.ext) else (stock.ext*-1) end) as amount $field
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc in ('rr') and date(head.dateid) between '$start' and '$end'  $filter $filter1 
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            $lessreturn_addedqry
            order by $sortby;";
          }
          break;
        case 'return':
          if ($companyid == 8) { //maxipro
            $query = "select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            where head.doc='dm' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            union all
            select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount,ifnull((select group_concat(distinct stock.ref separator ', ')
            from lastock as stock where stock.trno=head.trno),'') as ponum
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref,ponum
            order by $sortby";
          } else {
            $query = "select * from (select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount $field
            from glhead as head left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join client as agent on agent.clientid=head.agentid
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc='dm' and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            union all
            select 'purchase return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
            client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
            head.yourref, sum(stock.ext) as amount $field
            from lahead as head left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join client as agent on agent.client=head.agent
            left join cntnum on cntnum.trno=head.trno
            $ljoin
            where head.doc in ('dm') and date(head.dateid) between '$start' and '$end'  $filter $filter1
            group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref $group
            ) as x
            order by $sortby;";
          }
          break;
      }
    }

    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $typeofreport = "REPORT";
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

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
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER PURCHASE ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else if ($posttype == 1) {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
    }
    $str .= $this->reporter->col('Center :' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($companyid != 8) { //not maxipro
      if ($sortby == 'docno') {
        $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();

    if ($companyid == 8) { //maxipro
      $projectname = $config['params']['dataparams']['dprojectname'];
      $subproject  = $config['params']['dataparams']['subprojectname'];

      if ($projectname == '') {
        $projectname = 'ALL';
      }
      if ($subproject == '') {
        $subproject = 'ALL';
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projectname, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub-Project : ' . $subproject, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('PO #', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('REFERENCE #', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('SUPPLIER NAME', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '20', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('REFERENCE #', '20', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('SUPPLIER NAME', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('AMOUNT', '20', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_REPORT($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $typeofreport = "REPORT";
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $count = 56;
    $page = 55;
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
    $str .= $this->default_displayHeader($config);

    $totalamt = 0;

    foreach ($result as $key => $data) {
      $amt = number_format($data->amount, 2);
      if ($amt == 0) {
        $amt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 8) { //maxipros
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ponum, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '20', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '20', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_LESSRETURN($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 71;
    $page = 70;
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
    $str .= $this->default_displayHeader($config);

    $totalamt = 0;

    foreach ($result as $key => $data) {
      $amt = number_format($data->amount, 2);
      if ($amt == 0) {
        $amt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ponum, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '20', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $data->amount;

      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '20', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_RETURN($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 51;
    $page = 50;
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
    $str .= $this->default_displayHeader($config);

    $totalamt = 0;

    foreach ($result as $key => $data) {
      $amt = number_format($data->amount, 2);
      if ($amt == 0) {
        $amt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ponum, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->dateid, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '20', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }


      $totalamt = $totalamt + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->endrow();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '20', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();


    return $str;
  }

  private function xcomp_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $typeofreport = "REPORT";
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PURCHASE ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else if ($posttype == 1) {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
    }
    $str .= $this->reporter->col('Center :' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');


    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REFERENCE #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('OURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('SUPPLIER NAME', '220', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function xcomp_Layout_REPORT($config)
  {
    $result = $this->reportDefault($config);

    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $typeofreport = "REPORT";
    } else {
      $typeofreport = $config['params']['dataparams']['typeofreport'];
    }

    $count = 56;
    $page = 55;
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
    $str .= $this->xcomp_displayHeader($config);

    $totalamt = 0;

    foreach ($result as $key => $data) {
      if ($typeofreport == "lessreturn") {
        $amt = number_format($data->amount - $data->lessreturn, 2);
        $bal = number_format($data->bal - $data->lessreturn, 2);
      } else {
        $amt = number_format($data->amount, 2);
        $bal = number_format($data->bal, 2);
      }

      if ($amt == 0) {
        $amt = '-';
      }

      if ($bal == 0) {
        $bal = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '220', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($bal, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($amt, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->page_break();
        $str .= $this->xcomp_displayHeader($config);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '220', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function xcomp_Layout_LESSRETURN($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 71;
    $page = 70;
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
    $str .= $this->xcomp_displayHeader($config);

    $totalamt = 0;

    foreach ($result as $key => $data) {
      $amt = number_format($data->amount, 2);
      if ($amt == 0) {
        $amt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ponum, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '20', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $data->amount;

      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->page_break();
        $str .= $this->xcomp_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '20', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function xcomp_Layout_RETURN($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 51;
    $page = 50;
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
    $str .= $this->xcomp_displayHeader($config);

    $totalamt = 0;

    foreach ($result as $key => $data) {
      $amt = number_format($data->amount, 2);
      if ($amt == 0) {
        $amt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ponum, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->dateid, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('     ', '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($amt, '20', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $totalamt = $totalamt + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->endrow();
        $str .= $this->xcomp_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('     ', '30', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '20', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
